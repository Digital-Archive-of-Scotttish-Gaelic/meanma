<?php

namespace models;

class translation
{
	private $_id, $_content, $_type, $_lastUpdated;
	private $_db; //an instance of models\database
	private $_citation; //an instance of models\citation

	public static $types = array("working");  //the possible values for translation type

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
			SELECT * FROM translation WHERE id = :id
SQL;
		$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$row = $result[0];
		$this->_content = $row["content"];
		$this->_type = $row["type"];
		$this->_lastUpdated = $row["lastUpdated"];
	}

	private function _init() {
		$this->_type = "working";   //default for new translation
		$sql = <<<SQL
			INSERT INTO translation (`type`) VALUES(:type);
SQL;
		$this->_db->exec($sql, array(":type" => $this->getType()));
		$id = $this->_db->getLastInsertId();
		$this->_id = $id;
		$sql = <<<SQL
			INSERT INTO citation_translation (`citation_id`, `translation_id`) VALUES(:cid, :tid);
SQL;
		$this->_db->exec($sql, array(":cid" => $this->_citation->getId(), ":tid" => $this->getId()));
		$this->getCitation()->addTranslation($this);
	}

	public function save() {
		$sql = <<<SQL
			UPDATE translation SET `type` = :type, `content` = :content
				WHERE id = :id
SQL;
		$this->_db->exec($sql, array(":type" => $this->getType(), ":content" => $this->getContent(), ":id" => $this->getId()));
	}

	/**
	 * Deletes translation from DB
	 */
	public static function delete($id, $db) {
		//translations
		$sql = <<<SQL
			DELETE t, ct FROM translation t
				JOIN citation_translation ct ON t.id = ct.translation_id
				WHERE ct.translation_id = :id
SQL;
		$db->exec($sql, array(":id" => $id));
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

	public function getCitation() {
		if (empty($this->_citation)) {
			$sql = <<<SQL
				SELECT citation_id FROM citation_translation WHERE translation_id = :id
SQL;
			$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
			$this->_citation = new citation($this->_db, $result[0]["citation_id"]);
		}
		return $this->_citation;
	}

	//SETTERS
	public function setContent($content) {
		$this->_content = $content;
	}

	public function setType($type) {
		$this->_type = $type;
	}
}