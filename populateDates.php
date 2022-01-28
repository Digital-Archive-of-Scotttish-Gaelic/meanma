<?php

use models;

require_once "includes/include.php";

if (!models\users::checkSuperuserAuth()) {
	die ("not authorised");
}

$db = new \models\database();
$sql = <<<SQL
	SELECT id FROM text ORDER BY id;
SQL;
$results = $db->fetch($sql);
foreach ($results as $result) {

	$textId = $result["id"];

	echo "<br>{$textId}";

	$text = new models\corpus_browse($textId, $db);

	if ($text->getDate() == NULL) {
		$date = $text->getParentText()->getDate();
		if ($date) {
			echo "<br>{$date}";
			$updateSql = <<<SQL
			UPDATE text SET date = {$date} WHERE id = '{$textId}'
SQL;
			$db->exec($updateSql);
		}
	}
}
