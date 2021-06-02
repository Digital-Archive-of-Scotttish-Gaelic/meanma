<?php


namespace views;


use models;

class slow_search extends search
{
	private $_model;  //an instance of models\slow_search

	public function __construct($model) {
		$this->_model = $model;
	}

	public function show($xpath) {
		if ($xpath=="") {   //no results so write the form
			parent::writeSubHeading();
			echo <<<HTML
				<div class="float-right">
					<small><a href="?m=corpus&a=search&id={$_GET["id"]}">word search</a></small>
				</div>
		    <form>
			    <input type="hidden" name="m" value="corpus">
			    <input type="hidden" name="a" value="slow_search">
			    <input type="hidden" name="id" value="{$_GET["id"]}">
			    <div class="form-group row">
				    <div class="input-group col-sm-6">
					    <input type="text" class="form-control" name="xpath" value="@lemma='craobh'">
					    <div class="input-group-append">
						    <button class="btn btn-primary" type="submit">search</button>
					    </div>
				    </div>
			    </div>
			    <div class="form-group row">
	          <label class="form-check-label col-sm-1" for="chunkOff">Get all results</label>
	          <input type="radio" id="chunkOff" class="form-control-default col-sm-1" aria-label="Get all results" name="chunk" value="off">
					</div>
					<div class="form-group row">
	          <label class="form-check-label col-sm-1" for="chunkOn">Chunk results</label>
	          <input type="radio" id="chunkOn" class="form-control-default col-sm-1" aria-label="Chunk results" name="chunk" value="on" checked>
	        </div>
	        <div class="form-group row">
	          <label class="form-check-label col-sm-1" for="chunkValue">Results per chunk</label>
	          <input type="text" id="chunkValue" class="form-control-sm col-sm-1" name="chunkValue" value="10">
					</div>
		    </form>
HTML;
		} else {    //there are results so show them
			models\collection::writeSlipDiv();
			echo <<<HTML
				<p><a href="?m=corpus&a=slow_search&id={$_GET["id"]}">new xpath search</a></p>
				<p>Searching for: {$xpath}</p>
HTML;
			$chunkSize = ($_GET["chunk"] == "on") ? $_GET["chunkValue"]-1 : null;
			$html = <<<HTML
				<table id="table" class="table">					
					<tbody>
HTML;
				$loadMoreResultsHtml = $chunkSize ? '<a href="#" id="loadMoreResults" title="load more">load more results ...</a>' : "";
				$html .= <<<HTML
					</tbody>
				</table>
				<div class="loading" style="display:none;"><img src="https://dasg.ac.uk/images/loading.gif" width="200" alt="loading"></div>
				<div class="pagination"></div>
				<div id="endOfResults" style="display: none;"><h3>no more results</h3></div>
				{$loadMoreResultsHtml}
HTML;
			echo $html;
    }
		$this->_writeResultsJavascript($xpath, $chunkSize);
	}

	private function _writeResultsJavascript($xpath, $chunkSize) {
		$xpath = urlencode($xpath);
		$chunkSize = $chunkSize ? $chunkSize : 'null';
		echo <<<HTML
			<script type="text/javascript" src="js/jquery.simplePagination.js"></script>
			<script>
				$(function() {
				  
					loadResults();  //initial automatic search on page load
					
				  $('#chunkOn').on('click', function() {
				    $('#chunkValue').prop('disabled', false);
				  });
				  
				  $('#chunkOff').on('click', function () {
				    $('#chunkValue').prop('disabled', true);  
				  });
				  
				  $('#loadMoreResults').on('click', document, function () {
				    loadResults();
				  });
				  
				});   //end of document load handler
				
				/** Main load results function to fetch results using AJAX **/
				function loadResults() {
				  $('.loading').show();
			    var xpath = '{$xpath}';
			    var chunkSize = {$chunkSize};
			    var offsetFilename = $('table tr').last().attr('data-filename');
			    var offsetId = $('table tr').last().attr('data-id');
			    var index = $('table tr').last().attr('data-index');
			    if (!index) {
			      index = -1;
			    }
			    $.getJSON('ajax.php', {action: 'getSlowSearchResults', xpath: xpath, chunkSize: chunkSize, 
			          offsetFilename: offsetFilename, offsetId: offsetId, index: index, id: '{$_GET["id"]}'})
			      .done(function (results) {
			        if (error = results.error) {
			          $('.loading').hide();
			          $('#endOfResults').html('<h2>'+error+'</h2>');
			          $('#loadMoreResults').hide();
			          $('#endOfResults').show();
			          return;
			        }
			        $('.loading').hide();
			        if (results.length == 0) {
			          $('#loadMoreResults').hide();
			          $('#endOfResults').show();
			          return;
			        }
			        $.each(results, function (key, result) {
			          index++;
			          var rowNum = index+1;
			          var data = result.data;
			          var context = data.context;
			          var title = 'Headword: '+data.lemma+'<br>';
		            title += 'POS: '+data.pos+' '+ data.posLabel+'<br>';
		            title += 'Date: '+data.date_of_lang+'<br>';
		            title += 'Title: '+data.title+'<br>';
		            title += 'Page No: '+data.page+'<br><br>';
		            title += data.filename+'<br>'+data.id;
			          var html = '<tr data-filename="'+data.filename+'" data-id="'+data.id+'" data-index='+index+'>';
			          html += '<th>'+rowNum+'</th>';
			          html += '<td>'+data.date_of_lang+'</td>';
			          html += '<td style="text-align:right;">'+context.pre.output+'</td>';
			          html += '<td style="text-align: center;">';
			          html += '<a target="_blank" href="?m=corpus&a=browse&id='+data.tid+'&wid='+data.id+'"';
                html +=  ' data-toggle="tooltip" data-html="true" title="'+title+'">';
                html += context.word + '</a></td>';
			          html += '<td>'+context.post.output+'</td>';
			          html += '<td><small>'+data.slipLinkHtml+'</small></td>';
			          html += '</tr>';
			          $("table").append(html);
			          paginate();
			          if (!chunkSize) {
			            $('.pagination').pagination('selectPage', 1);    //jump to first page of results on page load
			          }
			        });				        
			      });
				}
				
				/** Pagination for results */
			  function paginate() {				    
			    var items = $("table tr");
					var numItems = items.length;
					var perPage = 10;
					items.slice(perPage).hide();
					if(numItems != 0) {
						$(".pagination").pagination({
							items: numItems,
							itemsOnPage: perPage,
							cssStyle: "light-theme",
							onPageClick: function(pageNumber) { 
								var showFrom = perPage * (pageNumber - 1);
								var showTo = showFrom + perPage;
								items.hide().slice(showFrom, showTo).show();
							}
						});
						var totalPages = $('.pagination').pagination('getPagesCount');
						$('.pagination').pagination('selectPage', totalPages);    //jump to last page of results
					}
				}
			</script>
HTML;
	}
}
