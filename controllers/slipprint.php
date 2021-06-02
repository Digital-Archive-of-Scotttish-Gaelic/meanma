<?php

namespace controllers;
use models;

//require_once "includes/include.php";

class slipprint
{
	private $_model;

	public function __construct() {
		$this->_model = new models\slipprint();
	}

	public function run($action) {
		if (empty($action)) {
			$action = "writePDF";
		}
		switch ($action) {
			case "writePDF":
				$slipIds = array_keys($_SESSION["printSlips"]);
				//reset the array
				$_SESSION["printSlips"] = array() ;
				$this->_model->writePDF($slipIds);
				break;
		}
	}

}