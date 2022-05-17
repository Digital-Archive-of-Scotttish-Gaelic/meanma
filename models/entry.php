<?php


namespace models;


class entry
{
	private $_id, $_groupId, $_headword, $_wordclass, $_notes, $_updated;
	private $_piles = array();   //array of pile objects
	private $_pileSlipIds = array();
	private $_slipPiles = array();
	private $_individualPiles = array();
	private $_senses = array(); //array of sense objects
	private $_etymology;
	private $_subclass;
	private $_subclasses = array(
		"noun" => array("masculine", "feminine", "variable-gender", "unclear-gender"));

	public function __construct($id) {
		$this->_id = $id;
	}

	//SETTERS

	public function setGroupId($groupId) {
		$this->_groupId = $groupId;
	}

	public function setHeadword($headword) {
		$this->_headword = $headword;
	}

	public function setWordclass($wordclass) {
		$this->_wordclass = $wordclass;
	}

	public function setNotes($notes) {
		$this->_notes = $notes;
	}

	public function setUpdated($timestamp) {
		$this->_updated = $timestamp;
	}

	public function setSubclass($option) {
		$this->_subclass = $option;
	}

	public function setEtymology($text) {
		$this->_etymology = $text;
	}

	public function addPile($pile, $slipId) {
		$this->_piles[$slipId][] = $pile;
		$this->_individualPiles[$pile->getId()][] = $slipId;
	}

	// GETTERS

	public function getId() {
		return $this->_id;
	}

	public function getGroupId() {
		return $this->_groupId;
	}

	public function getHeadword() {
		return $this->_headword;
	}

	public function getWordclass() {
		return $this->_wordclass;
	}

	public function getNotes() {
		return $this->_notes;
	}

	public function getEtymology() {
		return $this->_etymology;
	}

	public function getUpdated() {
		return $this->_updated;
	}

	public function getSlipCount($db) {
		$sql = <<<SQL
				SELECT COUNT(*) as c FROM `slips` WHERE entry_id = :id
SQL;
		$results = $db->fetch($sql, array(":id"=>$this->getId()));
		return $results[0]["c"];
	}

	public function getSlipIds($db) {
		$sql = <<<SQL
			SELECT auto_id FROM slips WHERE entry_id = :id
SQL;
		$results = $db->fetch($sql, array(":id" => $this->getId()));
		$slipIds = array();
		foreach ($results as $row) {
			$slipIds[] = $row["auto_id"];
		}
		return $slipIds;
	}

	public function getWordforms($db) {
		$wordforms = array();
		$sql = <<<SQL
			SELECT wordform, auto_id AS slipId
				FROM slips
				JOIN text t ON text_id = t.id
				WHERE entry_id = :entryId
				ORDER by date ASC
SQL;
		$results = $db->fetch($sql, array(":entryId"=>$this->getId()));
		foreach ($results as $row) {
			$wordform = mb_strtolower($row["wordform"], "UTF-8");
			$slipId = $row["slipId"];
			$slipMorphResults = collection::getSlipMorphBySlipId($slipId, $db);
			$morphString = implode('|', $slipMorphResults);
			$wordforms[$wordform][$morphString][] = $slipId;
		}
		foreach ($wordforms as $wordform => $morphString) {
			ksort($wordforms[$wordform], SORT_STRING);
		}
		return $wordforms;
	}

	public function getPiles($db) {
		if (empty($this->_piles)) {
			entries::addPileIdsForEntry($this, $db);
		}
		return $this->_piles;
	}

	public function getIndividualPiles() {
		return $this->_individualPiles;
	}

	public function getPileSlipIds($slipId) {
		return $this->_pileSlipIds[$slipId];
	}

	/**
	 * Groups the piles together
	 * Adds the IDs of the grouped slips into _pileSlipIds for parsing in citations.
	 */
	public function getUniquePileIds($db) {
		foreach ($this->getPiles($db) as $slipId => $pileGroup) {
			$pileIds = array();
			foreach ($pileGroup as $pile) {
				array_push($pileIds, $pile->getId());
			}
			$this->_slipPiles[$slipId] = $pileIds;
			sort($this->_slipPiles[$slipId]);
			$this->_slipPiles[$slipId] = implode('|', $this->_slipPiles[$slipId]);
		}
		$uniqueIds = array();
		foreach ($this->_slipPiles as $slipId => $pileIds) {
			if (in_array($pileIds, $uniqueIds)) {
				$id = array_search($pileIds, $uniqueIds);
				array_push($this->_pileSlipIds[$id], $slipId);
			} else {
				$this->_pileSlipIds[$slipId] = array($slipId);
			}
			$uniqueIds[$slipId] = $pileIds;
		}
		return array_unique($uniqueIds);
	}


	public function getTopLevelSenses($db) {
		if (!empty($this->_senses)) {
			return $this->_senses;
		}
		$sql = "SELECT id FROM subsense WHERE subsense_of IS NULL AND entry_id = :entryId";
		$results = $db->fetch($sql, array(":entryId" => $this->getId()));
		foreach ($results as $result) {
			$this->_senses[] = new sense($db, $result["id"]);
		}
		return $this->_senses;
	}

	/**
	 * Returns all possible subclass options for this entry (based on wordclass)
	 * @return array: of strings
	 */
	public function getSubclasses() {
		return $this->_subclasses[$this->getWordclass()]; //only subclasses for this wordclass
	}

	/**
	 * Returns the selected subclass for this entry from the array of subclasses
	 * @return string
	 */
	public function getSubclass() {
		return $this->_subclass;
	}

	// OTHER METHODS

	public function saveEntry($db) {
		$sql = <<<SQL
			UPDATE entry SET headword = :headword, wordclass = :wordclass, subclass = :subclass, notes = :notes,
			  etymology = :etymology
				WHERE id = :id
SQL;
		$db->exec($sql, array(":id"=>$this->getId(), ":headword"=>$this->getHeadword(), ":wordclass"=>$this->getWordclass(),
			":subclass"=>$this->getSubclass(), ":notes"=>$this->getNotes(), ":etymology"=>$this->getEtymology()));
	}
}