<?php

namespace views;
use models;

class corpus_search extends search
{
	private $_model;  //an instance of models\corpus_search

	public function __construct($model) {
		$this->_model = $model;
	}

	public function show() {
		if ($this->_model->getTerm()) {
			echo <<<HTML
				<div class="spinner"><img src="https://dasg.ac.uk/images/loading.gif"></div>
				<div class="resultsContainer">
HTML;

			$this->_writeSearchResults();   //there is a search term so run the search
			echo <<<HTML
				</div>  <!-- //end results container -->
HTML;
		} else {
			$this->_writeSearchForm();  //no search term so show the form
		}
	}

	private function _writeSearchForm() {
		$user = models\users::getUser($_SESSION["user"]);
		$minMaxDates = models\corpus_search::getMinMaxDates(); // needs a rethink for individual texts
		$dateRangeBlock = <<<HTML
			<div class="form-group">
            <p>Restrict by date range:</p>
            <div id="selectedDatesDisplay">{$minMaxDates["min"]}-{$minMaxDates["max"]}</div>
            <input type="hidden" class="form-control col-2" name="selectedDates" id="selectedDates">
            <div id="dateRangeSelector" class="col-6">
                <label id="dateRangeMin">{$minMaxDates["min"]}</label>
                <label id="dateRangeMax">{$minMaxDates["max"]}</label>
            </div>
        </div>
HTML;
		$districtBlock = $this->_getDistrictHtml();
		if ($_GET["id"]) {    //if this is a subtext don't write the date range block
			$dateRangeBlock = $districtBlock = "";
		}
		parent::writeSubHeading();
		echo <<<HTML
			<div class="float-right">
				<small><a href="?m=corpus&a=slow_search&id={$_GET["id"]}">xpath search</a></small>
			</div>
      <form>
        <div class="form-group">
          <div class="input-group">
            <input type="text" name="term"/>
            <div class="input-group-append">
              <input type="hidden" name="m" value="corpus">
              <input type="hidden" name="a" value="search"/>
              <input type="hidden" name="id" value="{$_GET["id"]}">
              <button name="submit" class="btn btn-primary" type="submit">search</button>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="headwordRadio" value="headword" checked>
            <label class="form-check-label" for="headwordRadio">headword</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="wordformRadio" value="wordform">
            <label class="form-check-label" for="wordformRadio">wordform</label>
          </div>
        </div>
        <div class="form-group">
          <a href="#" id="multiWordShow">show multi-word options</a>
          <a href="#" id="multiWordHide">hide multi-word options</a>
				</div>
        <div id="multiWord" style="padding:20px; display: none;">
          <div class="form-group">
            <label for="precedingWord">preceding word</label>
            <input type="text" id="precedingWord" name="pw">
					</div>
					<div class="form-group">
	          <div class="form-check form-check-inline">
	            <input class="form-check-input" type="radio" name="preMode" id="preHeadwordRadio" value="headword" checked>
	            <label class="form-check-label" for="preHeadwordRadio">headword</label>
	          </div>
	          <div class="form-check form-check-inline">
	            <input class="form-check-input" type="radio" name="preMode" id="preWordformRadio" value="wordform">
	            <label class="form-check-label" for="preWordformRadio">wordform</label>
	          </div>
	        </div>
          <div class="form-group">
            <label for="precedingWord">following word</label>
            <input type="text" id="followingWord" name="fw">
					</div>
					<div class="form-group">
	          <div class="form-check form-check-inline">
	            <input class="form-check-input" type="radio" name="postMode" id="postHeadwordRadio" value="headword" checked>
	            <label class="form-check-label" for="postHeadwordRadio">headword</label>
	          </div>
	          <div class="form-check form-check-inline">
	            <input class="form-check-input" type="radio" name="postMode" id="postWordformRadio" value="wordform">
	            <label class="form-check-label" for="postWordformRadio">wordform</label>
	          </div>
	        </div>
				</div>  <!-- //end multiWord -->
        <div id="wordformOptions" class="form-group">
          <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="caseSensitiveRadio" name="case" value="sensitive">
              <label class="form-check-label" for="caseSensitiveRadio">case sensitive</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="accentSensitiveRadio" name="accent" value="sensitive">
            <label class="form-check-label" for="accentSensitiveRadio">accent sensitive</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="lenitionSensitiveRadio" name="lenition" value="sensitive">
            <label class="form-check-label" for="lenitionSensitiveRadio">mutation sensitive</label>
          </div>
        </div>
        <div class="form-group">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="view" id="corpusViewRadio" value="corpus" checked>
            <label class="form-check-label" for="corpusViewRadio">corpus view</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="view" id="dictionaryViewRadio" value="dictionary">
            <label class="form-check-label" for="dictionaryViewRadio">dictionary view</label>
          </div>
        </div>
        <div class="form-group">
          <p>Order results:</p>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="order" id="offDateRadio" value="off" checked>
            <label class="form-check-label" for="offDateRadio">off</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="order" id="ascDateRadio" value="dateAsc">
            <label class="form-check-label" for="ascDateRadio">date ascending</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="order" id="descDateRadio" value="dateDesc">
            <label class="form-check-label" for="ascDateRadio">date descending</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="order" id="randomDateRadio" value="random">
            <label class="form-check-label" for="randomDateRadio">random</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="order" id="precedingWordRadio" value="precedingWord">
            <label class="form-check-label" for="precedingWordRadio">preceding word</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="order" id="precedingWordReverseRadio" value="precedingWordReverse">
            <label class="form-check-label" for="precedingWordReverseRadio">reverse preceding word</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="order" id="followingWordRadio" value="followingWord">
            <label class="form-check-label" for="followingWordRadio">following word</label>
          </div>
        </div>
        {$dateRangeBlock}
        <br>
        {$districtBlock}
        <div class="form-group">
            <p>Restrict by medium:</p>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="medium[]" id="proseMediumCheck" value="prose" checked>
                <label class="form-check-label" for="proseMediumCheck">prose</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="medium[]" id="verseMediumCheck" value="verse" checked>
                <label class="form-check-label" for="verseMediumCheck">verse</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="medium[]" id="otherMediumCheck" value="other" checked>
                <label class="form-check-label" for="otherMediumCheck">other</label>
            </div>
        </div>
        <div class="form-group">
            <p>Restrict by importance:</p>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="level[]" id="level1Check" value="1" checked>
                <label class="form-check-label" for="level1Check"><i class="fas fa-star gold"></i></label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="level[]" id="level2Check" value="2" checked>
                <label class="form-check-label" for="level2Check"><i class="fas fa-star silver"></i></label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="level[]" id="level3Check" value="3" checked>
                <label class="form-check-label" for="level2Check"><i class="fas fa-star bronze"></i></label>
            </div>
        </div>
        <div class="form-group">
            <p>Restrict by part-of-speech:</p>
            {$this->_getSelectPosHtml()}
            <note><em>Select multiple options by using CTRL key (Windows) or Command key (Mac)</em></note>
        </div>
      </form>
HTML;
		$this->_writeSearchJavascript($minMaxDates); // writes JS for year slider (maybe not necessary?)
	}

	protected function _getSelectPosHtml() {
		$distinctPOS = models\partofspeech::getAllLabels();
		$posHtml = "";
		foreach ($distinctPOS as $abbr => $label) {
			$posHtml .= '<option value="' . $abbr . '">' . $abbr . ' (' . $label . ')</option>';
		}
		$posHtml = <<<HTML
        <select class="form-control col-3" multiple name="pos[]">
            <option value="" selected>-- all POS --</option>
            {$posHtml}
        </select>
HTML;
		return $posHtml;
	}

	protected function _getDistrictHtml() {
		$districts = models\districts::getAllDistrictsInfo();
		foreach ($districts as $district) {
			$id = $district["id"];
			$districtsHtml .= <<<HTML
				<div class="form-check form-check-inline">
            <input class="form-check-input district" type="checkbox" name="district[]" id="district{$id}Check" value="{$id}" checked>
            <label class="form-check-label" for="district{$id}Check">
              {$district["name"]}
						</label>
        </div>
HTML;
		}
		$html = <<<HTML
			<div class="form-group">
            <p>Restrict by location:</p>
            <div>
              {$districtsHtml}
            </div>
            <div>
              <a href="#" id="uncheckAllDistricts">uncheck all</a>
              <a href="#" id="checkAllDistricts">check all</a>
						</div>
        </div>
HTML;
		return $html;
	}

	private function _writeSearchResults() {
		$results = $this->_model->getResults();
		$resultTotal = $this->_model->getHits();
		models\collection::writeSlipDiv();
		//Add a back link to originating script
		echo <<<HTML
        <p><a href="index.php?m=corpus&a=search&id={$_GET["id"]}" title="Back to search">&lt; Back to search</a></p>
HTML;

		if ($this->_model->getView() == "dictionary") {
			$this->_writeDictionaryView();
			return;
		}
		$rowNum = $this->_model->getPage() * $this->_model->getPerPage() - $this->_model->getPerPage() + 1;
		echo <<<HTML
        <table class="table">
            <tbody>
HTML;
		if (count($results)) {
			$this->_writeResultsHeader($rowNum, $resultTotal);
			$filename = "";
			foreach ($results as $result) {
				echo <<<HTML
                <tr>
                    <th scope="row">{$rowNum}</th>
HTML;
				$this->_writeSearchResult($result, $rowNum-1);
				echo <<<HTML
                </tr>
HTML;
				$rowNum++;
			}
			echo <<<HTML
            </tbody>
        </table>
				<div class="float-right"><small><a id="autoCreateRecords" href="#">Automatically create all records</a></small></div>
        <ul id="pagination" class="pagination-sm"></ul>
HTML;
			$this->_writeViewSwitch();
		} else {
			echo <<<HTML
                <tr><th>Sorry, there were No results for <em>{$this->_model->getTerm()}</em></th></tr>
HTML;

		}
		$this->_writeResultsJavascript();
	}

	private function _writeResultsHeader($rowNum, $resultTotal) {
		$lastDisplayedRowNum = $rowNum + $this->_model->getPerPage() - 1;
		$lastDisplayedRowNum = ($lastDisplayedRowNum > $resultTotal) ? $resultTotal : $lastDisplayedRowNum;
		$html = <<<HTML
        <p>[Showing results {$rowNum}–{$lastDisplayedRowNum} of {$resultTotal} 
        for {$this->_model->getMode()} <strong>{$this->_model->getTerm()}</strong>
HTML;
		if (!empty($_GET["pos"][0])) {
			$posString = implode(", ", $_GET["pos"]);
			$html .= "({$posString})";
		}
		if (isset($_GET["medium"]) && count($_GET["medium"]) < 3) {
			$html .= " in " . implode(", ", $_GET["medium"]);
		}
		if ($_GET["selectedDates"]) {
			$html .= " {$_GET["selectedDates"]}";
		}
		$html .= "]</p>";
		$html .= <<<HTML
			<small>
				<a href="#" id="basicSwitch" class="resultsSwitch float-right" data-value="basic">basic view</a>
				<a href="#" id="extendedSwitch" class="resultsSwitch float-right" data-value="advanced">extended view</a>
			</small>
HTML;
		echo $html;
	}

	private function _writeViewSwitch() {
		$alternateView = ($this->_model->getView() == "corpus") ? "dictionary" : "corpus";
		$queryString = $alternateView == "corpus"
			? str_replace("view=dictionary", "view=corpus", $_SERVER["QUERY_STRING"])
			: str_replace("view=corpus", "view=dictionary", $_SERVER["QUERY_STRING"]);
		echo <<<HTML
        <div id="viewSwitch">
            <small><a href="index.php?{$queryString}">
                switch to {$alternateView} view
            </a></small>
        </div>
HTML;
	}

	/* print out search result as table row */
	private function _writeSearchResult($result, $index) {
		$context = $result["context"];
		$pos = new models\partofspeech($result["pos"]);

		$shortTitle = mb_strlen($result["title"]) < 30
			? $result["title"]
			: mb_substr($result["title"], 0, 29) . "...";

		$title = <<<HTML
        Headword: {$result["lemma"]}<br>
        POS: {$result["pos"]} ({$pos->getLabel()})<br>
        Date: {$result["date_of_lang"]}<br>
        Title: {$result["title"]}<br>
        Page No: {$result["page"]}<br><br>
        {$result["filename"]}<br>{$result["id"]}
HTML;
		$textNum = stristr($result["filename"], "_", true);
		$slipLinkHtml = models\collection::getSlipLinkHtml($result, $index);
		echo <<<HTML
				<td class="extendedField">{$result["date_of_lang"]}</td>
				<td class="extendedField">#{$textNum} {$shortTitle}</td>
        <td style="text-align: right;">{$context["pre"]["output"]}</td>
        <td style="text-align: center;">
            <a target="_blank" href="?m=corpus&a=browse&id={$result["tid"]}&wid={$result["id"]}"
                    data-toggle="tooltip" data-html="true" title="{$title}">
                {$context["word"]}
            </a>
        </td>
        <td>{$context["post"]["output"]}</td>
        <td> <!-- the slip link -->
            <small>{$slipLinkHtml}</small>
        </td>
HTML;
		return;
	}

	private function _writeDictionaryView() { // added by MM
		$_GET["pp"] = null;   //don't limit the results - fetch them all
		//instantiate a new model to set the per page to null
		$model = new models\corpus_search($_GET);
		$searchResults = $model->getResults();
		echo '<h4>' . $searchResults[0]['lemma'] . '</h4>';
		echo '<h5>' . $this->_model->getHits() .' results</h5>';
		$forms = [];
		foreach ($searchResults as $nextResult) {
			$forms[] = $nextResult['wordform'] . '|' . $nextResult['pos'];
		}
		$forms = array_unique($forms);
		echo <<<HTML
      <table class="table">
        <tbody>
HTML;
		$formNum=0;
		foreach ($forms as $nextForm) {
			$formNum++;
			$array = explode('|',$nextForm);
			echo '<tr><td>' . $array[0] . '</td><td>' . $array[1] . '</td><td>';
			$i=0;
			$locations = array();
			foreach ($searchResults as $nextResult) {
				if ($nextResult['wordform']==$array[0] && $nextResult['pos']==$array[1]) {
					$i++;
					$locations[] = $nextResult['filename'] . ' ' . $nextResult['id'] . ' '
						. $nextResult['date_of_lang'] . ' ' . $nextResult["auto_id"] . ' '
						. str_replace(" ", "\\", $nextResult['title']) . ' ' . $nextResult["page"]
						. ' ' . $nextResult["tid"];
				}
			}
			$locs = implode('|', $locations);
			echo <<<HTML
            <a href="#" id="show-{$formNum}" data-formNum="{$formNum}" data-locs="{$locs}"
                data-pos="{$array[1]}" data-lemma="{$array[0]}" data-action="show"
                 class="loadDictResults">
                <span class="actionToggle">show</span> {$i} result(s)
            </a>
            <div id="results-{$formNum}">
              <img id="loadingImage-{$formNum}" src="https://dasg.ac.uk/images/loading.gif" width="400" style="display: none;">
              <table id="form-{$formNum}"></table>
              <div id="pag-{$formNum}"></div>
            </div>
        </td></tr>
HTML;
		}
		echo <<<HTML
        </tbody>
      </table>
HTML;
		models\collection::writeSlipDiv();
		$this->_writeViewSwitch();
		$this->_writeDictionaryResultsJavascript();
		return;
	}

	public function setHits($num) {
		$this->_hits = $num;
	}

	/**
	 * Writes the Javascript required for the pagination
	 */
	private function _writeResultsJavascript() {
		//write the Javascript
		echo <<<HTML
				<script type="text/javascript" src="js/jquery.simplePagination.js"></script>
        <script>
        $(function() {
            
          $('#autoCreateRecords').on('click', function() {
            let check = confirm('Are you absolutely sure you want to automatically create ca. {$this->_model->getHits()} records? (Previously created records will not be affected.)');
            if (check) {
              let paramString = encodeURI('{$_SERVER["QUERY_STRING"]}');
              $('.resultsContainer').hide();
              $('.spinner').show();
              $.getJSON('ajax.php?action=autoCreateSlips&' + paramString , function() {
              })
              .done(function(data) {
                if (data.success) {
                  console.log("done");
                  location.reload();
                }       
              })
            }
          });
          
		      /**
		      * Basic/advanced results  
					*/
		      if (Cookies.get('resultsPref') == "basic") {
		        setBasicResultsView();
		      } else {
		        setExtendedResultsView();
		      }
		      
		      $('.resultsSwitch').on('click', function() {
		        if ($(this).attr('data-value') == 'basic') {
		          setBasicResultsView();
		        } else {
		          setExtendedResultsView();
		        }
		      });
      
          /*
            Open the add new slip form in a new tab        
           */
             $('.createSlipLink').on('click', function() {
               var url = $(this).attr('data-url');
               var win = window.open(url, '_blank');
               if (win) {
						      //Browser has allowed it to be opened
						      win.focus();
						    } else {
						      //Browser has blocked it
						      alert('Please allow popups for this website');
						    }
             });
          /*
            Date range slider
           */
             $( "#dateRange" ).slider({
                range:true,
                min: 0,
                max: 500,
                values: [ 35, 200 ],
                slide: function( event, ui ) {
                  $( "#selectedDate" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
                }
              });

			     /*
				    Pagination handler
			     */
		          $("#pagination").pagination({
				          currentPage: {$this->_model->getPage()},
		              items: {$this->_model->getHits()},
		              itemsOnPage: {$this->_model->getPerPage()},
		              cssStyle: "light-theme",
		              onPageClick: function(pageNum) {
				            var url = 'index.php?{$_SERVER["QUERY_STRING"]}&page=' + pageNum;
                    window.location.assign(url);
		              }
		          });
		      });
        
          function setBasicResultsView() {
            $('.extendedField').hide();
			      $('#basicSwitch').hide();
			      $('#extendedSwitch').show();
			      Cookies.set('resultsPref', 'basic', { expires: 365 });
			    }
			    
			    function setExtendedResultsView() {
            $('.extendedField').show();
			      $('#extendedSwitch').hide();
			      $('#basicSwitch').show();
			      Cookies.set('resultsPref', 'extended', { expires: 365 });
			    }
	       </script>
HTML;
	}

	/**
	 * Writes the JS required for the search form
	 * @param $params array
	 */
	private function _writeSearchJavascript($params) {
		echo <<<HTML

    <script>
    $(function() {
      $('#multiWordHide').hide();
      $('#multiWordShow').on('click', function () {
        $('#multiWord').show();
        $('#multiWordShow').hide();
        $('#multiWordHide').show();
      });
      $('#multiWordHide').on('click', function () {
        $('#multiWord').hide();
        $('#multiWordHide').hide();
        $('#multiWordShow').show();
      });
      
      $( "#dateRangeSelector" ).slider({
        range:true,
        min: {$params["min"]},
        max: {$params["max"]},
        values: [ {$params["min"]}, {$params["max"]} ],
        slide: function( event, ui ) {
          var output = ui.values[0] + "-" + ui.values[1];
          $("#selectedDates").val(output);
          $('#selectedDatesDisplay').html(output);
        }
      });
      
      $('#uncheckAllDistricts').on('click', function() {
        $('.district').prop('checked', false);
      });
      
      $('#checkAllDistricts').on('click', function() {
        $('.district').prop('checked', true);
      });
      
    });
    </script>
HTML;
	}

	private function _writeDictionaryResultsJavascript() {
		echo <<<HTML
<style>
.paginationjs{line-height:1.6;font-family:Marmelad,"Lucida Grande",Arial,"Hiragino Sans GB",Georgia,sans-serif;font-size:14px;box-sizing:initial}.paginationjs:after{display:table;content:" ";clear:both}.paginationjs .paginationjs-pages{float:left}.paginationjs .paginationjs-pages ul{float:left;margin:0;padding:0}.paginationjs .paginationjs-go-button,.paginationjs .paginationjs-go-input,.paginationjs .paginationjs-nav{float:left;margin-left:10px;font-size:14px}.paginationjs .paginationjs-pages li{float:left;border:1px solid #aaa;border-right:none;list-style:none}.paginationjs .paginationjs-pages li>a{min-width:30px;height:28px;line-height:28px;display:block;background:#fff;font-size:14px;color:#333;text-decoration:none;text-align:center}.paginationjs .paginationjs-pages li>a:hover{background:#eee}.paginationjs .paginationjs-pages li.active{border:none}.paginationjs .paginationjs-pages li.active>a{height:30px;line-height:30px;background:#aaa;color:#fff}.paginationjs .paginationjs-pages li.disabled>a{opacity:.3}.paginationjs .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs .paginationjs-pages li:first-child,.paginationjs .paginationjs-pages li:first-child>a{border-radius:3px 0 0 3px}.paginationjs .paginationjs-pages li:last-child{border-right:1px solid #aaa;border-radius:0 3px 3px 0}.paginationjs .paginationjs-pages li:last-child>a{border-radius:0 3px 3px 0}.paginationjs .paginationjs-go-input>input[type=text]{width:30px;height:28px;background:#fff;border-radius:3px;border:1px solid #aaa;padding:0;font-size:14px;text-align:center;vertical-align:baseline;outline:0;box-shadow:none;box-sizing:initial}.paginationjs .paginationjs-go-button>input[type=button]{min-width:40px;height:30px;line-height:28px;background:#fff;border-radius:3px;border:1px solid #aaa;text-align:center;padding:0 8px;font-size:14px;vertical-align:baseline;outline:0;box-shadow:none;color:#333;cursor:pointer;vertical-align:middle\9}.paginationjs.paginationjs-theme-blue .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-blue .paginationjs-pages li{border-color:#289de9}.paginationjs .paginationjs-go-button>input[type=button]:hover{background-color:#f8f8f8}.paginationjs .paginationjs-nav{height:30px;line-height:30px}.paginationjs .paginationjs-go-button,.paginationjs .paginationjs-go-input{margin-left:5px\9}.paginationjs.paginationjs-small{font-size:12px}.paginationjs.paginationjs-small .paginationjs-pages li>a{min-width:26px;height:24px;line-height:24px;font-size:12px}.paginationjs.paginationjs-small .paginationjs-pages li.active>a{height:26px;line-height:26px}.paginationjs.paginationjs-small .paginationjs-go-input{font-size:12px}.paginationjs.paginationjs-small .paginationjs-go-input>input[type=text]{width:26px;height:24px;font-size:12px}.paginationjs.paginationjs-small .paginationjs-go-button{font-size:12px}.paginationjs.paginationjs-small .paginationjs-go-button>input[type=button]{min-width:30px;height:26px;line-height:24px;padding:0 6px;font-size:12px}.paginationjs.paginationjs-small .paginationjs-nav{height:26px;line-height:26px;font-size:12px}.paginationjs.paginationjs-big{font-size:16px}.paginationjs.paginationjs-big .paginationjs-pages li>a{min-width:36px;height:34px;line-height:34px;font-size:16px}.paginationjs.paginationjs-big .paginationjs-pages li.active>a{height:36px;line-height:36px}.paginationjs.paginationjs-big .paginationjs-go-input{font-size:16px}.paginationjs.paginationjs-big .paginationjs-go-input>input[type=text]{width:36px;height:34px;font-size:16px}.paginationjs.paginationjs-big .paginationjs-go-button{font-size:16px}.paginationjs.paginationjs-big .paginationjs-go-button>input[type=button]{min-width:50px;height:36px;line-height:34px;padding:0 12px;font-size:16px}.paginationjs.paginationjs-big .paginationjs-nav{height:36px;line-height:36px;font-size:16px}.paginationjs.paginationjs-theme-blue .paginationjs-pages li>a{color:#289de9}.paginationjs.paginationjs-theme-blue .paginationjs-pages li>a:hover{background:#e9f4fc}.paginationjs.paginationjs-theme-blue .paginationjs-pages li.active>a{background:#289de9;color:#fff}.paginationjs.paginationjs-theme-blue .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-blue .paginationjs-go-button>input[type=button]{background:#289de9;border-color:#289de9;color:#fff}.paginationjs.paginationjs-theme-green .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-green .paginationjs-pages li{border-color:#449d44}.paginationjs.paginationjs-theme-blue .paginationjs-go-button>input[type=button]:hover{background-color:#3ca5ea}.paginationjs.paginationjs-theme-green .paginationjs-pages li>a{color:#449d44}.paginationjs.paginationjs-theme-green .paginationjs-pages li>a:hover{background:#ebf4eb}.paginationjs.paginationjs-theme-green .paginationjs-pages li.active>a{background:#449d44;color:#fff}.paginationjs.paginationjs-theme-green .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-green .paginationjs-go-button>input[type=button]{background:#449d44;border-color:#449d44;color:#fff}.paginationjs.paginationjs-theme-yellow .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-yellow .paginationjs-pages li{border-color:#ec971f}.paginationjs.paginationjs-theme-green .paginationjs-go-button>input[type=button]:hover{background-color:#55a555}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li>a{color:#ec971f}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li>a:hover{background:#fdf5e9}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li.active>a{background:#ec971f;color:#fff}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-yellow .paginationjs-go-button>input[type=button]{background:#ec971f;border-color:#ec971f;color:#fff}.paginationjs.paginationjs-theme-red .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-red .paginationjs-pages li{border-color:#c9302c}.paginationjs.paginationjs-theme-yellow .paginationjs-go-button>input[type=button]:hover{background-color:#eea135}.paginationjs.paginationjs-theme-red .paginationjs-pages li>a{color:#c9302c}.paginationjs.paginationjs-theme-red .paginationjs-pages li>a:hover{background:#faeaea}.paginationjs.paginationjs-theme-red .paginationjs-pages li.active>a{background:#c9302c;color:#fff}.paginationjs.paginationjs-theme-red .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-red .paginationjs-go-button>input[type=button]{background:#c9302c;border-color:#c9302c;color:#fff}.paginationjs.paginationjs-theme-red .paginationjs-go-button>input[type=button]:hover{background-color:#ce4541}.paginationjs .paginationjs-pages li.paginationjs-next{border-right:1px solid #aaa\9}.paginationjs .paginationjs-go-input>input[type=text]{line-height:28px\9;vertical-align:middle\9}.paginationjs.paginationjs-big .paginationjs-pages li>a{line-height:36px\9}.paginationjs.paginationjs-big .paginationjs-go-input>input[type=text]{height:36px\9;line-height:36px\9}			
</style>
			<script src="js/pagination.min.js"></script>
			<script>
				function template(data, params) {
				  var headword = params.headword;
				  var pos = params.pos;
				  var html = '<tbody>';
				  $.each(data, function(key, val){		    
				    var title = 'Headword: ' + headword + '<br>';
		        title += 'POS: ' + pos + '<br>';
		        title += 'Date: ' + val.date + '<br>';
		        title += 'Title: ' + val.title + '<br>';
		        title += 'Page No:: ' + val.page + '<br><br>';
		        title += val.filename + '<br>' + val.id;
		        var slipClass = 'editSlipLink';
		        var slipLinkText = 'add';
		        var createSlipStyle = 'createSlipLink';
		        var slipUrl = '?m=collection&a=add&filename='+val.filename+'&wid='+val.id+'&headword='+headword+'&pos'+pos;
		        if (val.auto_id) {    //if a slip exists for this entry
		          slipLinkText = 'view';
		          slipClass = 'slipLink2';
		          createSlipStyle = '';
		          slipUrl = '#';
		        }
		        html += '<tr>';
		        html += '<td>' + val.date + '</td>'; 
		        html += '<td style="text-align: right;">'+val.pre.output + '</td>';
		        html += '<td><a target="_blank" href="?m=corpus&a=browse&id=' + val.tid + '&wid=' + val.id + '"';
		        html += ' data-toggle="tooltip" data-html="true" title="' + title + '">';
		        html += val.word + '</a>';
		        html += '<td>' + val.post.output + '</td>';
		        html += '<td><small><a href="'+slipUrl+'" target="_blank" class="' + slipClass + ' ' + createSlipStyle + '" data-uri="' + val.uri + '"';
		        if (slipClass == 'slipLink2') {   //only use the modal for existing slips
		          html += ' data-toggle="modal" data-target="#slipModal" ';
		        }
		        html += ' data-headword="' + headword + '" data-pos="' + pos + '"';
		        html += ' data-id="' + val.id + '" data-xml="' + val.filename + '"';
		        html += ' data-date="' + val.date + '" data-title="' + val.title + '" data-page="' + val.page + '"';
		        html += ' data-auto_id="' + val.auto_id + '"';
		        html += '>' + slipLinkText + '</a></small>';
		        html += '</td>';
		        html += '</tr>';
          });
				  html += '</tbody>';
				  return html;
				}
				
				$(function () {
				  $('.loadDictResults').on('click', function () {
				    var formNum = $(this).attr('data-formnum');
				    var action = $(this).attr('data-action');
				    if (action == 'hide') {
				      $('#results-'+formNum).hide();
				      $(this).attr('data-action', 'show');
				      $(this).find('.actionToggle').text('show'); //switch the toggle text to "show"
				      return;
				    }
				    $('#results-'+formNum).show();
				    $(this).find('.actionToggle').text('hide'); //switch the toggle text to "hide"
				    $('#loadingImage-'+formNum).show();
				    var locations = $(this).attr('data-locs');
				    var headword = $(this).attr('data-lemma');
            var pos = $(this).attr('data-pos');
				    var table = $('#form-'+formNum);	
				    var params = {headword: headword, pos: pos}
						$(this).attr('data-action', 'hide');  //link to hide the results
				    $('#pag-'+formNum).pagination({
					    dataSource: 'ajax.php',
					    locator: 'results',
					    totalNumberLocator: function(response) {
                return response.hits;
              },
					    pageSize: 10,
					    ajax: {
					        type: "POST",
					        data: {action: "getDictionaryResults", locs: locations},
					        //do something else here
					       /* beforeSend: function() {
					            table.html('Loading data from DASG ...');
					        }*/
					    },
					    callback: function(data, pagination) {
					        var html = template(data, params);	
					        $('#loadingImage-'+formNum).hide();
					        table.html(html);
					    }
						})
				  })
				});
			</script>
HTML;

	}
}
