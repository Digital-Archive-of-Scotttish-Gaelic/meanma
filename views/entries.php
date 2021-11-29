<?php

namespace views;
use models;

class entries
{
	private $_db;
	private $_entry;

	public function __construct($db) {
		$this->_db = $db;
	}

	public function writeEntry($entry) {
		$this->_entry = $entry;
  	$headword = $entry->getHeadword();
  	$wordclass = $entry->getWordclass();
  	$abbr = models\functions::getWordclassAbbrev($wordclass);
    echo <<<HTML
      <div id="#entryContainer">
        <div>
          <h4>{$headword} <em>{$abbr}</em></h4>
          <input type="hidden" id="lemma" value="{$headword}">
          <input type="hidden" id="wordclass" value="{$wordclass}">
        </div>
        <div>
          <a href="#" class="createPaperSlip" data-headword="{$entry->getHeadword()}" data-wordform="" data-entryid="{$entry->getId()}"><small>add paper slip</small></a> 
				</div>
        <div>
          <h5>Forms:</h5>
          {$this->_getFormsHtml()}
				</div>
				<div>
					<h5>Senses:</h5>
					{$this->_getSensesHtml()}
				</div>
			</div>
HTML;
    models\collection::writeSlipDiv();
    models\sensecategories::writeSenseModal();
    $this->_writeJavascript();
  }

  private function _getFormsHtml() {
  	$i=0;
	  $hideText = array("unmarked person", "unmarked number");
	  $html = "<ul>";
	  foreach ($this->_entry->getWordforms($this->_db) as $wordform => $morphGroup) {
	  	foreach ($morphGroup as $morphString => $slipIds) {
	  		$i++;
			  $morphHtml = str_replace('|', ' ', $morphString);
			  $morphHtml = str_replace($hideText, '', $morphHtml);
			  $slipList = $this->_getSlipListForForms($slipIds);
			  $citationHtml = <<<HTML
						<small><a href="#" class="citationsLink" data-type="form" data-index="{$i}">
								citations
						</a></small>
						<div id="form_citations{$i}" class="citation">
							<div class="spinner">
				        <div class="spinner-border" role="status">
				          <span class="sr-only">Loading...</span>
				        </div>
							</div>
							{$slipList}
						</div>
HTML;
			  $html .= <<<HTML
          <li>{$wordform} 
            ({$morphHtml}) {$citationHtml}
          </li>
HTML;
		  }
	  }
	  $html .= "</ul>";
	  return $html;
  }

  private function _getSlipListForForms($slipIds) {
  	$slipData = array();
	  foreach ($slipIds as $id) {
		  $info = models\collection::getSlipInfoBySlipId($id, $this->_db);
		  $isPaperSlip = ($info) ? false : true;      //if there is info this is a corpus_slip,
		                                              //otherwise it's a paper_slip
		  $slipData[$id] = $isPaperSlip ? array(array("auto_id"=>$id, "isPaperSlip"=>$isPaperSlip)) : $info;
	  }
		$slipList = '<table class="table"><tbody>';
		foreach ($slipData as $id => $data) {
			foreach ($data as $row) {
				$slipLinkData = array(
					"auto_id" => $row["auto_id"],
					"lemma" => $row["lemma"],
					"pos" => $row["pos"],
					"id" => $row["id"],
					"filename" => $row["filename"],
					"uri" => "",
					"date_of_lang" => $row["date_of_lang"],
					"title" => $row["title"],
					"page" => $row["page"]
				);
				$filenameElems = explode('_', $row["filename"]);
				$textLink = $row["filename"] ? '<a target="_blank" href="#" class="entryCitationTextLink"><small>view in text</small>' : '';
				$emojiHtml = $row["isPaperSlip"] ? '<span data-toggle="tooltip" data-placement="top" title="paper slip">&#x1F4DD;</span>' : "";
				$slipList .= <<<HTML
					<tr id="#slip_{$row["auto_id"]}" data-slipid="{$row["auto_id"]}"
						data-filename="{$row["filename"]}"
						data-id="{$row["id"]}"
						data-tid="{$row["tid"]}"
						data-date="{$row["date_of_lang"]}">
					<!--td data-toggle="tooltip"
						title="#{$filenameElems[0]} p.{$row["page"]}: {$row["date_of_lang"]}"
						class="entryCitationContext"></td-->
					<td class="entryCitationContext"></td>
					<td>{$emojiHtml}</td>
					<td class="entryCitationSlipLink">{$this->_getSlipLink($slipLinkData)}</td>
					<td>{$textLink}</td>
				</tr>
HTML;
			}
		}
		$slipList .= "</tbody></table>";
		return $slipList;
  }

	private function _getSensesHtml() {
  	//orphaned (uncategorised) senses
  	$orphanedSensesHtml = $this->_getOrphanSensesHtml();
  	if ($orphanedSensesHtml != "") {
		  $html = "<ul>" . $orphanedSensesHtml . "</ul>";
	  }
  	$html .= <<<HTML
			<div id="groupedSenses">
				<h6>Grouped Senses <a id="showIndividual" href="#" title="show individual senses"><small>show individual</small></a></h6> 
				<ul>
HTML;
  	//grouped senses
		$html .= $this->_getGroupedSensesHtml();
		$html .= '</ul></div>';
		//individual senses
		$html .= <<<HTML
			<div id="individualSenses" class="hide">
				<h6>Indivdual Senses <a id="showGrouped" href="#" title="show grouped senses"><small>show grouped</small></a></h6> 
				<ul>
HTML;
		$html .= $this->_getIndividualSensesHtml();
		$html .= '</ul></div>';
		return $html;
	}

	private function _getOrphanSensesHtml() {
		/* Get any citations without senses */
		$html = "";
		$nonSenseSlipIds = models\sensecategories::getNonCategorisedSlipIds($this->_entry->getId(), $this->_db);
		if (count($nonSenseSlipIds)) {
			$slipData = array();
			$index = 0;
			foreach ($nonSenseSlipIds as $slipId) {
				$index++;
				$slipData[] = models\collection::getSlipInfoBySlipId($slipId, $this->_db);
			}
			$html .= $this->_getSlipListHtml($slipData, array("uncategorised"), "orp_" . $index);
		}
		return $html;
	}

	private function _getIndividualSensesHtml() {
		/* Get citations for individual senses */
		$individualSenses = $this->_entry->getIndividualSenses();
		$index = 0;
		foreach ($individualSenses as $sense => $slipIds) {
			$slipData = array();
			foreach ($slipIds as $slipId) {
				$index++;
				$slipData[] = models\collection::getSlipInfoBySlipId($slipId, $this->_db);
			}
			$html .= $this->_getSlipListHtml($slipData, $sense, "ind_".$index);
		}
		return $html;
	}

	private function _getGroupedSensesHtml() {
		/* Get the citations with grouped senses */
		$index = 0;
		foreach ($this->_entry->getUniqueSenseIds($this->_db) as $slipId => $senseIds) {
			$slipData = array();
			$senseSlipIds = $this->_entry->getSenseSlipIds($slipId);
			foreach ($senseSlipIds as $id) {
				$index++;
				$slipData[] = models\collection::getSlipInfoBySlipId($id, $this->_db);
			}
			$html .= $this->_getSlipListHtml($slipData, $senseIds, "grp_".$index);
		}
		return $html;
	}

	private function _getSlipListHtml($slipData, $senseIds, $index) {
		$slipList = '<table class="table"><tbody>';
		foreach($slipData as $data) {
			foreach ($data as $row) {
				$filenameElems = explode('_', $row["filename"]);
				$translation = $row["translation"];
				$slipLinkData = array(
					"auto_id" => $row["auto_id"],
					"lemma" => $row["lemma"],
					"pos" => $row["pos"],
					"id" => $row["id"],
					"filename" => $row["filename"],
					"uri" => "",
					"date_of_lang" => $row["date_of_lang"],
					"title" => $row["title"],
					"page" => $row["page"]
				);
				$textLink = $row["filename"] ? '<a target="_blank" href="#" class="entryCitationTextLink"><small>view in text</small>' : '';
				$slipList .= <<<HTML
					<tr id="#slip_{$row["auto_id"]}" data-slipid="{$row["auto_id"]}"
							data-filename="{$row["filename"]}"
							data-id="{$row["id"]}"
							data-tid="{$row["tid"]}"
							data-precontextscope="{$row["preContextScope"]}"
							data-postcontextscope="{$row["postContextScope"]}"
							data-translation="{$translation}"
							data-date="{$row["date_of_lang"]}">
						<!--td data-toggle="tooltip"
							title="#{$filenameElems[0]} p.{$row["page"]}: {$row["date_of_lang"]} : {$translation}"
							class="entryCitationContext"></td-->
						<td class="entryCitationContext"></td>
						<td class="entryCitationSlipLink">{$this->_getSlipLink($slipLinkData)}</td>
						<td>{$textLink}</td>
					</tr>
HTML;
			}
		}
		$slipList .= "</tbody></table>";
		$citationsHtml = <<<HTML
				<small><a href="#" class="citationsLink" data-type="sense" data-index="{$index}">
						citations
				</a></small>
				<div id="sense_citations{$index}" class="citation">
					{$slipList}
				</div>
HTML;
		$senseString = "";
		if ($senseIds[0] == "uncategorised") {
			$senseString = <<<HTML
				<span data-toggle="modal" data-target="#senseModal" title="rename this sense" class="badge badge-secondary entrySense">
						uncategorised
					</span> 
HTML;
		} else {
			$senseIds = explode('|', $senseIds);
			foreach ($senseIds as $senseId) {
				$sense = new models\sense($senseId, $this->_db);
				$senseDescription = $sense->getDescription();
				$senseString .= <<<HTML
					<span data-toggle="modal" data-target="#senseModal" data-sense="{$senseId}" 
					data-sense-description="{$sense->getDescription()}" data-sense-name="{$sense->getName()}"
					data-title="{$senseDescription}" class="badge badge-success senseBadge">
						{$sense->getName()}
					</span> 
HTML;
			}
		}
		$html = <<<HTML
				<li>{$senseString} {$citationsHtml}</li>
HTML;
		return $html;
	}

	private function _formatTranslation($html) {  //not currently in use - revisit SB
  	$text = strip_tags($html);
  	$text = addslashes($text);
  	return $text;
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
    $this->_writeBrowseJavascript();;
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

  private function _getSlipLink($result) {
		$slipType = $result["filename"] ? "corpus" : "paper";
		return <<<HTML
						<small>
                <a href="#" class="slipLink2"
                    data-toggle="modal" data-target="#slipModal"
                    data-auto_id="{$result["auto_id"]}"
                    data-headword="{$this->_entry->getHeadword()}"
                    data-entryid="{$this->_entry->getId()}"
                    data-sliptype= "{$slipType}"
                    data-pos="{$result["pos"]}"
                    data-id="{$result["id"]}"
                    data-filename ="{$result["filename"]}"
                    data-date="{$result["date_of_lang"]}"
                    data-title="{$result["title"]}"
                    data-page="{$result["page"]}"
                    data-resultindex="">
                      view
                </a>
            </small>
HTML;
  }

  private function _writeBrowseJavascript() {
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

  private function _writeJavascript() {
  	echo <<<HTML
			<script>
				//create a paper slip for the selected wordform
				$('.createPaperSlip').on('click', function () {
				   let wordform = $(this).attr('data-wordform');
				   let entryId = $(this).attr('data-entryid');
				   let headword = $(this).attr('data-headword');
				   $.getJSON('ajax.php?action=createPaperSlip&entryId='+entryId+'&wordform='+wordform, function (data) {
				     var url = '?m=collection&a=edit&entryId='+entryId+'&id=' + data.id + '&filename=&headword='+headword;
             url += '&pos=' + data.pos + '&wid=';
             var win = window.open(url, '_blank');
				   });
				});
				
				$('#showIndividual').on('click', function () {
				  $('#groupedSenses').hide();
				  $('#individualSenses').show();
				  return false;
				});
				
				$('#showGrouped').on('click', function () {
				  $('#individualSenses').hide();
				  $('#groupedSenses').show();
				  return false;
				});
							
				/**
        *  Load and show the citations for wordforms or senses
        */
				$('.citationsLink').on('click', function () {
				  $('.spinner').show();
				  let type = $(this).attr('data-type');   //i.e. "form" or "sense"
			    var citationsLink = $(this);
			    var citationsContainerId = '#' + type + '_citations' + $(this).attr('data-index');
			    if ($(this).hasClass('hideCitations')) {
			      $(citationsContainerId).hide();
			      $(this).text('citations');
			      $(this).removeClass('hideCitations');
			      return;
			    }
			    $(citationsContainerId + "> table > tbody > tr").each(function() {
			      var slipId = $(this).attr('data-slipid');
			      var date = $(this).attr('data-date');
			      var html = '';
			      if (date) {
			        html += '<span class="text-muted">' + date + '.</span> ';
			      } 
			      var wid = $(this).attr('data-id');
			      var tid = $(this).attr('data-tid');
			      var tr = $(this);
			      var title = tr.prop('title');
						var url = 'ajax.php?action=getCitationsBySlipId&slipId='+slipId;
			      $.getJSON(url, function (data) {
			        var corpusLink = 'index.php?m=corpus&a=browse&id=' + tid + '&wid=' + wid; //title id and word id
				      tr.find('.entryCitationTextLink').attr('href', corpusLink); //add the link to text url
				      if (type == "form") {     //default to short citation for forms 	  
							  if (!data.form) {
							    if (!data.sense) {
							      html += getCitationHtml("draft", data.draft); //no form or sense so write draft
							    } else {
							      html += getCitationHtml("sense", data.sense); //no form so write sense
							    }
							  } else {
							    html += getCitationHtml("form", data.form); //there is a form so write it
							  }
				      } else if (type == "sense") {
				        if (!data.sense) {
							    if (!data.form) {
							      html += getCitationHtml("draft", data.draft); //no form or sense so write draft
							    } else {
							      html += getCitationHtml("form", data.form); //no sense so write form
							    }
							  } else {
							    html += getCitationHtml("sense", data.sense); //there is a sense so write it 
							  }
				      }			      
				      tr.find('.entryCitationContext').html(html);
			      })
			        .then(function () {
			          $('.spinner').hide();
			        });

			    });
			    $(citationsContainerId).show();
			    citationsLink.text('hide');
			    citationsLink.addClass('hideCitations');
			  });
				
				function getCitationHtml(citationType, info) {
				  let translation = info.translation;
					html = info.context.html + ' <em>(' + citationType + ')</em>';
					if (info.reference) {
					  html += '<br>' + info.reference;
					}
					if (translation) {
					  html += getTranslationHtml(translation, info.cid);
					}
					return html;
				}
				
				function getTranslationHtml(content, index) {
				  html = '<div><small><a href="#translation' + index + '" '; 
          html += 'data-toggle="collapse" aria-expanded="false" aria-controls="#translation' + index + '">';
          html += 'show/hide translation</a></small></div>';
          html += '<div id="translation' + index + '" class="collapse"><small class="text-muted">'+content+'</small></div>';
          return html;
				}
			</script>
HTML;
  }
}
