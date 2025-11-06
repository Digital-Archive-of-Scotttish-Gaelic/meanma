<?php

namespace controllers;
use models, views;

class corpus
{
    private $_db;

    public function __construct()
    {
        $_GET["pp"] = ($_GET["pp"]) ? $_GET["pp"] : 10; // number of results per page
        $_GET["page"] = ($_GET["page"]) ? $_GET["page"] : 1; // results page number
        if (!$this->_db) {
            $this->_db = new models\database();
        }
    }

    public function run($action)
    {

        $id = isset($_GET["id"]) ? $_GET["id"] : "0"; // the root corpus has id = 0
        // A temporary hack to restrict browsing for Manuscripts workspace to only MSS texts
        if ($_SESSION["groupId"] == 4 && $id == "0") {
            $id = "804";
        }

        switch ($action) {
            case "browse":
                $model = new models\corpus_browse($id, $this->_db);
                $view = new views\corpus_browse($model);
                $view->show();
                break;
            case "search":
                $model = new models\corpus_search($_GET, true, $this->_db);
                $view = new views\corpus_search($model, $this->_db);
                $view->show();
                break;
            case "xsearch":
                $view = new views\xsearch();
                if (isset($_GET["q"])) {    //query, so show the search results
           //         $view->showSearchResults($_GET);
                    $params["q"] = htmlspecialchars($_GET["q"]);
                    $params["mode"] = htmlspecialchars($_GET["mode"]);
                    $params["text"] = $this->_assembleTextList($_GET);

                    models\collection::writeSlipDiv();

                    require_once 'views/xsearch-results.php';
                } else {                    //no query, so show the search form
                    $minMaxDates = models\corpus_search::getMinMaxDates();
                    $districts = models\districts::getAllDistrictsInfo();
                    $distinctPOS = models\partofspeech::getAllLabels();
            //        $view->showSearchForm();
                    require_once 'views/xsearch-form.php';
                }
                break;
            case "edit":
                $model = new models\corpus_browse($id, $this->_db);
                $view = new views\corpus_browse($model);
                $view->show("edit");
                break;
            case "save":
                $model = new models\corpus_browse($id, $this->_db);
                $model->save($_POST);
                $model = new models\corpus_browse($id, $this->_db);
                $view = new views\corpus_browse($model, $this->_db);
                $view->show();
                break;
            case "generate":
                $model = new models\corpus_generate($id, $this->_db);
                $view = new views\corpus_generate($model);
                $view->show();
                break;
            case "slow_search":
                $model = new models\slow_search($id, $this->_db);
                $view = new views\slow_search($model);
                $view->show($_GET["xpath"]);
                break;
        }
    }

    private function _assembleTextList($q) {
        if ($q["allDistricts"] == "") {      //limit search by geographical origins (district)
            $districts = $q["district"];    //an array of integers

        }
    }
}
