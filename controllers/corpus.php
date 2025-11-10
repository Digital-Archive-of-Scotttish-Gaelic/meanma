<?php

namespace controllers;
use models, views;
use models\DynamicModel;

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
                if (isset($_GET["q"])) {    //query, so show the search results
                    $params["q"] = htmlspecialchars($_GET["q"]);
                    $params["mode"] = htmlspecialchars($_GET["mode"]);
                    $texts = "";
                    if ($_GET["selectedDates"]) {
                        $dates = explode("-", $_GET["selectedDates"]);

                        $pdo = $this->_db->getDatabaseHandle();

                        $sth = $pdo->prepare('SELECT id FROM text WHERE date >= ? AND date <= ?');
                        $sth->execute(array($dates[0], $dates[1]));
                        $results = $sth->fetchAll(\PDO::FETCH_ASSOC);
                        $ids = array_column($results, 'id');
                        $texts = implode(',', array_map(fn($id) => '_' . $id, $ids));
                    }
                    $params["text"] = $texts;
                    models\collection::writeSlipDiv();
                    require_once 'views/xsearch-results.php';
                } else {                    //no query, so show the search form
                    if ($_GET["id"]) {  //not whole corpus, so don't show dates or districts options
                        $minMaxDates = $districts = array();
                    } else {    //only add dates and districts if searching whole corpus
                        $minMaxDates = models\corpus_search::getMinMaxDates();
                        $districts = models\districts::getAllDistrictsInfo();
                    }
                    $distinctPOS = models\partofspeech::getAllLabels();
                    $this->writeSubHeading();
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

        //$textModel = new models\text();
        // $texts = DynamicModel::where($this->_db->getDatabaseHandle(), 'corpus_text', ['date of edition > 1990']);
    }

    protected function writeSubHeading() {
        $user = models\users::getUser($_SESSION["user"]);
        if ($_GET["id"]) {    //if this is a subtext don't write the date range block
            $dateRangeBlock = $districtBlock = "";
        }
        echo <<<HTML
	    	<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">
HTML;
        if ($_GET["id"]=="0") {
            echo <<<HTML
			  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=browse&id=0">view corpus</a></li>
			  <li class="nav-item"><div class="nav-link active">searching corpus</div></li>
HTML;
            if ($user->getSuperuser()) {
                echo <<<HTML
				  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=edit&id=0">add text</a></li>
HTML;
            }
            echo <<<HTML
				<li class="nav-item"><a class="nav-link" href="?m=corpus&a=generate&id=0">corpus wordlist</a></li>
HTML;
        } else {
            echo <<<HTML
			  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=browse&id={$_GET["id"]}">view text #{$_GET["id"]}</a></li>
			  <li class="nav-item"><div class="nav-link active">searching text #{$_GET["id"]}</div></li>
HTML;
            if ($user->getSuperuser()) {
                echo <<<HTML
			      <li class="nav-item"><a class="nav-link" href="?m=corpus&a=edit&id={$_GET["id"]}">edit text #{$_GET["id"]}</a></li>
HTML;
            }
            echo <<<HTML
				  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=generate&id={$_GET["id"]}">text #{$_GET["id"]} wordlist</a></li>
HTML;
        }
        echo <<<HTML
		  </ul>
			<hr/>
HTML;
    }
}
