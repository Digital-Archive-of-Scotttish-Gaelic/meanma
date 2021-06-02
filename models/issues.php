<?php


namespace models;


class issues
{
	public static function getAllIssues()
	{
		$issues = array();
		$db = new database();
		$sql = <<<SQL
			SELECT * FROM issue ORDER BY updated DESC	
SQL;
		$results = $db->fetch($sql);
		foreach ($results as $issueData) {
			$id = $issueData["id"];
			$issues[$id] = new issue($id);
			$issues[$id]->init($issueData);
		}
		return $issues;
	}
}