<?php

namespace models;

class citation
{
	private $_db; //an instance of models\database
	private $_id, $_type, $_preContextScope, $_postContextScope, $_preContextString, $_postContextString;
	private $_lastUpdated;
	private $_translations = array(); //an array of translation objects

	public function __construct($db, $id = null) {
		$this->_db = $db;
		if ($id) {
			$this->_id = $id;
			$this->_load();
		}
	}

	private function _load() {
		$sql = <<<SQL
			SELECT * FROM citation c WHERE id = :id
SQL;
		$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$row = $result[0];
		$this->_type = $row["type"];
		$this->_preContextScope = $row["preContextScope"];
		$this->_postContextScope = $row["postContextScope"];
		$this->_preContextString = $row["preContextString"];
		$this->_postContextString = $row["postContextString"];
		$this->_lastUpdated = $row["lastUpdated"];
		$this->_loadTranslations();
	}

	private function _loadTranslations() {
		$sql = <<<SQL
			SELECT translation_id FROM citation_translation WHERE citation_id = :id
SQL;
		$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
		foreach ($result as $row) {
			$this->_translations[] = new translation($this->_db, $row["translation_id"]);
		}
	}

	//GETTERS
	public function getId() {
		return $this->_id;
	}

	public function getType() {
		return $this->_type;
	}

	public function getPreContextScope() {
		return $this->_preContextScope;
	}

	public function getPostContextScope() {
		return $this->_postContextScope;
	}

	public function getPreContextString() {
		return $this->_preContextString;
	}

	public function getPostContextString() {
		return $this->_postContextString;
	}

	public function getTranslations() {
		return $this->_translations;
	}

	public function getLastUpdated() {
		return $this->_lastUpdated;
	}
}