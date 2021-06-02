<?php


namespace models;


class manuscripts
{
	public static function getAllMSS() {
		$mss = array();
		$db = new database();
		$sql = <<<SQL
			SELECT * FROM text t 
				WHERE id LIKE '804-%';
SQL;
		$results = $db->fetch($sql);
		foreach ($results as $result) {
			$id = trim($result["id"]);
			$mss[$id] = new manuscript($id);
			$mss[$id]->setTitle($result["title"]);
			$mss[$id]->setFilename($result["filepath"]);
		}
		return $mss;
	}

	public static function getMSById($id) {
		$db = new database();
		$sql = <<<SQL
			SELECT * FROM text 
				WHERE id = :textId
SQL;
		$result = $db->fetch($sql, array(":textId"=>$id));
		$ms = new manuscript($id);
		$ms->setTitle($result[0]["title"]);
		$ms->setFilename($result[0]["filepath"]);
		$ms->loadXml();
		return $ms;
	}
}