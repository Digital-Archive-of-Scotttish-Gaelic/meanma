<?php

namespace models;

class deletion
{
	private $_id, $_tokenIdStart, $_tokenIdEnd, $_lastUpdated;
	private $_db;   //an instance of models\database
	private $_citation;  //an instance of models\citation

	public function __construct($db, $id = null, $citationId = null) {
		$this->_db = $db;
		if ($id) {
			$this->_id = $id;
			$this->_load();
		} else {
			$this->_citation = new citation($db, $citationId);
			$this->_init();
		}
	}

	private function _load() {
		$sql = <<<SQL
			SELECT * FROM deletion WHERE id = :id
SQL;
		$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$row = $result[0];
		$this->_tokenIdStart = $row["token_id_start"];
		$this->_tokenIdEnd = $row["token_id_end"];
		$this->_lastUpdated = $row["lastUpdated"];
	}

	public function getId() {
		return $this->_id;
	}

	private function _init() {
		//create a holding entry in the database to get the ID
		$sql = <<<SQL
			INSERT INTO deletion (`citation_id`, `token_id_start`, `token_id_end`) 
				VALUES(:citation_id, "0", "0");
SQL;
		$this->_db->exec($sql, array(":citation_id" => $this->_citation->getId()));
		$id = $this->_db->getLastInsertId();
		$this->_id = $id;
	}

	/**
	 * Deletes deletion from DB : NB ensure emendation is removed from array in citation object!
	 */
	public static function delete($id, $db) {
		$sql = <<<SQL
			DELETE FROM deletion 
				WHERE id = :id
SQL;
		$db->exec($sql, array(":id" => $id));
	}

	//GETTERS

	public function save() {
		$sql = <<<SQL
			UPDATE deletion 
				SET `token_id_start` = :tokenIdStart, `token_id_end` = :tokenIdEnd
				WHERE id = :id
SQL;
		$this->_db->exec($sql, array(":tokenIdStart" => $this->getTokenIdStart(), ":tokenIdEnd" => $this->getTokenIdEnd(),
			 ":id" => $this->getId()));
	}

	public function getTokenIdStart() {
		return $this->_tokenIdStart;
	}

	public function getTokenIdEnd() {
		return $this->_tokenIdEnd;
	}

	public function getLastUpdated() {
		return $this->_lastUpdated;
	}

	public function getCitation() {
		if (empty($this->_citation)) {
			$sql = <<<SQL
				SELECT citation_id FROM deletion WHERE id = :id
SQL;
			$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
			$this->_citation = new citation($this->_db, $result[0]["citation_id"]);
		}
		return $this->_citation;
	}

	//SETTERS
	public function setTokenIdStart($tokenId) {
		$this->_tokenIdStart = $tokenId;
	}

	public function setTokenIdEnd($tokenId) {
		$this->_tokenIdEnd = $tokenId;
	}
}