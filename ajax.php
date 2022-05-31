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
    $slip = ($_GET["slipType"])
	    ? new paper_slip($_GET["auto_id"], $_GET["entryId"], null, $db)
	    : new corpus_slip($_GET["filename"], $_GET["id"], $_GET["auto_id"], $_GET["pos"], $db);
    $slip->updateResults($_GET["index"]); //ensure that "view slip" (and not "create slip") displays
    $textId = $slip->getTextId();
    $referenceTemplate = $slip->getText()->getReferenceTemplate();
    $results = array("locked"=>$slip->getLocked(), "auto_id"=>$slip->getId(), "owner"=>$slip->getOwnedBy(),
	    "starred"=>$slip->getStarred(), "notes"=>$slip->getNotes(), "type"=>$slip->getType(),
      "wordClass"=>$slip->getWordClass(), "senses"=>$slip->getPilesInfo(),
      "lastUpdated"=>$slip->getLastUpdated(), "textId"=>$textId, "slipMorph"=>$slip->getSlipMorph()->getProps(),
	    "referenceTemplate"=>$referenceTemplate);
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
		$citationIds = collection::getCitationIdsForSlip($_GET["slipId"], $db);
		foreach ($citationIds as $cid) {
			$citation = new citation($db, $cid);
			$translations = $citation->getTranslations();
			$translation = isset($translations[0]) ? $translations[0]->getContent() : null;
			$slip = $citation->getSlip();
			$reference = $slip->getReference();   //deprecate once auto references are in place
			$page = $slip->getPage();   //also a temporary solution SB - remember that this WILL slow the system
			$referenceTemplate = $slip->getText()->getReferenceTemplate(); //refactor once manual references are deprecated
			$citationInfo[$citation->getType()] = array("cid"=>$cid, "context"=>$citation->getContext(false), "translation"=>$translation, "reference"=>$reference,
				"referenceTemplate"=>$referenceTemplate, "page"=>$page);
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
		$tagContext = $_GET["context"] == "false" ? false : true;     //used to provide popups for trimming context
		$edit = $_GET["edit"];   //used to provide dropdown menus for editing citations
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
		$context = $citation->getContext($tagContext, $edit); //check context 1st to ensure correct pre and post scope values
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
	case "deleteCitation":
		//! only superusers can do this
		/*$user = users::getUser($_SESSION["email"]);
		if (!$user->getSuperuser()) {
			return json_encode(array("message" => "not authorised"));
		}*/
		citation::delete($_GET["id"], $db);
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
	case "deleteTranslation":
		//! only superusers can do this
		/*
		$user = users::getUser($_SESSION["email"]);
		if (!$user->getSuperuser()) {
			return json_encode(array("message" => "not authorised"));
		} */
		translation::delete($_GET["id"], $db);
		break;
	case "deleteSlips":
				//! only superusers can do this
		$user = users::getUser($_SESSION["email"]);
		if (!$user->getSuperuser()) {
			return json_encode(array("message" => "not authorised"));
		}
		collection::deleteSlips($_GET["slipIds"], $db);
		break;
	case "deleteEntries":
		//! only superusers can do this
		$user = users::getUser($_SESSION["email"]);
		if (!$user->getSuperuser()) {
			return json_encode(array("message" => "not authorised"));
		}
		$response = entries::deleteEntries($_GET["entryIds"], $db);
		echo json_encode(array("response" => $response));
		break;
	case "loadSlipData":                             //this is only used externally as an API (by e.g. briathradan
		$result = collection::getSlipInfoBySlipId($_GET["id"], $db, $_GET["groupId"]);
		$slip = collection::getSlipBySlipId($_GET["id"], $db);
		$citations = $slip->getCitations();
		$citation = reset($citations);
		$translations = $citation->getTranslations();
		$translation = reset($translations);
		$slipInfo = $result[0];
		$slipInfo["context"] = $citation->getContext();
		$slipInfo["translation"] = $translation->getContent();
		echo json_encode($slipInfo);
		break;
	case "createPaperSlip":
		$slip = new paper_slip(null, $_GET["entryId"], $_GET["wordform"], $db);
		echo json_encode(array("id" => $slip->getId(), "wordclass" => $slip->getWordClass(), "pos" => $slip->getPOS()));
		break;
	case "getPileCategoriesForNewWordclass":
		$slip = ($_GET["slipType"] == "corpus")
			? new corpus_slip($_GET["filename"], $_GET["id"], $_GET["auto_id"], $_GET["pos"], $db)
			: new paper_slip($_GET["auto_id"], $_GET["entryId"], null, $db);
		$oldEntryId = $slip->getEntryId();
		$slip->updateEntry($_GET["headword"], $_GET["wordclass"]);  //update entry with new wordclass
		$_GET["entryId"] = $slip->getEntryId();
		$slip->saveSlip($_GET);
		// check the old entry for this slip and delete if now empty
		if (entries::isEntryEmpty($oldEntryId, $db)) {
			entries::deleteEntry($oldEntryId, $db);
		}
		$piles = $slip->getUnusedPiles();
		$unusedPileInfo = array();
		foreach ($piles as $pile) {
			$unusedPileInfo[$pile->getId()] = array("name" => $pile->getName(), "description" => $pile->getDescription());
		}
		echo json_encode(array("entryId" => $slip->getEntryId(), "pileInfo" => $unusedPileInfo));
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
		$search = new corpus_search($_GET, false, $db);
		$results = $search->getDBResults();
		foreach ($results as $result) {
			if (!$result["auto_id"]) {
				new corpus_slip($result["filename"], $result["id"], "", $result["pos"], $db);
			}
		}
		echo json_encode(array("success" => true));
		break;
  case "saveSlipPile":
    pilecategories::saveSlipPile($_POST["slipId"], $_POST["pileId"], $db);
    collection::touchSlip($_POST["slipId"]);
    echo "success";
    break;
	case "addPile":
		$pileId = pilecategories::addPile($_GET["name"], $_GET["description"], $_GET["entryId"], $db);
		pilecategories::saveSlipPile($_GET["slipId"], $pileId, $db);
		echo json_encode(array("pileId" => $pileId, "pileDescription" => $_GET["description"]));
		break;
	case "editPile":
		pilecategories::updatePile($_GET["id"], $_GET["name"], $_GET["description"], $db);
		//remove association with slip
		if ($_GET["slipId"]) {
			pilecategories::deleteSlipPile($_GET["slipId"], $_GET["id"], $db);
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
      $context["title"] = str_replace("\\", " ", $elems[4]);   //return the title.
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
		case "saveEntry":
		$entry = entries::getEntryById($_POST["id"], $db);
		$entry->setSubclass($_POST["subclass"]);
		$entry->setNotes($_POST["notes"]);
		$entry->setEtymology($_POST["etymology"]);
		$entry->saveEntry($db);
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
	case "createEmendation":
		$emendation = new emendation($db, null, $_GET["cid"]);
		$emendation->setType($_GET["type"]);
		$emendation->setTokenId($_GET["tid"]);
		$emendation->setPosition($_GET["pos"]);
		$emendation->save();
		echo json_encode(array("id" => $emendation->getId()));
		break;
	case updateEmendation:
		$emendation = new emendation($db, $_GET["id"]);
		$emendation->setContent($_GET["content"]);
		$emendation->save();
		break;
	case "deleteEmendation":
		emendation::delete($_GET["id"], $db);
		break;
	case "createDeletion":
		$deletion = new deletion($db, null, $_GET["cid"]);
		$deletion->setTokenIdStart($_GET["tid"]);
		$deletion->save();
		echo json_encode(array("id" => $deletion->getId()));
		break;
	case "updateDeletion":
		$deletion = new deletion($db, $_GET["id"]);
		$deletion->setTokenIdEnd($_GET["tid"]);
		if ($_GET["startId"]) {
			$deletion->setTokenIdStart($_GET["startId"]);
		}
		$deletion->save();
		echo json_encode(array("message" => "saved"));
		break;
	case "deleteDeletion":
		deletion::delete($_GET["id"], $db);
		break;
	case "saveSense":
		$sense = new sense($db, $_POST["id"], $_POST["entryId"]);
		$sense->setLabel($_POST["label"]);
		$sense->setDefinition($_POST["definition"]);
		if ($_POST["parentId"]) {
			$sense->setSubsenseOf($_POST["parentId"]);
		}
		$sense->save();
		echo json_encode(array("id" => $sense->getId()));
		break;
	case "swapSense":
		$sense = new sense($db, $_GET["sid"]);
		$swapId = null;
		switch ($_GET["dir"]) {
			case "up":
			case "down":
				$swapId = $sense->swapSense($_GET["dir"]);
				$position = $sense->getSensePosition(); //whether this sense is first and/or last in the sort order
				$swapSense = new sense($db, $swapId);
				$swapPosition = $swapSense->getSensePosition();
				break;
			case "left":
				$swapId = $sense->swapSense($_GET["dir"]);
				$position = array("last" => 1);
				$swapPosition = "";
				break;
		}
		echo json_encode(array("id" => $swapId, "position" => $position, "swapPosition" => $swapPosition,
			"parentId" => $sense->getSubsenseOf()));
		break;
	default:
		echo json_encode(array("error"=>"undefined action"));
}
