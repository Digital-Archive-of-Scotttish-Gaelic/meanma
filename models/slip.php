<?php

namespace models;

class slip
{
	const SCOPE_DEFAULT = 80;

	private $_id; //the slip ID (called 'auto_id' in the DB)
	protected $_db; //an instance of models\database
	protected $_pos;

	public function __construct($id = null) {
		$this->_id = $id;
	}

	//GETTERS

	public function getId() {
		return $this->_id;
	}

	public function getPOS() {
		return $this->_pos;
	}

	public function getScopeDefault() {
		return self::SCOPE_DEFAULT;
	}

	//SETTERS

	protected function setId($id) {
		$this->_id = $id;
	}

	protected function setPOS($pos) {
		$this->_pos = $pos;
	}
}