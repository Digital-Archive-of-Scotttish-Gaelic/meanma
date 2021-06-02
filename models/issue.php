<?php


namespace models;


class issue
{
	private $_id;
	private $_description, $_userEmail, $_status, $_updated;
	private $_db; //an instance of models\database

	public function __construct($id = null) {
		$this->_id = $id;
	}

	//initialize the instance, setting class properties
	public function init($params) {
		$this->_setDescription($params["description"]);
		$this->_setUserEmail($params["userEmail"]);
		$this->_setStatus($params["status"]);
		$this->_setUpdated($params["updated"]);
	}

	//load the issue info from the database
	public function load() {
		if (!$this->_db) {
			$this->_db = new database();
		}
		$sql = <<<SQL
			SELECT * FROM issue WHERE id = :id
SQL;
		$results = $this->_db->fetch($sql, array(":id"=>$this->getId()));
		$issue = $this->init($results[0]);
		return $issue;
	}

	public function save() {
		if (!$this->_db) {
			$this->_db = new database();
		}
		$sql = <<<SQL
			REPLACE INTO issue (id, description, userEmail, status) VALUES(:id, :desc, :userEmail, :status)
SQL;
		$this->_db->exec($sql, array(":id"=>$this->getId(), ":desc"=>$this->getDescription(),
			":userEmail"=>$this->getUserEmail(), ":status"=>$this->getStatus()));
		$this->_setId($this->_db->getLastInsertId());
		return true;
	}

	// GETTERS
	public function getId() {
		return $this->_id;
	}

	public function getDescription() {
		return $this->_description;
	}

	public function getUserEmail() {
		return $this->_userEmail;
	}

	public function getStatus() {
		return $this->_status;
	}

	public function getUpdated() {
		return $this->_updated;
	}

	// SETTERS
	private function _setId($id) {
		$this->_id = $id;
	}

	private function _setDescription($desc) {
		$this->_description = $desc;
	}

	private function _setUserEmail($email) {
		$this->_userEmail = $email;
	}

	private function _setStatus($status) {
		$this->_status = $status;
	}

	private function _setUpdated($timestamp) {
		$this->_updated = $timestamp;
	}
}