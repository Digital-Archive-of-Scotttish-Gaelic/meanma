<?php

require_once 'includes/htmlHeader.php';

if (empty($_GET["filepath"])) {
	$_GET["filepath"] = "13_Iasgach.xml";
}
if (empty($_GET["headword"])) {
	$_GET["headword"] = "airson";
}

$url = REST_PATH . "?filepath={$_GET["filepath"]}&headword={$_GET["headword"]}";

$result = file_get_contents($url);
echo <<<HTML
	<form method="get">
		<label>filepath:</label>
		<input type="text" name="filepath" value="{$_GET["filepath"]}">
		<label>headword</label>
		<input type="text" name="headword" value="{$_GET["headword"]}">
		<button type="submit">submit</button>
	</form>
	
	<h2>results:</h2>
		{$result}
HTML;


require_once 'includes/htmlFooter.php';

