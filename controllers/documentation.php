<?php


namespace controllers;
use models, views;

class documentation
{
	private $_model;  //an instance of models\Documentation

	public function __construct() {
		$this->_model = new models\documentation();
	}

	public function run($action) {
		if (empty($action)) {
			$action = "view";
		}
		switch ($action) {
			case "view":
				$html = $this->_model->getManualHtml();
				$view = new views\documentation();
				$view->show($html);
		}
	}
}