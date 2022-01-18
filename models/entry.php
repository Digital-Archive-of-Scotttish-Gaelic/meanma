<?php


namespace models;


class entry
{
	private $_id, $_groupId, $_headword, $_wordclass, $_notes, $_updated;
	private $_senses = array();   //array of sense objects
	private $_senseSlipIds = array();
	private $_slipSenses = array();
	private $_individualSenses = array();

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

	public function addSense($sense, $slipId) {
		$this->_senses[$slipId][] = $sense;
		$this->_individualSenses[$sense->getId()][] = $slipId;
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

	public function getWordforms($db) {
		$wordforms = array();
		//get the corpus_slip wordforms
		$sql = <<<SQL
			SELECT l.wordform AS wordform, auto_id AS slipId
				FROM lemmas l 
				JOIN slips s ON s.id = l.id AND s.filename = l.filename
				JOIN entry e ON e.id = s.entry_id
				WHERE e.id = :entryId
				ORDER BY date_of_lang
SQL;
		$results = $db->fetch($sql, array(":entryId"=>$this->getId()));

		//get the paper_slip wordforms
		$sql = <<<SQL
			SELECT wordform, auto_id AS slipId
				FROM slips 
				WHERE wordform IS NOT NULL AND entry_id = :entryId
SQL;
		$results = array_merge($results, $db->fetch($sql, array(":entryId"=>$this->getId())));
		foreach ($results as $row) {
			$wordform = mb_strtolower($row["wordform"], "UTF-8");

			// 		$entryForm = new entry_form($wordform);

			$slipId = $row["slipId"];
			$slipMorphResults = collection::getSlipMorphBySlipId($slipId, $db);

			//	  $entryForm->addMorphFeature();

			$morphString = implode('|', $slipMorphResults);
			$wordforms[$wordform][$morphString][] = $slipId;
		}
		foreach ($wordforms as $wordform => $morphString) {
			ksort($wordforms[$wordform], SORT_STRING);
		}
		return $wordforms;
	}

	public function getSenses($db) {
		if (empty($this->_senses)) {
			entries::addSenseIdsForEntry($this, $db);
		}
		return $this->_senses;
	}

	public function getIndividualSenses() {
		return $this->_individualSenses;
	}

	public function getSenseSlipIds($slipId) {
		return $this->_senseSlipIds[$slipId];
	}

	/**
	 * Groups the senses together
	 * Adds the IDs of the grouped slips into _senseSlipIds for parsing in citations.
	 */
	public function getUniqueSenseIds($db) {
		foreach ($this->getSenses($db) as $slipId => $senseGroup) {
			$senseIds = array();
			foreach ($senseGroup as $sense) {
				array_push($senseIds, $sense->getId());
			}
			$this->_slipSenses[$slipId] = $senseIds;
			sort($this->_slipSenses[$slipId]);
			$this->_slipSenses[$slipId] = implode('|', $this->_slipSenses[$slipId]);
		}
		$uniqueIds = array();
		foreach ($this->_slipSenses as $slipId => $senseIds) {
			if (in_array($senseIds, $uniqueIds)) {
				$id = array_search($senseIds, $uniqueIds);
				array_push($this->_senseSlipIds[$id], $slipId);
			} else {
				$this->_senseSlipIds[$slipId] = array($slipId);
			}
			$uniqueIds[$slipId] = $senseIds;
		}
		return array_unique($uniqueIds);
	}
}