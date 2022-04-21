<?php

namespace models;

class emendation
{
	public static $types = array("working");
	private $_id, $_type, $_tokenId, $_position, $_content, $_lastUpdated;
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
			SELECT * FROM emendation WHERE id = :id
SQL;
		$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$row = $result[0];
		$this->_type = $row["type"];
		$this->_tokenId = $row["token_id"];
		$this->_position = $row["position"];
		$this->_content = $row["content"];
		$this->_lastUpdated = $row["lastUpdated"];
	}

	public function getId() {
		return $this->_id;
	}

	private function _init() {
		//create a holding entry in the database to get the ID
		$sql = <<<SQL
			INSERT INTO emendation (`citation_id`, `token_id`, `type`) 
				VALUES(:citation_id, "0", "temp");
SQL;
		$this->_db->exec($sql, array(":citation_id" => $this->_citation->getId()));
		$id = $this->_db->getLastInsertId();
		$this->_id = $id;
	}

	/**
	 * Deletes emendation from DB : NB ensure emendation is removed from array in citation object!
	 */
	public static function delete($id, $db) {
		$sql = <<<SQL
			DELETE FROM emendation 
				WHERE id = :id
SQL;
		$db->exec($sql, array(":id" => $id));
	}

	//GETTERS

	public function save() {
		$sql = <<<SQL
			UPDATE emendation 
				SET `type` = :type, `token_id` = :tokenId, `position` = :position, `content` = :content
				WHERE id = :id
SQL;
		$this->_db->exec($sql, array(":type" => $this->getType(), ":tokenId" => $this->getTokenId(),
			":position" => $this->getPosition(), ":content" => $this->getContent(), ":id" => $this->getId()));
	}

	public function getType() {
		return $this->_type;
	}

	public function getContent() {
		return $this->_content;
	}

	public function getTokenId() {
		return $this->_tokenId;
	}

	public function getPosition() {
		return $this->_position;
	}

	public function getLastUpdated() {
		return $this->_lastUpdated;
	}

	public function getCitation() {
		if (empty($this->_citation)) {
			$sql = <<<SQL
				SELECT citation_id FROM emendation WHERE id = :id
SQL;
			$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
			$this->_citation = new citation($this->_db, $result[0]["citation_id"]);
		}
		return $this->_citation;
	}

	//SETTERS
	public function setType($type) {
		$this->_type = $type;
	}

	public function setTokenId($tokenId) {
		$this->_tokenId = $tokenId;
	}

	public function setPosition($position) {
		$this->_position = $position;
	}

	public function setContent($content) {
		$this->_content = $content;
	}

}