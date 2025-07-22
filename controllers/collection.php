<?php

namespace controllers;
use views, models;

class collection
{
	private $_db;

	public function __construct() {
		if (!$this->_db) {
			$this->_db = new models\database();
		}
	}

	public function run($action) {

  	$id = isset($_GET["id"]) ? $_GET["id"] : "0";
  	$type = isset($_GET["type"]) ? $_GET["type"] : "corpus";

    switch ($action) {
	    case "browse":
		    $view = new views\collection();
		    $view->show($action, $type);  //corpus or paper
		    break;
	    case "edit":
		    $slip = ($_GET["filename"])
		      ? new models\corpus_slip($_GET["filename"], $_GET["wid"], $id, $_GET["pos"], $this->_db)
			    : new models\paper_slip($id, $_GET["entryId"], $_GET["wordform"], $this->_db);
		    $view = new views\slip($slip);
		    $view->show("edit");
	    	break;
	    case "add":
	    	$slip = new models\corpus_slip($_GET["filename"], $_GET["wid"], null, $_GET["pos"], $this->_db, $_GET["wordform"], $_GET["headword"]);
	    	$view = new views\slip($slip);
	    	$view->show("edit");
	    	break;
    }
  }
}