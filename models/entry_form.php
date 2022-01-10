<?php

namespace models;

class entry_form
{
	private $_word;
	private $_morphology = array();

	public function __construct($word) {
		$this->_word = $word;
	}

	public function addMorphFeature($relation, $value) {
		$this->_morphology[$relation] = $value;
	}

	public function getMorphology() {
		return $this->_morphology;
	}
}