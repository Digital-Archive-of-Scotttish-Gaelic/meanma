<?php

namespace views;
use models;

class collection
{
	private $_type = "corpus";  //the collection type : corpus or paper

	public function show($action = "browse", $type = "corpus", $id = null) {
		$this->_type = $type;
		$this->_writeSubNav($type);
		$this->_writeBrowseTable();
	}

	private function _getType() {
		return $this->_type;
	}

	private function _writeSubNav($type) {
		$listItemHtml = "";
		if ($type == "paper") {
			$listItemHtml = <<<HTML
				<li class="nav-item"><a class="nav-link" title="corpus slips" href="?m=collection&a=browse&type=corpus">corpus slips</a></li>
		    <li class="nav-item"><div class="nav-link active">paper slips</div></li>	
HTML;
		} else {
			$listItemHtml = <<<HTML
				<li class="nav-item"><div class="nav-link active">corpus slips</div></li>
		    <li class="nav-item"><a class="nav-link" title="paper slips" href="?m=collection&a=browse&type=paper">paper slips</a></li>	
HTML;
		}
		echo <<<HTML
			<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">			  
				{$listItemHtml}		    		
		  </ul>	
HTML;

	}

  private function _writeBrowseTable() {
		$user = models\users::getUser($_SESSION["email"]);
		$deleteHeading = $deleteHtml = "";
		if ($user->getSuperuser()) {
			$deleteHeading = '<th class="bg-danger" data-field="deleteSlip"><span class="text-white">Delete</span></th>';
			$deleteHtml = <<<HTML
				<div class="col">
          <a href="#" id="deleteSlips" class="btn btn-danger disabled">delete</a>
				</div>
HTML;
		}
    echo <<<HTML
      <table id="table" data-toggle="table" data-ajax="ajaxRequest"
        data-search="true"
        data-side-pagination="server"
        data-pagination="true">
          <thead>
              <tr>
                  <th data-field="auto_id" data-sortable="true">ID</th>
                  <th data-field="headword" data-sortable="true">Headword</th>
                  <th data-field="wordform" data-sortable="true">Wordform</th>
                  <th data-field="wordclass" data-sortable="true">Part-of-speech</th>
                  <th data-field="senses">Piles</th>
                  <th data-field="morph">Morphological</th>
                  <th data-field="fullname" data-sortable="true">Owned By</th>
                  <th data-field="lastUpdated" data-sortable="true">Date</th>
                  <th data-field="printSlip">Print</th>
                  {$deleteHeading}
              </tr>
          </thead>
      </table>
      <div class="row">
        <div class="col">
          <a href="printSlip.php?action=writePDF" target="_blank" id="printSlips" class="btn btn-primary disabled">print</a>
				</div>
				{$deleteHtml}
			</div>
HTML;

    models\collection::writeSlipDiv();
    models\pilecategories::writePileModal();
    $this->_writeJavascript();
  }

  private function _writeJavascript() {
    echo <<<HTML
			<script>

				$(function () {
					$(document). on('change', '.chooseSlip', function () {
					  var elemId = $(this).attr('id');
					  var elemParts = elemId.split('_');
					  var slipId = elemParts[1];
					  var url = 'ajax.php?action=updatePrintList';
						if ($(this).prop('checked')) {  //add to the print list
						  url += '&addSlip=' + slipId;
						} else {    //remove from the print list
							url += '&removeSlip=' + slipId;
						}
						var count = null;
						$.getJSON(url, function (data) {
						  count = data.count;
						}).done(function () {
						    if (count) {
						      $('#printSlips').removeClass('disabled');
						    } else {
						      $('#printSlips').addClass('disabled');
						    }
						});
					});
				});

				/**
				* Clear the checkboxes when the print button is clicked
				*/
				$('#printSlips').on('click', function () {
				  $(this).addClass('disabled');
				  $('.chooseSlip').prop('checked', '');
				});
				
				// delete slip(s) functions - should only work for superuser
				$(document).on('click', '.markToDelete', function () {
				  if ($(this).hasClass('deleteSlip')) {
				    $(this).removeClass('deleteSlip');
				  } else {
				    
				    $('#deleteSlips').removeClass('disabled');    // !! revisit
				    
				    $(this).addClass('deleteSlip');
				  }
				});
				
				$('#deleteSlips').on('click', function () {
				  if (!confirm('Are you sure you want to delete selected slips(s)?')) {
				    return;
				  }
				  var slipIds = [];
				  $('.deleteSlip').each(function() {
				    let linkId = $(this).attr('id');
				    let slipId = linkId.split('_')[1];
				    slipIds.push(slipId);
				  });
				  let url = 'ajax.php';
				  let data = {action: 'deleteSlips', slipIds: slipIds};
				  $.ajax({url: url, data: data})
				    .done(function () {
				      location.reload();
				    });
				});
				
				/**
				* Runs an AJAX request to populate the Bootstrap table 
				* @param params
				*/
			  function ajaxRequest(params) {
			    $.getJSON( 'ajax.php?action=getSlips&type={$this->_getType()}&' + $.param(params.data), {format: 'json'}).then(function (res) {
			      params.success(res)
					});
			  }
</script>
HTML;
  }
}
