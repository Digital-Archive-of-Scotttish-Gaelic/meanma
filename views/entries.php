<?php

namespace views;
use models;

class entries
{
	private $_db;

	public function __construct($db) {
		$this->_db = $db;
	}

  public function writeBrowseTable($entryIds) {
	  $user = models\users::getUser($_SESSION["email"]);
	  $deleteHeading = $deleteHtml = $deleteCell = "";
	  if ($user->getSuperuser()) {
		  $deleteHeading = '<th class="bg-danger" data-field="deleteEntry"><span class="text-white">Delete</span></th>';
		  $deleteHtml = <<<HTML
				<div class="col">
          <a href="#" id="deleteEntries" class="btn btn-danger disabled">delete</a>
				</div>
HTML;
	  }
    $tableBodyHtml = "<tbody>";
    foreach ($entryIds as $id) {
    	$entry = models\entries::getEntryById($id, $this->_db);
      $entryUrl = "?m=entries&a=view&id={$id}";
      if ($user->getSuperuser()) {
	      $deleteCell = <<<HTML
					<td><input type="checkbox" class="markToDelete" id="deleteEntry_{$id}"></td> 
HTML;
      }
      $tableBodyHtml .= <<<HTML
        <tr>
          <td>{$entry->getHeadword()}</td>
          <td>{$entry->getWordclass()}</td>
          <td><a href="{$entryUrl}" title="view entry for {$entry->getHeadword()}">
            view entry
          </td>
          {$deleteCell}
        </tr>
HTML;
    }
    $tableBodyHtml .= "</tbody>";
    echo <<<HTML
        <table id="browseEntriesTable" data-toggle="table" data-pagination="true" data-search="true">
          <thead>
            <tr>
              <th data-sortable="true">Headword</th>
              <th data-sortable="true">Part-of-speech</th>
              <th>Link</th>
              {$deleteHeading}
            </tr>
          </thead>
          {$tableBodyHtml}
        </table>
        <div class="row">
					<div class="col">
						<div class="mx-auto" style="width: 100px;">
              <a href="#" data-toggle="modal" data-target="#addEntryModal" title="add entry" id="addEntry" class="btn btn-success">add entry</a>
            </div>
					</div>
					{$deleteHtml}
				</div>
HTML;
    $this->_writeAddEntryModal();
    $this->_writeJavascript();;
  }

	private function _writeAddEntryModal() {
		echo <<<HTML
        <div id="addEntryModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="addEntryModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Add entry</h5>
                </div>
                <div class="modal-body">
                  <div class="form-group">
										<div class="row">		
											<label class="col-4" for="addHeadword">Headword:</label>
											<input type="text" class="form-control col-7" id="addHeadword" name="addHeadword" autofocus/>             
										</div>
										<div class="row">
											<label class="col-4" for="addWordclass">Part-of-speech:</label>
											<select id="addWordclass" name="addWordclass" class="form-control col-7">
			                  <option value="noun">noun</option>
			                  <option value="short">verb</option>
			                  <option value="preposition">preposition</option>
			                  <option value="adjective">adjective</option>
			                  <option value="adverb">verb</option>
			                  <option value="other">other</option>
			                </select> 
										</div>
                </div>
                <div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-dismiss="modal">cancel</button>
                  <button type="button" id="createEntry" class="btn btn-primary">add</button>
								</div>
							</div>
            </div>
          </div>
        </div>
HTML;
	}

  private function _writeJavascript() {
		echo <<<HTML
			<script>
				$(function () {
					//create an entry on modal form create button click
					$('#createEntry').on('click', function () {
					  let headword = $('#addHeadword').val();
					  let wordclass = $('#addWordclass').val();
					  if (headword == '') {
					    alert("Headword cannot be empty!");
					    $('#addHeadword').focus();
					    return;
					  }
					  $.getJSON('ajax.php?action=createEntry&headword='+headword+'&wordclass='+wordclass, function () {
					  })
					    .done(function (data) {
					      window.open('?m=entries&a=view&id='+data.id,'_self');
					    });	  
					});
					
					//mark entry for deletion - should only work for superuser
					$(document).on('click', '.markToDelete', function () {
					  if ($(this).hasClass('deleteEntry')) {
					    $(this).removeClass('deleteEntry');
					  } else {	    
					    $('#deleteEntries').removeClass('disabled');    // !! revisit
					    $(this).addClass('deleteEntry');
					  }
					});
				
					//delete selected entries
					$('#deleteEntries').on('click', function () {
					  if (!confirm('Are you sure you want to delete selected entries?')) {
					    return;
					  }
					  var entryIds = [];
					  $('.deleteEntry').each(function() {
					    let linkId = $(this).attr('id');
					    let entryId = linkId.split('_')[1];
					    entryIds.push(entryId);
					  });
					  let url = 'ajax.php';
					  let data = {action: 'deleteEntries', entryIds: entryIds}; 
					  $.ajax({url: url, data: data})
					    .done(function () {
					      location.reload();
					    });
					});
				
				});
			</script>
HTML;
  }
}
