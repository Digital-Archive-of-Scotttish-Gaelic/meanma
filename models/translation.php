<?php

namespace models;

class translation
{
	private $_id, $_content, $_type, $_lastUpdated;
	private $_db; //an instance of models\database

	public function __construct($db, $id = null) {
		$this->_db = $db;
		if ($id) {
			$this->_id = $id;
			$this->_load();
		}
	}

	private function _load() {
		$sql = <<<SQL
			SELECT * FROM translation WHERE id = :id
SQL;
		$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$row = $result[0];
		$this->_content = $row["content"];
		$this->_type = $row["type"];
		$this->_lastUpdated = $row["lastUpdated"];
	}

	//GETTERS
	public function getId() {
		return $this->_id;
	}

	public function getContent() {
		return $this->_content;
	}

	public function getType() {
		return $this->_type;
	}

	public function getLastUpdated() {
		return $this->_lastUpdated;
	}


}