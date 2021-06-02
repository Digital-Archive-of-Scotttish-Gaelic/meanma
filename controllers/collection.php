<?php

namespace controllers;
use views, models;

class collection
{
  public function run($action) {

  	$id = isset($_GET["id"]) ? $_GET["id"] : "0";

    switch ($action) {
	    case "browse":
		    $view = new views\collection();
		    $view->show();
		    break;
	    case "edit":
		    $slip = new models\slip($_GET["filename"], $_GET["wid"], $id, $_GET["pos"]);
		    $view = new views\slip($slip);
		    $view->show("edit");
	    	break;
	    case "add":
	    	$slip = new models\slip($_GET["filename"], $_GET["wid"], null, $_GET["pos"]);
	    	$view = new views\slip($slip);
	    	$view->show("edit");
	    	break;
    }
  }
}