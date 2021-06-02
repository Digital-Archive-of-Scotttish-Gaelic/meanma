<?php


namespace models;


class sense
{
	private $_id, $_name, $_description, $_entryId;
	private $_db;  //database connection

	public function __construct($id) {
		$this->_id = $id;
		$this->_db = isset($this->_db) ? $this->_db : new database();
		$this->_load();
	}

	private function _load() {
		$sql = <<<SQL
			SELECT name, description, entry_id FROM sense WHERE id = :id
SQL;
		$results = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$this->_init($results[0]);
	}

	private function _init($params) {
		$this->_setName($params["name"]);
		$this->_setDescription($params["description"]);
		$this->_setEntryId($params["entry_id"]);
	}

	//SETTERS

	private function _setName($name) {
		$this->_name = $name;
	}

	private function _setDescription($description) {
		$this->_description = $description;
	}

	private function _setEntryId($entryId) {
		$this->_entryId = $entryId;
	}

	//GETTERS

	public function getId() {
		return $this->_id;
	}

	public function getName() {
		return $this->_name;
	}

	public function getDescription() {
		return $this->_description;
	}

	public function getEntryId() {
		return $this->_entryId;
	}


}