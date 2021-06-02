<?php

namespace controllers;
use models, views;

class corpus
{

	public function __construct() {
		$_GET["pp"] = ($_GET["pp"]) ? $_GET["pp"] : 10; // number of results per page
		$_GET["page"] = ($_GET["page"]) ? $_GET["page"] : 1; // results page number
	}

	public function run($action) {

    $id = isset($_GET["id"]) ? $_GET["id"] : "0"; // the root corpus has id = 0

		switch ($action) {
      case "browse":
				$model = new models\corpus_browse($id);
				$view = new views\corpus_browse($model);
				$view->show();
			  break;
			case "search":
				$model = new models\corpus_search($_GET);
				$view = new views\corpus_search($model);
				$view->show();
				break;
			case "edit":
				$model = new models\corpus_browse($id);
				$view = new views\corpus_browse($model);
				$view->show("edit");
				break;
			case "save":
				$model = new models\corpus_browse($id);
				$model->save($_POST);
				$model = new models\corpus_browse($id);
				$view = new views\corpus_browse($model);
				$view->show();
				break;
			case "generate":
        $model = new models\corpus_generate($id);
				$view = new views\corpus_generate($model);
				$view->show();
			  break;
			case "slow_search":
				$model = new models\slow_search($id);
				$view = new views\slow_search($model);
				$view->show($_GET["xpath"]);
				break;
		}
  }

}
