<?php

echo <<<HTML
			<div class="row">
				<div class="col">
				
		      <form>
		        <div class="form-group">
		          <div class="input-group">
		            <input type="text" name="q"/>
		            <div class="input-group-append">
		              <input type="hidden" name="m" value="corpus">
		              <input type="hidden" name="a" value="xsearch"/>
		              <input type="hidden" name="id" value="{$_GET["id"]}">
		              <button name="submit" class="btn btn-primary" type="submit">search</button>
		            </div>
		          </div>
		        </div>
		        <div class="form-group">
		          <div class="form-check form-check-inline">
		            <input class="form-check-input" type="radio" name="mode" id="headwordRadio" value="head-form" checked>
		            <label class="form-check-label" for="headwordRadio">headword</label>
		          </div>
		          <div class="form-check form-check-inline">
		            <input class="form-check-input" type="radio" name="mode" id="wordformRadio" value="word-form">
		            <label class="form-check-label" for="wordformRadio">wordform</label>
		          </div>
		        </div>
		        <!--div class="form-group">
		          <a href="#" id="multiWordShow">show multi-word options</a>
		          <a href="#" id="multiWordHide">hide multi-word options</a>
						</div-->
		        <!--div id="multiWord" style="padding:20px; display: none;">
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
						</div-->  <!-- //end multiWord -->
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
		          <!--div class="form-check form-check-inline">
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
		          </div-->
		        </div>
HTML;

//Date range
if (!empty($minMaxDates)) {
    ?>
        <div class="form-group">
            <p>Restrict by date range:</p>
            <div id="selectedDatesDisplay"><?= $minMaxDates["min"] ?>-<?=$minMaxDates["max"]?></div>
            <input type="hidden" class="form-control col-2" name="selectedDates" id="selectedDates">
            <div id="dateRangeSelector" class="col-6 mb-5">
                <label id="dateRangeMin"><?= $minMaxDates["min"] ?></label>
                <label id="dateRangeMax"><?= $minMaxDates["max"] ?></label>
            </div>
        </div>
<?php
}

//Districts (Geographical Origins)
if (!empty($districts)) {
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
    echo <<<HTML
			<div class="form-group">
            <p>Restrict by location:</p>
            <div>
              {$districtsHtml}
              <input type="hidden" id="allDistricts" name="allDistricts" value="true">
            </div>
            <div>
              <a href="#" id="uncheckAllDistricts">uncheck all</a>
              <a href="#" class="hide" id="checkAllDistricts">check all</a>
            </div>
        </div>
HTML;
}

echo <<<HTML
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
HTML;

//Parts of Speech
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
echo $posHtml;

$id = $_GET["id"] != 0 ? "_" . $_GET["id"] : "";    //ignore the "root" 0 text
echo <<<HTML
		            <note><em>Select multiple options by using CTRL key (Windows) or Command key (Mac)</em></note>
		        </div>
          </div> <!-- end col -->
        </div>  <!-- end row -->
        <input type="hidden" name="text" value="{$id}">
      </form>




<script>
    $(function() {
        
      $( "#dateRangeSelector" ).slider({
        range:true,
        min: {$minMaxDates["min"]},
        max: {$minMaxDates["max"]},
        values: [ {$minMaxDates["min"]}, {$minMaxDates["max"]} ],
        slide: function( event, ui ) {
          var output = ui.values[0] + "-" + ui.values[1];
          $("#selectedDates").val(output);
          $('#selectedDatesDisplay').html(output);
        }
      });
      
      $('#uncheckAllDistricts').on('click', function() {
        $('.district').prop('checked', false);
        $('#allDistricts').val('');
        $('#checkAllDistricts').removeClass('hide');
        $('#uncheckAllDistricts').addClass('hide');
      });
      
      $('#checkAllDistricts').on('click', function() {
        $('.district').prop('checked', true);
        $('#allDistricts').val('true');
        $('#checkAllDistricts').addClass('hide');
        $('#uncheckAllDistricts').removeClass('hide');
      });
      
      //clean up the query string (e.g. don't include all the districts)
      $('form').on('submit', function(e) {    
          if ($('#allDistricts').val() == 'true') {
              $('.district').prop('checked', false);
          } 
        });
      
    });
    </script>
HTML;

