<?php

namespace views;
use models;

class entry
{
	private $_db;
	private $_entry;

	public function __construct($db) {
		$this->_db = $db;
	}

	public function writeEntry($entry, $type) {
		$starttime = microtime(true);
		$this->_entry = $entry;
		$headword = $entry->getHeadword();
		$wordclass = $entry->getWordclass();
		$abbr = models\functions::getWordclassAbbrev($wordclass);
		$this->_writeSubNav($type);
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
HTML;
		switch ($type) {
			case "piles":
				$this->_writeSensesView();
				break;
			case "slips":
				$this->_writeSlipsView();
				break;
			default:
				$this->_writeFormsView();
		}
		echo <<<HTML
			</div>
HTML;
		/**
		 * Debug code for admin
		 */
		$user = models\users::getUser($_SESSION["email"]);
		if ($user->getSuperuser()) {
			$endtime = microtime(true);
			$pageLoadTime = $endtime - $starttime;
			$queryCount = $this->_db->getQueryCount();
			$slipCount = $this->_entry->getSlipCount($this->_db);
			echo <<<HTML
			<h4>Page info:</h4>
			<dl>
				<dt>Page load time</dt>
				<dd>{$pageLoadTime} seconds</dd>
				<dt>Query count</dt>
				<dd>{$queryCount}</dd>
				<dt>Slip count</dt>
				<dd>{$slipCount}</dd>
			</dl>
HTML;
		}
		models\collection::writeSlipDiv();
		models\sensecategories::writeSenseModal();
		$this->_writeJavascript();
	}

	private function _writeSubNav($type) {
		$listItemHtml = "";
		switch ($type) {
			case "piles":
				$listItemHtml = <<<HTML
					<li class="nav-item"><a class="nav-link" title="forms" href="?m=entries&a=view&type=forms&id={$this->_entry->getId()}">forms</a></li>
					<li class="nav-item"><div class="nav-link active">piles</div></li>
					<li class="nav-item"><a class="nav-link" title="slips" href="?m=entries&a=view&type=slips&id={$this->_entry->getId()}">slips</a></li>    	
HTML;
				break;
			case "slips":
				$listItemHtml = <<<HTML
					<li class="nav-item"><a class="nav-link" title="forms" href="?m=entries&a=view&type=forms&id={$this->_entry->getId()}">forms</a></li>
					<li class="nav-item"><a class="nav-link" title="piles" href="?m=entries&a=view&type=piles&id={$this->_entry->getId()}">piles</a></li>
			    <li class="nav-item"><div class="nav-link active">slips</div></li>	
HTML;
				break;
			default:
				$listItemHtml = <<<HTML
					<li class="nav-item"><div class="nav-link active">forms</div></li>
					<li class="nav-item"><a class="nav-link" title="piles" href="?m=entries&a=view&type=piles&id={$this->_entry->getId()}">piles</a></li>
					<li class="nav-item"><a class="nav-link" title="slips" href="?m=entries&a=view&type=slips&id={$this->_entry->getId()}">slips</a></li>    	
HTML;
		}
		echo <<<HTML
			<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">			  
				{$listItemHtml}		    		
		  </ul>	
HTML;
	}

	private function _writeFormsView() {
		echo <<<HTML
			<div>
        <h5>Forms:</h5>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="formsOptions" id="formsOnly" value="formsOnly" checked>
          <label class="form-check-label" for="formsOnly"><small>form citations only</small></label>
				</div>
				<div class="form-check form-check-inline"> 
          <input class="form-check-input" type="radio" name="formsOptions" id="allCitations" value="allCitations">
          <label class="form-check-label" for="allCitations"><small>all citations</small></label>
				</div>
        {$this->_getFormsHtml()}
			</div>
HTML;
		return;
	}

	private function _writeSensesView() {
		echo <<<HTML
				<div>
					<h5>Piles:</h5>
					{$this->_getSensesHtml()}
				</div>
HTML;
	}

	private function _writeSlipsView() {
		$tableBodyHtml = "<tbody>";
		$slipIds = $this->_entry->getSlipIds($this->_db);
		foreach ($slipIds as $slipId) {
			$slip = models\collection::getSlipBySlipId($slipId, $this->_db);
			$citations = $slip->getCitations();

			$testCitation = array_pop($citations);
			if (!empty($testCitation)) {
				$context = $testCitation->getContext();
				$tableBodyHtml .= <<<HTML
					<tr>
						<td>{$context["html"]}</td>
						<td>{$slipId}</td>
					</tr>
HTML;
			}
		}
		$tableBodyHtml .= "</tbody>";

		echo <<<HTML
        <table id="browseSlipsTable" data-toggle="table" data-pagination="true" data-search="true">
          <thead>
            <tr>
              <th data-sortable="true">Context</th>
              <th data-sortable="true">ID</th>
            </tr>
          </thead>  
            {$tableBodyHtml}
        </table>
HTML;
	}

	private function _getFormsHtml() {
		$i=0;
		$hideText = array("unmarked person", "unmarked number");
		$html = "<ul>";

		//group the wordforms by morphology –
		$groupedArray = array();
		foreach ($this->_entry->getWordforms($this->_db) as $wordform => $morphGroup) {
			foreach ($morphGroup as $morphString => $slipIds) {
				$groupedArray[$morphString][$wordform] = $slipIds;
			}
		}

		foreach ($groupedArray as $morphString => $wordforms) {
			$morphHtml = str_replace('|', ' ', $morphString);
			$morphHtml = str_replace($hideText, '', $morphHtml);
			$html .= <<<HTML
				<li>{$morphHtml} – <ul>		
HTML;

			foreach ($wordforms as $wordform => $slipIds) {
				$i++;
				$slipList = $this->_getSlipListForForms($slipIds);
				$citationHtml = <<<HTML
						<small><a href="#" class="citationsLink" data-type="form" data-index="{$i}">
								citations
						</a></small>
						<div id="form_citations{$i}" data-loaded class="citation">
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
            {$citationHtml}
          </li>
HTML;
			}
			$html .= "</ul></li>";
		}

		$html .= "</ul>";
		return $html;
	}

	private function _getSlipListForForms($slipIds) {
		$slipList = '<table class="table"><tbody>';
		foreach ($slipIds as $id) {
			$slipData = models\collection::getSlipInfoBySlipId($id, $this->_db);
			$row = $slipData[0];
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
	/*			if (!$row["auto_id"]) {
					continue;             //bug fix
				}
	*/			$slipList .= <<<HTML
					<tr id="#slip_{$row["auto_id"]}" data-slipid="{$row["auto_id"]}"
						data-filename="{$row["filename"]}"
						data-id="{$row["id"]}"
						data-tid="{$row["tid"]}"
						data-date="{$row["date_internal"]}"
						data-date_display="{$row["date_display"]}">
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

		$slipList .= "</tbody></table>";
		return $slipList;
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

	private function _getSensesHtml() {
		//orphaned (uncategorised) senses
		$orphanedSensesHtml = $this->_getOrphanSensesHtml();
		if ($orphanedSensesHtml != "") {
			$html = "<ul>" . $orphanedSensesHtml . "</ul>";
		}
		$html .= <<<HTML
			<div id="groupedSenses">
				<h6>Grouped Piles <a id="showIndividual" href="#" title="show individual senses"><small>show individual</small></a></h6> 
				<ul>
HTML;
		//grouped senses
		$html .= $this->_getGroupedSensesHtml();
		$html .= '</ul></div>';
		//individual senses
		$html .= <<<HTML
			<div id="individualSenses" class="hide">
				<h6>Individual Piles <a id="showGrouped" href="#" title="show grouped senses"><small>show grouped</small></a></h6> 
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

	private function _getSlipListHtml($slipData, $senseIds, $index) {
		$slipList = '<table class="table"><tbody>';
		foreach($slipData as $data) {
			foreach ($data as $row) {
				if (!$row["auto_id"]) {
					continue;             // temp bug fix
				}

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
             url += '&pos=&wid=';
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
					
				$('#formsOnly').on('click', function () {
					$('.forms_sense').hide();
					$('.forms_draft').hide();
				});
				
				$('#allCitations').on('click', function () {
					$('.forms_sense').show();
					$('.forms_draft').show();
				});
						
				/**, 
        *  Load and show the citations for wordforms or senses
        */
				$('.citationsLink').on('click', function () {
				  //$('.spinner').show();
				  let type = $(this).attr('data-type');   //i.e. "form" or "sense"
			    var citationsLink = $(this);
			    var citationsContainerId = '#' + type + '_citations' + $(this).attr('data-index');
			    if ($(this).hasClass('hideCitations')) {
			      $(citationsContainerId).hide();
			      $(this).text('citations');
			      $(this).removeClass('hideCitations');
			      return;
			    }
			    //check if data has alreay loaded
		      if ($(citationsContainerId).attr('data-loaded')) {
		        
		        $(citationsContainerId).show();
			    citationsLink.text('hide');
			    citationsLink.addClass('hideCitations');
			      console.log('populated');
			      return;
			    }
			    
			    $(citationsContainerId + "> table > tbody > tr").each(function() {
			      var tr = $(this);
			      var formsOnly = $("input[name='formsOptions']:checked").val() == "formsOnly" ? true : false;  
			      var slipId = $(this).attr('data-slipid');
			      var date = $(this).attr('data-date_display');
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
							      tr.addClass("forms_draft");
							      if (formsOnly) {
							        tr.addClass('hide');
							      } else {
							        tr.removeClass('hide');
							      }
							    } else {
							      html += getCitationHtml("sense", data.sense); //no form so write sense
							      tr.addClass("forms_sense");
							      if (formsOnly) {
							        tr.addClass('hide');
							      } else {
							        tr.removeClass('hide');
							      }
							    }
							  } else {
							    html += getCitationHtml("form", data.form); //there is a form so write it
							    tr.addClass("forms_form");
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
			          $(citationsContainerId).attr('data-loaded', true);
			          //$('.spinner').hide();
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

	private function _formatTranslation($html) {  //not currently in use - revisit SB
		$text = strip_tags($html);
		$text = addslashes($text);
		return $text;
	}
}