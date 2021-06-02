<?php


namespace controllers;

use models, views;

class districts
{
	public function run($action) {

		$id = isset($_GET["id"]) ? $_GET["id"] : "0";

		switch ($action) {
			case "browse":
				if ($id == "0") { // list all districts
					$model = new models\districts();
					$view = new views\districts($model);
					$view->show();
				} else { // view particular district
					$model = new models\district($id);
					$view = new views\district($model);
					$view->show();
				}
				break;
		}
	}
}
