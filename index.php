<?php
namespace controllers;

require_once "includes/htmlHeader.php";

$module = isset($_GET["m"]) ? $_GET["m"] : ""; // this doesn't do anything surely
$action = isset($_GET["a"]) ? $_GET["a"] : "";

switch ($module) {
	case "corpus":
		$controller = new corpus();
		break;
	case "writers":
		$controller = new writers();
		break;
	case "districts":
		$controller = new districts();
		break;
	case "collection":
		$controller = new collection(); // START HERE
		break;
  /*
	case "dictionary":
		$controller = new dictionary();
		break;
	case "documentation":
		$controller = new documentation();
		break;
	*/
	case "slips":
		$controller = new collection();
		break;
	case "slip":
		$slipId = !empty($_GET["auto_id"]) ? $_GET["auto_id"] : false;
		$controller = new slip($slipId);
		break;
	case "entries":
		$controller = new entries();
		break;
	case "issues":
		$controller = new issue();
		break;
	/**
	**/
	default:
		$controller = new home();
}

$controller->run($action);

require_once "includes/htmlFooter.php"; // ditto
