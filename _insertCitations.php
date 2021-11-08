<?php

namespace models;

require_once 'includes/htmlHeader.php';


$db = new database();

//Get all the slips' data
$sql = <<<SQL
	SELECT * FROM slips
SQL;
$results = $db->fetch($sql);
foreach ($results as $slip) {

	//create the citation
	$sql = <<<SQL
		INSERT INTO citation (preContextScope, postContextScope) 
			VALUES ({$slip["preContextScope"]}, {$slip["postContextScope"]})
SQL;
	$db->exec($sql);

	//create the links between the slip and the new citation
	$citationId = $db->getLastInsertId();
	$sql = <<<SQL
		INSERT INTO slip_citation (slip_id, citation_id) 
			VALUES ({$slip["auto_id"]}, $citationId)
SQL;
	$db->exec($sql);

	//Translations
	if ($slip["translation"]) {   //only add a translation entry if the slip has translation content

		//create the translation
		$sql = <<<SQL
		INSERT INTO translation (content)
			VALUES (:translation)
SQL;
		$db->exec($sql, array(":translation" => $slip["translation"]));

		//create the link between the citation and the new translation
		$translationId = $db->getLastInsertId();
		$sql = <<<SQL
		INSERT INTO citation_translation (citation_id, translation_id)
			VALUES ({$citationId}, {$translationId})
SQL;
		$db->exec($sql);
	}
}


require_once 'includes/htmlFooter.php';