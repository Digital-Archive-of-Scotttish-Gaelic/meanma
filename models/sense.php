<?php

namespace models;

class sense
{
	private $_id, $_label, $_definition, $_entryId, $_subsenseOf;
	private $_subsenses;  //an array of sense objects that are subsenses of this sense
	private $_entry;  //an instance of models\entry
	private $_parentSense = null; //an (optional) instance of models\sense
	private $_db; //an instance of models\database


	public function __construct($db, $id = null, $entryId = null) {
		$this->_db = $db;
		if ($id) {
			$this->_id = $id;
			$this->_load();
		} else {
			$this->_entryId = $entryId;
			$this->_entry = entries::getEntryById($entryId, $db);
			$this->_init();
		}
		return $this;
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
		$this->_entry = entries::getEntryById($this->getEntryId(), $this->_db);
		$this->_subsenseOf = $row["subsense_of"];
		$this->_loadSubsenses();
	}

	private function _init() {
		$sql = <<<SQL
			INSERT INTO subsense (`entry_id`) VALUES(:entryId);
SQL;
		$this->_db->exec($sql, array(":entryId" => $this->getEntryId()));
		$id = $this->_db->getLastInsertId();
		$this->_id = $id;
	}

	private function _loadSubsenses() {
		$sql = <<<SQL
			SELECT id FROM subsense WHERE subsense_of = :id
SQL;
		$results = $this->_db->fetch($sql, array(":id" => $this->getId()));
		foreach ($results as $result) {
			$id = $result["id"];
			$this->_subsenses[$id] = new sense($this->_db, $id);   echo "<h1>{$this->getId()} –> {$id}</h1>";
		}
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
			":entryId" => $this->getEntryId(), ":subsenseOf" => $this->getSubsenseOf(), ":id" => $this->getId()));
	}

	// GETTERS

	public function getId() {
		return $this->_id;
	}

	public function getEntryId() {
		return $this->_entryId;
	}

	public function getSubsenses() {
		return $this->_subsenses;
	}

	public function getSubsenseOf() {
		return $this->_subsenseOf;
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

	public function setSubsenseOf($parentId) {
		$this->_subsenseOf = $parentId;
	}
}