<?php

namespace controllers;
use models, views;

class entries
{
  private $_view, $_db;

  public function __construct() {
  	$this->_db = new models\database();
  }

  public function run($action) {
	  if (empty($action)) {
		  $action = "browse";
	  }
	  switch ($action) {
		  case "browse":
			  $entryIds = models\entries::getActiveEntryIds($this->_db);
			  $this->_view = new views\entries($this->_db);
			  $this->_view->writeBrowseTable($entryIds);
			  break;
		  case "view":
			  $entry = models\entries::getEntryById($_GET["id"], $this->_db);
			  $this->_view = new views\entry($this->_db);
			  $this->_view->writeEntry($entry);
			  break;
	  }
  }
}