<?php

namespace views;
use models;

class collection
{
	public function show($action = "browse", $id = null) {
		switch ($action) {
			case "browse":
				$this->_writeBrowseTable();
				break;
		}
	}

  private function _writeBrowseTable() {
    echo <<<HTML
      <table id="table" data-toggle="table" data-ajax="ajaxRequest"
        data-search="true"
        data-side-pagination="server"
        data-pagination="true">
          <thead>
              <tr>
                  <th data-field="auto_id" data-sortable="true">ID</th>
                  <th data-field="lemma" data-sortable="true">Headword</th>
                  <th data-field="wordform" data-sortable="true">Wordform</th>
                  <th data-field="wordclass" data-sortable="true">Part-of-speech</th>
                  <th data-field="senses">Senses</th>
                  <th data-field="morph">Morphological</th>
                  <th data-field="fullname" data-sortable="true">Owned By</th>
                  <th data-field="lastUpdated" data-sortable="true">Date</th>
                  <th data-field="printSlip">Print</th>
              </tr>
          </thead>
      </table>
      <a href="printSlip.php?action=writePDF" target="_blank" id="printSlips" class="btn btn-primary disabled">print</a>
HTML;

    models\collection::writeSlipDiv();
    models\sensecategories::writeSenseModal();
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

				/**
				* Runs an AJAX request to populate the Bootstrap table
				* @param params
				*/
			  function ajaxRequest(params) {
			    $.getJSON( 'ajax.php?action=getSlips&' + $.param(params.data), {format: 'json'}).then(function (res) {
			      params.success(res)
					});
			  }
</script>
HTML;
  }
}
