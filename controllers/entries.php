<?php

namespace controllers;
use models, views;

class entries
{
  private $_view;

  public function __construct() {
	  $this->_view = new views\entries();
  }

  public function run($action) {
	  if (empty($action)) {
		  $action = "browse";
	  }
	  switch ($action) {
		  case "browse":
			  $entryIds = models\entries::getActiveEntryIds();
			  $this->_view->writeBrowseTable($entryIds);
			  break;
		  case "view":
			  $entry = models\entries::getEntryById($_GET["id"]);
			  $this->_view->writeEntry($entry);
			  break;
	  }
  }
}