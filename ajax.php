<?php

namespace models;

require_once 'includes/include.php';

$db = new database(); //TODO: consider remodelling SB

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
  	$citation = new citation($db, $_GET["citationId"]);
	  $citation->attachToSlip($_GET["slipId"]);
  	$citation->setType($_GET["type"]);
  	$citation->setPreContextScope($_GET["preScope"]);
  	$citation->setPostContextScope($_GET["postScope"]);
	  $context = $citation->getContext(true);
    echo json_encode($context);
    break;
	case "getSlips":
		$slipInfo = collection::getAllSlipInfo($_GET["offset"], $_GET["limit"], $_GET["search"], $_GET["sort"], $_GET["order"], $_GET["type"], $db);
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
    $slip = ($_GET["slipType"] == "paper")
	    ? new paper_slip($_GET["auto_id"], $_GET["entryId"], null, $db)
	    : new corpus_slip($_GET["filename"], $_GET["id"], $_GET["auto_id"], $_GET["pos"], $db);
    $slip->updateResults($_GET["index"]); //ensure that "view slip" (and not "create slip") displays
    $filenameElems = explode('_', $slip->getFilename());
    $textId = $filenameElems[0];
    $results = array("locked"=>$slip->getLocked(), "auto_id"=>$slip->getId(), "owner"=>$slip->getOwnedBy(),
	    "starred"=>$slip->getStarred(), "notes"=>$slip->getNotes(),
      "wordClass"=>$slip->getWordClass(), "senses"=>$slip->getSensesInfo(),
      "lastUpdated"=>$slip->getLastUpdated(), "textId"=>$textId, "slipMorph"=>$slip->getSlipMorph()->getProps());
    //code required for modal slips
    $citations = $slip->getCitations();
    foreach ($citations as $citation) {
    	$citationId = $citation->getId();
	    $context = $citation->getContext(false);
	    $results["citation"][$citationId]["type"] = $citation->getType();
	    $results["citation"][$citationId]["context"] = $context["html"];
	    foreach ($citation->getTranslations() as $translation) {
	    	$tid = $translation->getId();
		    $results["citation"][$citationId]["translation"][$tid]["content"] = $translation->getContent();
		    $results["citation"][$citationId]["translation"][$tid]["type"] = $translation->getType();
	    }
    }
    $results['isOwner'] = $slip->getOwnedBy() == $_SESSION["user"];
    $user = users::getUser($_SESSION["user"]);
    $superuser = $user->getSuperuser();
    $results["canEdit"] =  $superuser || (!$slip->getIsLocked()) ? 1 : 0;
    //
    echo json_encode($results);
    break;
	case "getCitationsBySlipId":
		$citationInfo = array();
		$citationIds = collection::getCitationIdsForCitation($_GET["slipId"], $db);
		foreach ($citationIds as $cid) {
			$citation = new citation($db, $cid);
			$translations = $citation->getTranslations();
			$translation = isset($translations[0]) ? $translations[0]->getContent() : null;
			$slip = $citation->getSlip();
			$reference = $slip->getReference();
			$citationInfo[$citation->getType()] = array("cid"=>$cid, "context"=>$citation->getContext(false), "translation"=>$translation, "reference"=>$reference);
		}
		echo json_encode($citationInfo);
    break;
	case "loadCitation":
		if ($_GET["id"] == "-1") {  //create a new citation
			$citation = new citation($db);
			$citation->attachToSlip($_GET["slipId"]);
		} else {
			$citation = new citation($db, $_GET["id"]);
		}
		$slip = $citation->getSlip();
		$slipType = $slip->getType();
		$translations = $citation->getTranslations();
		$translationCount = count($translations);
		$translationIds = $firstTranslationContent = $firstTranslationType = "";
		if ($translationCount) {
			$translationIds = $citation->getTranslationIdsString();
			$firstTranslationContent = $translations[0]->getContent();
			$firstTranslationType = $translations[0]->getType();
		}
		$context = $citation->getContext(true); //check context 1st to ensure correct pre and post scope values
		$citationData = array("id" => $citation->getId(), "preScope" => $citation->getPreContextScope(),
			"postScope" => $citation->getPostContextScope(), "type" => $citation->getType(),
			"firstTranslationContent" => $firstTranslationContent, "firstTranslationType" => $firstTranslationType,
			"translationCount" => $translationCount, "translationIds" => $translationIds);
		//check whether we are dealing with a corpus slip or a paper slip
		if ($slipType == "corpus") {  //corpus slip
			$citationData["context"] = $context;
		} else {                                                                    //paper slip
			$citationData["preContextString"] = $citation->getPreContextString();
			$citationData["postContextString"] = $citation->getPostContextString();
		}
		$citationData["type"] = $slipType;
		echo json_encode($citationData);
		break;
	case "saveCitation":
		$citation = new citation($db, $_GET["id"]);
		$citation->setType($_GET["type"]);
		$citation->setPreContextScope($_GET["preScope"]);
		$citation->setPostContextScope($_GET["postScope"]);
		$citation->setPreContextString($_POST["preContextString"]);
		$citation->setPostContextString($_POST["postContextString"]);
		$citation->save();
		break;
	case "createTranslation":
		$translation = new translation($db, null, $_GET["citationId"]);
		$translationCount = count($translation->getCitation()->getTranslations());
		echo json_encode(array("id" => $translation->getId(), "type" => $translation->getType(),
			"content" => "", "translationCount" => $translationCount));
		break;
	case "loadTranslation":
		$translation = new translation($db, $_GET["id"]);
		$citation = $translation->getCitation();
		echo json_encode(array("type" => $translation->getType(), "content" => $translation->getContent(), "cid" => $citation->getId()));
		break;
	case "saveTranslation":
		$translation = new translation($db, $_POST["translationId"], $_POST["citationId"]);
		$translation->setType($_POST["type"]);
		$translation->setContent($_POST["content"]);
		$translation->save();
		break;
	case "deleteSlips":
				//! only superusers can do this
		$user = users::getUser($_SESSION["email"]);
		if (!$user->getSuperuser()) {
			return json_encode(array("message" => "not authorised"));
		}
		collection::deleteSlips($_GET["slipIds"]);
		break;
	case "loadSlipData":
		$result = collection::getSlipInfoBySlipId($_GET["id"], $db, $_GET["groupId"]);
		$slipInfo = $result[0];
		$handler = new xmlfilehandler($slipInfo["filename"]);
		$context = $handler->getContext($slipInfo["id"], $slipInfo["preContextScope"], $slipInfo["postContextScope"]);
		$slipInfo["context"] = $context;
		echo json_encode($slipInfo);
		break;
	case "createPaperSlip":
		$slip = new paper_slip(null, $_GET["entryId"], $_GET["wordform"], $db);
		echo json_encode(array("id" => $slip->getId(), "wordclass" => $slip->getWordClass(), "pos" => $slip->getPOS()));
		break;
	case "getSenseCategoriesForNewWordclass":
		$slip = ($_GET["slipType"] == "corpus")
			? new corpus_slip($_GET["filename"], $_GET["id"], $_GET["auto_id"], $_GET["pos"], $db)
			: new paper_slip($_GET["auto_id"], $_GET["entryId"], null, $db);
		$oldEntryId = $slip->getEntryId();
		$slip->updateEntry($_GET["headword"], $_GET["wordclass"]);  //update entry with new wordclass
		$_GET["entryId"] = $slip->getEntryId();
		$slip->saveSlip($_GET);
		// check the old entry for this slip and delete if now empty
		if (entries::isEntryEmpty($oldEntryId)) {
			entries::deleteEntry($oldEntryId);
		}
		$senses = $slip->getUnusedSenses();
		$unusedSenseInfo = array();
		foreach ($senses as $sense) {
			$unusedSenseInfo[$sense->getId()] = array("name" => $sense->getName(), "description" => $sense->getDescription());
		}
		echo json_encode(array("entryId" => $slip->getEntryId(), "senseInfo" => $unusedSenseInfo));
		break;
  case "saveSlip":
    $slip = ($_GET["slipType"] == "corpus")
	    ? new corpus_slip($_POST["filename"], $_POST["id"], $_POST["auto_id"], $_POST["pos"], $db)
	    : new paper_slip($_POST["auto_id"], $_POST["entryId"], $_POST["wordform"], $db);
    unset($_POST["action"]);
    $slip->saveSlip($_POST);
    echo "success";
    break;
	case "addTextIdToSlip":
		collection::addTextIdToSlip($_GET["slipId"], $_GET["textId"], $db);
		echo json_encode(array("msg" => "success"));
		break;
	case "getSlipLinkHtml":
		$slipId = collection::slipExists($_SESSION["groupId"], $_GET["filename"], $_GET["id"], $db);
		$lemma = urldecode($_GET["lemma"]); // decode required for MSS weird chrs
		$data = $slipId
			? collection::getSlipInfoBySlipId($slipId, $db)[0]    //there is a slip so use the data
			: array("filename"=>$_GET["filename"], "id"=>$_GET["id"], "pos"=>$_GET["pos"], "lemma"=>$lemma);  //new slip
		echo collection::getSlipLinkHtml($data, null, $db);
		break;
	case "autoCreateSlips":
		$search = new corpus_search($_GET, false);
		$results = $search->getDBResults();
		foreach ($results as $result) {
			if (!$result["auto_id"]) {
				new corpus_slip($result["filename"], $result["id"], "", $result["pos"], $db);
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
	case "createEntry":
		//creates an entry if the combination does not already exist
		$entry = entries::getEntryByHeadwordAndWordclass($_GET["headword"], $_GET["wordclass"], $db);
		echo json_encode(array("id" => $entry->getId()));
		break;
	case "getSlowSearchResults":
		$slowSearch = new slow_search($_GET["id"], $db);
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
