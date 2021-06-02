<?php

namespace models;

require_once 'includes/include.php';

switch ($_REQUEST["action"]) {
	case "msPopulateModal":
		$ms = manuscripts::getMSById($_GET["id"]);
		$data = $ms->getModalData($_GET["chunkId"]);
		echo json_encode($data);
		break;
	case "msGetEditionHtml":
		$ms = manuscripts::getMSById($_GET["id"]);
		$xml = $ms->getXml();
		$xsl = new \DOMDocument;
		$filename = $_GET["mode"] == "diplo" ? "diplomatic.xsl" : "semiDiplomatic.xsl";
		$xsl->load('xsl/' . $filename);
		$proc = new \XSLTProcessor;
		$proc->importStyleSheet($xsl);
		echo $proc->transformToXML($xml);
		break;
	case "getUsername":
		$user = users::getUser($_GET["email"]);
		if ($user) {
			$_SESSION["email"] = $_GET["email"];
		}
		echo json_encode(array("firstname"=>$user->getFirstName(), "lastname"=>$user->getLastName()));
		break;
  case "getContext":
  	$tagContext = $_GET["simpleContext"] ? false : true;
    $handler = new xmlfilehandler($_GET["filename"]);
    $context = $handler->getContext($_GET["id"], $_GET["preScope"], $_GET["postScope"], true, false, $tagContext);
    echo json_encode($context);
    break;
	case "getSlips":
		$slipInfo = collection::getAllSlipInfo($_GET["offset"], $_GET["limit"], $_GET["search"], $_GET["sort"], $_GET["order"]);
		echo json_encode($slipInfo);
		break;
	case "updatePrintList":
			if ($_GET["addSlip"]) {
				$_SESSION["printSlips"][$_GET["addSlip"]] = $_GET["addSlip"];
			} else if ($_GET["removeSlip"]) {
				unset($_SESSION["printSlips"][$_GET["removeSlip"]]);
			}
			//return the size of the array
			echo json_encode(array("count" => count($_SESSION["printSlips"])));
		break;
  case "loadSlip":
    $slip = new slip($_GET["filename"], $_GET["id"], $_GET["auto_id"], $_GET["pos"], $_GET["preContextScope"], $_GET["postContextScope"]);
    $slip->updateResults($_GET["index"]); //ensure that "view slip" (and not "create slip") displays
    $filenameElems = explode('_', $slip->getFilename());
    $textId = $filenameElems[0];
    $results = array("locked"=>$slip->getLocked(), "auto_id"=>$slip->getAutoId(), "owner"=>$slip->getOwnedBy(),
	    "starred"=>$slip->getStarred(), "translation"=>$slip->getTranslation(), "notes"=>$slip->getNotes(),
      "preContextScope"=>$slip->getPreContextScope(), "postContextScope"=>$slip->getPostContextScope(),
      "wordClass"=>$slip->getWordClass(), "senses"=>$slip->getSensesInfo(),
      "lastUpdated"=>$slip->getLastUpdated(), "textId"=>$textId, "slipMorph"=>$slip->getSlipMorph()->getProps());
    //code required for modal slips
    $handler = new xmlfilehandler($_GET["filename"]);
    $context = $handler->getContext($_GET["id"], $results["preContextScope"], $results["postContextScope"]);
    $results["context"] = $context;
    $results['isOwner'] = $slip->getOwnedBy() == $_SESSION["user"];
    $user = users::getUser($_SESSION["user"]);
    $superuser = $user->getSuperuser();
    $results["canEdit"] =  $superuser || (!$slip->getIsLocked()) ? 1 : 0;
    //
    echo json_encode($results);
    break;
    //the following used for the gramar site
	case "loadSlipData":
		$result = collection::getSlipInfoBySlipId($_GET["id"]);
		$slipInfo = $result[0];
		$handler = new xmlfilehandler($slipInfo["filename"]);
		$context = $handler->getContext($slipInfo["id"], $slipInfo["preContextScope"], $slipInfo["postContextScope"]);
		$slipInfo["context"] = $context;
		echo json_encode($slipInfo);
		break;
	case "getSenseCategoriesForNewWordclass":
		$slip = new slip($_GET["filename"], $_GET["id"], $_GET["auto_id"], $_GET["pos"]);
		$slip->updateEntry($_GET["headword"], $_GET["wordclass"]);  //update entry with new wordclass
		$slip->saveSlip($_GET);
		$senses = $slip->getUnusedSenses();
		$unusedSenseInfo = array();
		foreach ($senses as $sense) {
			$unusedSenseInfo[$sense->getId()] = array("name" => $sense->getName(), "description" => $sense->getDescription());
		}
		echo json_encode($unusedSenseInfo);
		break;
  case "saveSlip":
    $slip = new slip($_POST["filename"], $_POST["id"], $_POST["auto_id"], $_POST["pos"],
      $_POST["preContextScope"], $_POST["postContextScope"]);
    unset($_POST["action"]);
    $slip->saveSlip($_POST);
    echo "success";
    break;
	case "autoCreateSlips":
		$search = new corpus_search($_GET, false);
		$results = $search->getDBResults();
		foreach ($results as $result) {
			if (!$result["auto_id"]) {
				new slip($result["filename"], $result["id"], "", $result["pos"]);
			}
		}
		echo json_encode(array("success" => true));
		break;
  case "saveSlipSense":
    sensecategories::saveSlipSense($_POST["slipId"], $_POST["senseId"]);
    collection::touchSlip($_POST["slipId"]);
    echo "success";
    break;
	case "addSense":
		$senseId = sensecategories::addSense($_GET["name"], $_GET["description"], $_GET["entryId"]);
		sensecategories::saveSlipSense($_GET["slipId"], $senseId);
		echo json_encode(array("senseId" => $senseId, "senseDescription" => $_GET["description"]));
		break;
	case "editSense":
		sensecategories::updateSense($_GET["id"], $_GET["name"], $_GET["description"]);
		//remove association with slip
		if ($_GET["slipId"]) {
			sensecategories::deleteSlipSense($_GET["slipId"], $_GET["id"]);
			collection::touchSlip($_GET["slipId"]);
		}
		break;
  case "getDictionaryResults":
    $locs = $_POST["locs"];
    $pagenum = $_POST["pageNumber"];
    $perpage = $_POST["pageSize"];
    $offset = $pagenum == 1 ? 0 : ($perpage * $pagenum) - $perpage;
    $locations = explode('|', $locs);
    $filename = "";
    $fileHandler = null;
    $results["hits"] = count($locations);
    $paginatedLocations = array_slice($locations, $offset, $perpage);
    foreach ($paginatedLocations as $location) {
      $elems = explode(' ', $location);
      if ($filename != $elems[0]) {
        $filename = $elems[0];
        $fileHandler = new xmlfilehandler($filename);
      }
      $context = $fileHandler->getContext($elems[1], 8, 8);
      $context["date"] = $elems[2];   //return the date of language
      $context["auto_id"] = $elems[3]; //return the auto_id (slip id)
      $context["title"] = str_replace("\\", " ", $elems[4]);   //return the title
      $context["page"] = $elems[5]; //return the page no
	    $context["tid"] = $elems[6];  //return the text ID
      $results["results"][] = $context;
    }
    echo json_encode($results);
    break;
	case "getGrammarInfo":
		$grammarInfo = lemmas::getGrammarInfo($_GET["id"], $_GET["filename"]);
		echo json_encode($grammarInfo);
		break;
	case "saveLemmaGrammar":
		echo lemmas::saveLemmaGrammar($_GET["id"], $_GET["filename"], $_GET["headwordId"],
			$_GET["slipId"], $_GET["grammar"]);
		break;
	case "requestUnlock":
			collection::requestUnlock($_GET["slipId"]);
		break;
	case "setGroup":
		users::updateGroupLastUsed($_GET["groupId"]);
		break;
	case "getSlowSearchResults":
		$slowSearch = new slow_search($_GET["id"]);
		$xpath = urldecode($_GET["xpath"]);
		$results = $slowSearch->search($xpath, $_GET["chunkSize"], $_GET["offsetFilename"], $_GET["offsetId"], $_GET["index"]);
		echo json_encode($results);
		break;
	case "raiseIssue":
		$issue = new issue();
		$issue->init($_GET);
		if ($issue->save()) {
			$message = "Issue successfully recorded";
		} else {
			$message = "Error! Issue was not saved";
		}
		echo json_encode(array("message" => $message));
		break;
	case "updateIssue":
		$issue = new issue($_GET["id"]);
		$issue->init($_GET);
		if ($issue->save()) {
			$message = "Issue successfully updated";
		} else {
			$message = "Error! Issue was not updated";
		}
		echo json_encode(array("message" => $message));
		break;
	default:
		echo json_encode(array("error"=>"undefined action"));
}
