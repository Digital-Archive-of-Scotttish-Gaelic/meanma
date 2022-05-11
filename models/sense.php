<?php

namespace models;

class sense
{
	private $_id, $_label, $_definition, $_entryId, $_subsenseOf;
	private $_entry;  //an instance of models\entry
	private $_parentSense = null; //an (optional) instance of models\sense
	private $_db; //an instance of models\database


	public function __construct($db, $id = null, $entryId = null) {
		$this->_db = $db;
		if ($id) {
			$this->_id = $id;
			$this->_load();
		} else {
			$this->_entry = entries::getEntryById($entryId, $db);
			$this->_init();
		}
	}

	private function _load() {
		$sql = <<<SQL
			SELECT * FROM subsense WHERE id = :id
SQL;
		$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$row = $result[0];
		$this->_label = $row["label"];
		$this->_definition = $row["definition"];
		$this->_entryId = $row["entry_id"];
		$this->_entry = entries::getEntryById($this->getEntryId(). $this->_db);
		$this->_subsenseOf = $row["subsense_of"];
		if ($this->getSubsenseOf()) {
			$this->_parentSense = new sense($this->_db, $this->getSubsenseOf());
		}
	}

	public function getId() {
		return $this->_id;
	}

	public function getEntryId() {
		return $this->_entryId;
	}

	public function getSubsenseOf() {
		return $this->_subsenseOf;
	}

	//GETTERS

	private function _init() {
		$sql = <<<SQL
			INSERT INTO subsense (`entry_id`) VALUES(:entryId);
SQL;
		$this->_db->exec($sql, array(":entryId" => $this->getEntryId()));
		$id = $this->_db->getLastInsertId();
		$this->_id = $id;
	}

	/**
	 * Deletes sense from DB
	 */
	public static function delete($id, $db) {
		$sql = <<<SQL
			DELETE FROM subsense t
=				WHERE id = :id
SQL;
		$db->exec($sql, array(":id" => $id));
	}

	public function save() {
		$sql = <<<SQL
			UPDATE subsense SET `label` = :label, `definition` = :definition, `entry_id` = :entryId, `subsense_of` = :subsenseOf
				WHERE id = :id
SQL;
		$this->_db->exec($sql, array(":label" => $this->getLabel(), ":definition" => $this->getDefinition(),
			":entryId" => $this->getEntryId(), ":subsenseOf" => $this->getSubsenseOf()));
	}

	public function getLabel() {
		return $this->_label;
	}

	public function getDefinition() {
		return $this->_definition;
	}

	public function getEntry() {
		return $this->_entry;
	}

	public function getParentSense() {
		return $this->_parentSense;
	}

	//SETTERS
	public function setLabel($text) {
		$this->_label = $text;
	}

	public function setDefinition($text) {
		$this->_definition = $text;
	}
}