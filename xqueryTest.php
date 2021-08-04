<?php

require_once 'includes/htmlHeader.php';

if (empty($_GET["filepath"])) {
	$_GET["filepath"] = "13_Iasgach.xml";
}

$url = REST_PATH . '?filepath=' . $_GET["filepath"];

$result = file_get_contents(REST_PATH . "?filepath=" . $_GET["filepath"]);
echo <<<HTML
	<form method="get">
		<input type="text" name="filepath" value="{$_GET["filepath"]}">
		<button type="submit">submit</button>
	</form>
	
	<h2>lemmas:</h2>
	<ul>
		{$result}	
	</ul>
HTML;


require_once 'includes/htmlFooter.php';

