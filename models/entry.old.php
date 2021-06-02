<?php

namespace models;

class entry
{
	private $_lemma, $_wordclass;
	private $_slipMorphStrings = array();
	private $_individualSenses = array();
	private $_forms = array();
	private $_formSlipData = array();
	private $_senses = array();   //array of sense objects
	private $_senseSlipData = array();
	private $_slipMorphData = array();
	private $_formSlipIds = array();
	private $_senseSlipIds = array();
	private $_slipSenses = array();

	public function __construct($lemma, $wordclass) {
		$this->_lemma = $lemma;
		$this->_wordclass = $wordclass;
	}

	//Getters

	public function getLemma() {
		return $this->_lemma;
	}

	public function getWordclass() {
		return $this->_wordclass;
	}

	public function getForms() {
		return $this->_forms;
	}

	public function getFormSlipData($form, $slipId) {
		return $this->_formSlipData[$form][$slipId];
	}

	public function getSenses() {
		return $this->_senses;
	}

	public function getSenseSlipData($sense) {
		return $this->_senseSlipData[$sense];
	}

	public function getSlipMorphData($form) {
		return $this->_slipMorphData[$form];
	}

	public function getSlipMorphString($form, $slipId) {
		return $this->_slipMorphStrings[$form][$slipId];
	}

	public function getSlipMorphValues($form) {
		$values = array();
		foreach($this->getSlipMorphData($form) as $value) {
			$values[] = $value;
		}
		return $values;
	}

	public function getFormSlipIds($slipId) {
		return $this->_formSlipIds[$slipId];
	}

	public function getSenseSlipIds($slipId) {
		return $this->_senseSlipIds[$slipId];
	}

	/**
	 * Groups the forms by morphological info
	 * Adds the IDs of the grouped slips into _formSlipIds for parsing in citations
	 * @return array of strings with wordform and morph info delimited by '|'
	 */
	public function getUniqueForms() {
		$forms = array();
		foreach ($this->getForms() as $slipId => $form) {
			$morphString = $form . "|" . $this->getSlipMorphString($form, $slipId);
			if (in_array($morphString, $forms)) {
				$id = array_search($morphString, $forms);
				array_push($this->_formSlipIds[$id], $slipId);
			} else {
				$this->_formSlipIds[$slipId] = array($slipId);
			}
			$forms[$slipId] = $form . "|" . $this->getSlipMorphString($form, $slipId);
		}
		return array_unique($forms);
	}

	/**
	 * Groups the senses together
	 * Adds the IDs of the grouped slips into _senseSlipIds for parsing in citations
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

	public function getIndividualSenses() {
		return $this->_individualSenses;
	}

	//Setters

	public function setForms($forms) {
		$this->_forms = $forms;
	}

	public function addForm($form, $slipId) {
		$this->_forms[$slipId] = $form;
	}

	public function addFormSlipData($form, $slipId, $data) {
		$this->_formSlipData[$form][$slipId] = $data;
	}

	public function addSense($sense, $slipId) {
		$this->_senses[$slipId][] = $sense;
		$this->_individualSenses[$sense->getId()][] = $slipId;
	}

	public function setSlipMorphData($form, $data) {
		$this->_slipMorphData[$form] = $data;
	}

	public function addSlipMorphString($form, $slipId, $string) {
		$this->_slipMorphStrings[$form][$slipId] = $string;
	}
}