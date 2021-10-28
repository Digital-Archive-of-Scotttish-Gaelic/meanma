<?php

namespace models;

class slip
{
	private $_id; //the slip ID (called 'auto_id' in the DB)

	public function __construct($id = null) {
		$this->_id = $id;
	}

	//GETTERS

	public function getId() {
		return $this->_id;
	}

	//SETTERS

	protected function setId($id) {
		$this->_id = $id;
	}
}