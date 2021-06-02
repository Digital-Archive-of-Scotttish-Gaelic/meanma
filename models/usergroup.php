<?php

namespace models;

class usergroup
{
	private $_id, $_name, $_theme;

	public function __construct($id, $name, $theme, $lastUsed) {
		$this->setId($id);
		$this->setName($name);
		$this->setTheme($theme);
	}

	//Getters

	public function getId() {
		return $this->_id;
	}

	public function getName() {
		return $this->_name;
	}

	public function getTheme() {
		return $this->_theme;
	}

	//Setters

	public function setId($id) {
		$this->_id = $id;
	}

	public function setName($name) {
		$this->_name = $name;
	}

	public function setTheme($theme) {
		$this->_theme = $theme;
	}

}