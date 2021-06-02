<?php

namespace controllers;
use models, views;

class slip
{
  private $_db, $_slip;

  public function __construct($slipId) {
	  $this->_slip = new models\slip($_GET["filename"], $_GET["id"], $slipId, $_GET["pos"]);
  }

  public function run($action) {
	  if (empty($action)) {
		  $action = "edit";
	  }
	  switch ($action) {
		  case "edit":
			  $slipView = new views\slip($this->_slip);
			  $slipView->writeEditForm();
			  break;
	  }
  }
}