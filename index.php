<?php
namespace controllers;

require_once "includes/include.php";
require_once "includes/securityBootstrap.php";

$module = isset($_GET["m"]) ? $_GET["m"] : "";
$action = isset($_GET["a"]) ? $_GET["a"] : "";

$stateChangingRoutes = [
	'corpus:save',
	'writers:save',
	'collection:add',
	'slips:add',
];
if (in_array($module . ':' . $action, $stateChangingRoutes, true)) {
	\models\security::requireStateChangingRequest();
}

require_once "includes/htmlHeader.php";

// Anonymous requests may render only the existing authentication/recovery UI.
// Do not dispatch an application controller underneath the login modal.
if (!\models\users::isAuthenticated()) {
	require_once "includes/htmlFooter.php";
	exit;
}

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
    case "frequency":
		$controller = new frequency();
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
		if (!$slipId) {
			http_response_code(400);
			exit('A slip identifier is required');
		}
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

require_once "includes/htmlFooter.php";
