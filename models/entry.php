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

	public function getWordforms() {
		$wordforms = entries::getWordformsForEntry($this->getId());
		return $wordforms;
	}

	public function getSenses() {
		if (empty($this->_senses)) {
			entries::addSenseIdsForEntry($this);
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
	public function getUniqueSenseIds() {
		foreach ($this->getSenses() as $slipId => $senseGroup) {
			foreach ($senseGroup as $sense) {
				if (!isset($this->_slipSenses[$slipId])) {
					$this->_slipSenses[$slipId] .=  $sense->getId();
				} else {
					$this->_slipSenses[$slipId] .= '|' . $sense->getId();
				}
			}
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