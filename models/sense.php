<?php

namespace models;

class sense
{
	private $_id, $_label, $_definition, $_entryId, $_subsenseOf, $_sortOrder;
	private $_subsenses = array();  //an array of sense objects that are subsenses of this sense
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
		$this->_sortOrder = $row["sortOrder"];
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
			SELECT id FROM subsense WHERE subsense_of = :id ORDER BY sortOrder ASC
SQL;
		$results = $this->_db->fetch($sql, array(":id" => $this->getId()));
		foreach ($results as $result) {
			$id = $result["id"];
			$this->_subsenses[] = new sense($this->_db, $id);
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
		if (!$this->getSortOrder()) {   //new sense so set the sortOrder
			$sortOrder = $this->getHighestSortOrder() + 100;
			$this->setSortOrder($sortOrder);
		}
		$sql = <<<SQL
			UPDATE subsense SET `label` = :label, `definition` = :definition, `entry_id` = :entryId, 
			  `subsense_of` = :subsenseOf, `sortOrder` = :sortOrder
				WHERE id = :id
SQL;
		$this->_db->exec($sql, array(":label" => $this->getLabel(), ":definition" => $this->getDefinition(),
			":entryId" => $this->getEntryId(), ":subsenseOf" => $this->getSubsenseOf(),
			":sortOrder" => $this->getSortOrder(), ":id" => $this->getId()));
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

	public function getSortOrder() {
		return $this->_sortOrder;
	}

	public function getEntry() {
		return $this->_entry;
	}

	public function getParentSense() {
		return new sense($this->_db, $this->getSubsenseOf());
	}

	public function getHighestSortOrder() {
		$subsenseClause = $this->getSubsenseOf() ? " = :subsenseOf" : " IS :subsenseOf"; //allow for NULL
		$sql = "SELECT MAX(sortOrder) AS sortOrder FROM subsense WHERE entry_id = :entryId AND subsense_of {$subsenseClause}";
		$results = $this->_db->fetch($sql, array(":entryId" => $this->getEntryId(), ":subsenseOf" => $this->getSubsenseOf()));
		return $results[0]["sortOrder"] ? (int)$results[0]["sortOrder"] : 0;
	}

	public function getCitationSlipIds() {
		$slipIds = array();
		$sql = <<<SQL
			SELECT s.auto_id AS slipId FROM slips s
				JOIN slip_citation sc ON s.auto_id = sc.slip_id
				JOIN citation c ON c.id = sc.citation_id
				WHERE subsense_id = :id AND c.type = "sense"
SQL;
		$results = $this->_db->fetch($sql, array(":id" => $this->getId()));
		foreach ($results as $result) {
			$slipIds[] = $result["slipId"];
		}
		return $slipIds;
	}

	/**
	 * Swap sense in the sort order and return swapped sense ID
	 * @param string $direction
	 * @return int $swapId  : the ID of the sense swapped with
	 */
	public function swapSense($direction) {
		if ($direction == "left") {   //promote this sense up the hierarchy
			$parentSense = $this->getParentSense();
			$parentParentSenseId = $parentSense->getSubsenseOf();
			$this->setSubsenseOf($parentParentSenseId);
			$parentSenseIds = $parentSense->getSenseIdsInOrder();
			$lastSenseId = array_pop($parentSenseIds);
			$lastSense = new sense($this->_db, $lastSenseId);
			$sortOrder = $lastSense->getSortOrder();
			$this->setSortOrder($sortOrder+100);
			$this->save();
			return $lastSenseId;    //the last sense ID in the higher level list
		} else if ($direction == "right") { //demote this sense
			$senseIds = $this->getSenseIdsInOrder();
			//get the position of this sense in the array of sense IDs
			$key = array_search($this->getId(), $senseIds);
			//get the prior sense in the list to swap this sense into
			$parentSense = new sense($this->_db, $senseIds[$key-1]);
			$subsenses = $parentSense->getSubsenses();
			$lastSubsense = array_pop($subsenses);
			$newSortOrder = $lastSubsense ? $lastSubsense->getHighestSortOrder() + 100 : 100;
			$this->setSortOrder($newSortOrder);   //set the new sort order
			$this->setSubsenseOf($parentSense->getId());  //set the new parent
			$this->save();
			return $parentSense->getId();  //the swap ID = the last item in the existing subsense list
																														//OR -1 if there is no subsense list yet
		}
		//code for senses moving up or down
		$senseIds = $this->getSenseIdsInOrder();
		$swapId = null;
		while ($id = current($senseIds)) {  //iterate through the senseIds to find the current one
			if ($id == $this->getId()) {
				$key = key($senseIds);
				$swapKey = $direction == "up" ? $key-1 : $key+1;  //previous or next sense depending on direction
				$swapId = $senseIds[$swapKey];
				break;
			}
			next($senseIds);
		}
		$swapSense = new sense($this->_db, $swapId);
		$swapSortOrder = $swapSense->getSortOrder();
		$currentSortOrder = $this->getSortOrder();
		$swapSense->setSortOrder($currentSortOrder);
		$this->setSortOrder($swapSortOrder);
		$swapSense->save();
		$this->save();
		return $swapId;
	}

	public function getSenseIdsInOrder() {
		$senseIds = array();
		$subsenseHtml = $this->getSubsenseOf() ? "= {$this->getSubsenseOf()}" : "IS NULL";
		$sql = <<<SQL
			SELECT id FROM subsense WHERE entry_id = :entryId AND subsense_of {$subsenseHtml}
				ORDER BY sortOrder ASC
SQL;
		$results = $this->_db->fetch($sql, array(":entryId" => $this->getEntryId()));
		foreach ($results as $result) {
			$senseIds[] = $result["id"];
		}
		return $senseIds;
	}

	/**
	 * Checks array of senseIds and returns whether this sense is first and/or last or neither
	 * @return array $position : "first", "last", ""
	 */
	public function getSensePosition() {
		$senseIds = $this->getSenseIdsInOrder();
		$position = array("first" => null, "last" => null);
		$count = count($senseIds);
		$key = array_search($this->getId(), $senseIds);
		if ($key == 0) {
			$position["first"] = 1;
		}
		if ($key == $count-1) {
			$position["last"] = 1;
		}
		$position["key"] = $key;
		return $position;
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

	public function setSortOrder($sortOrder) {
		$this->_sortOrder = $sortOrder;
	}
}