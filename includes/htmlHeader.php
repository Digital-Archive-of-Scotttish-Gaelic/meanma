<?php

require_once "include.php";

$name = "";
//check login state
$loggedInHide = "hide";

$groupTheme = "007bff"; //default colour scheme (Faclair theme)

$loginControl = new controllers\login();
if ($loginControl->isLoggedIn() || ($_SESSION["email"] && $_POST["loginAction"] == "savePassword")) {
	$email = $_SESSION["user"] ? $_SESSION["user"] : $_SESSION["email"];
	$user = models\users::getUser($email);
	$userGroups = $user->getGroups();
	$lastUsedGroup = $user->getLastUsedGroup();
	$groupTheme = $lastUsedGroup->getTheme();
	$_SESSION["groupId"] = $lastUsedGroup->getId() ? $lastUsedGroup->getId() : 1;
	$groupHtml = "";
	if (count($userGroups ) > 1) {
		$groupHtml = <<<HTML
			<select class="selectpicker show-tick" data-width="150px">
HTML;

		foreach ($userGroups as $group) {
			$groupHtml .= <<<HTML
				<option style="background:#{$group->getTheme()}; color:#fff;" value="{$group->getId()}">{$group->getName()}</option>
HTML;
		}
		$groupHtml .= "</select>";
	}
	$name = $user->getFirstName() . ' ' . $user->getLastName();
	$loggedInHide = "";
}

echo <<<HTML

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" type="text/css" href="css/style.css">
  <link href="https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
  <link rel="stylesheet" type="text/css" href="css/simplePagination.css">
  <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.17.1/dist/bootstrap-table.min.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/zoomio@2.0.2/zoomio.css">
  <title>MEANⓂ️A</title>
  <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.2/dist/jquery.validate.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
	<script src="https://unpkg.com/bootstrap-table@1.17.1/dist/bootstrap-table.min.js"></script>
	<script src="https://cdn.ckeditor.com/4.14.1/basic/ckeditor.js"></script>
	<script src="https://kit.fontawesome.com/0b481d2098.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/js-cookie@2.2.1/src/js.cookie.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/zoomio@2.0.2/zoomio.min.js"></script>
	<script>
		$(function () {
		  $('.selectpicker').change(function () {
		    var groupId = $(this).children("option:selected"). val();
		    $.ajax({url: 'ajax.php?action=setGroup&groupId='+groupId})
		      .done(function () {
		        window.location.reload(true);
		    });
		    return false;
		  });
		});
	</script>
</head>
<body style="padding-top: 80px;">
  <div class="container-fluid container-fluid h-100 d-flex flex-column">
    <nav class="navbar navbar-dark fixed-top navbar-expand-lg" style="background-color: #{$groupTheme};">
      <a class="navbar-brand" href="index.php" style="font-size:x-large;">MEANⓂ️A</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
        <div class="navbar-nav">
					<a class="nav-item nav-link" title="browse corpus" href="?m=corpus&a=browse">browse</a>
          <a class="nav-item nav-link" title="search corpus" href="?m=corpus&a=search&id=0">search</a>
			    <a class="nav-item nav-link" title="browse collection" href="?m=collection&a=browse">collection</a>
			    <a class="nav-item nav-link" title="browse entries" href="?m=entries&a=browse">entries</a>
			    <a class="nav-item nav-link" title="RTFM" href="?m=docs&action=view">docs</a>
          <span class="loggedIn {$loggedInHide}">
            <form method="post">
              <a id="logoutLink" href="index.php?loginAction=logout" class="btn btn-link nav-link nav-item">logout</a>
            </form>
					</span>
					<div class="navbar-nav>">
						{$groupHtml}
					</div>
        </div>
        <div class="navbar-nav ml-auto">
          <span class="loggedIn {$loggedInHide}">
            <a id="loggedInAs" class="nav-link disabled" href="#">logged in as {$name}</a>
          </span>
        </div>
      </div>
    </nav>
HTML;

$loginControl->runAction();
