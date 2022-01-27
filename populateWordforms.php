<?php

use models;

require_once "includes/include.php";

if (!models\users::checkSuperuserAuth()) {
	die ("not authorised");
}

$db = new models\database();

$sql = <<<SQL
	SELECT l.wordform AS wordform, auto_id 
		FROM lemmas l 
		JOIN slips s ON s.filename = l.filename AND s.id = l.id
SQL;

$results = $db->fetch($sql);

$sql = <<<SQL
		UPDATE slips SET wordform = :wordform WHERE auto_id :slipId
SQL;
foreach ($results as $result) {
	$slipId = $result["auto_id"];
	$wordform = $result["wordform"];
//	$db->exec($sql, array(":wordform"=>$wordform, ":slipId"=>$slipId));
}