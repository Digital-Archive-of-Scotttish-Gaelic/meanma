<?php
namespace controllers;

// Composer dependencies must be bootstrapped by tracked application code.
// includes/include.php is deployment-specific and is not stored in Git.
require_once __DIR__ . "/vendor/autoload.php";
require_once "includes/include.php";
require_once "includes/securityBootstrap.php";

$loginControl = new login();
$loginAction = (string) ($_POST['loginAction'] ?? $_GET['loginAction'] ?? '');
if ($loginAction !== '') {
	$loginControl->runAction();
	exit;
}

$module = isset($_GET["m"]) ? $_GET["m"] : "";
$action = isset($_GET["a"]) ? $_GET["a"] : "";

$stateChangingRoutes = [
	'corpus:save',
	'writers:save',
	'collection:add',
	'slips:add',
	'users:create',
	'users:save',
	'users:resetPassword',
];
if (in_array($module . ':' . $action, $stateChangingRoutes, true)) {
	\models\security::requireStateChangingRequest();
}

if ($module === 'users' && !\models\users::checkSuperuserAuth()) {
	http_response_code(403);
	exit('Access denied');
}

if ($module === 'users') {
	ob_start();
}

// XLSX responses must be sent before the shared HTML header emits output.
if ($module === 'frequency' && $action === 'export') {
	$controller = new frequency();
	$controller->run($action);
	exit;
}

require_once "includes/htmlHeader.php";

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
	case "users":
		$controller = new usermanagement();
		break;
	/**
	**/
	default:
		$controller = new home();
}

$controller->run($action);

require_once "includes/htmlFooter.php";

if ($module === 'users' && ob_get_level() > 0) {
	ob_end_flush();
}
