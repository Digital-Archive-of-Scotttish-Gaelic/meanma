<?php

namespace views;
use models;

class entries
{
  public function writeEntry($entry) {
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
          <h5>Forms:</h5>
          {$this->_getFormsHtml($entry)}
				</div>
				<div>
					<h5>Senses:</h5>
					{$this->_getSensesHtml($entry)}
				</div>
			</div>
HTML;
    models\collection::writeSlipDiv();
    models\sensecategories::writeSenseModal();
    $this->_writeJavascript();
  }

  private function _getFormsHtml($entry) {
  	$i=0;
	  $hideText = array("unmarked person", "unmarked number");
	  $html = "<ul>";
	  foreach ($entry->getWordforms() as $wordform => $morphGroup) {
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
			  $html .= "<li>{$wordform} ({$morphHtml}) {$citationHtml}</li>";
		  }
	  }
	  $html .= "</ul>";
	  return $html;
  }

  private function _getSlipListForForms($slipIds) {
  	$slipData = array();
	  foreach ($slipIds as $id) {
		  $slipData[] = models\collection::getSlipInfoBySlipId($id);
	  }
		$slipList = '<table class="table"><tbody>';
		foreach ($slipData as $data) {
			foreach ($data as $row) {
				$translation = $this->_formatTranslation($row["translation"]);
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
					<td><a target="_blank" href="#" class="entryCitationTextLink"><small>view in text</small></td>
				</tr>
HTML;
			}
		}
		$slipList .= "</tbody></table>";
		return $slipList;
  }

	private function _getSensesHtml($entry) {
  	//orphaned (uncategorised) senses
  	$orphanedSensesHtml = $this->_getOrphanSensesHtml($entry);
  	if ($orphanedSensesHtml != "") {
		  $html = "<ul>" . $orphanedSensesHtml . "</ul>";
	  }
  	$html .= <<<HTML
			<div id="groupedSenses">
				<h6>Grouped Senses <a id="showIndividual" href="#" title="show individual senses"><small>show individual</small></a></h6> 
				<ul>
HTML;
  	//grouped senses
		$html .= $this->_getGroupedSensesHtml($entry);
		$html .= '</ul></div>';
		//individual senses
		$html .= <<<HTML
			<div id="individualSenses" class="hide">
				<h6>Indivdual Senses <a id="showGrouped" href="#" title="show grouped senses"><small>show grouped</small></a></h6> 
				<ul>
HTML;
		$html .= $this->_getIndividualSensesHtml($entry);
		$html .= '</ul></div>';
		return $html;
	}

	private function _getOrphanSensesHtml($entry) {
		/* Get any citations without senses */
		$html = "";
		$nonSenseSlipIds = models\sensecategories::getNonCategorisedSlipIds($entry->getId());
		if (count($nonSenseSlipIds)) {
			$slipData = array();
			$index = 0;
			foreach ($nonSenseSlipIds as $slipId) {
				$index++;
				$slipData[] = models\collection::getSlipInfoBySlipId($slipId);
			}
			$html .= $this->_getSlipListHtml($slipData, array("uncategorised"), "orp_" . $index);
		}
		return $html;
	}

	private function _getIndividualSensesHtml($entry) {
		/* Get citations for individual senses */
		$individualSenses = $entry->getIndividualSenses();
		$index = 0;
		foreach ($individualSenses as $sense => $slipIds) {
			$slipData = array();
			foreach ($slipIds as $slipId) {
				$index++;
				$slipData[] = models\collection::getSlipInfoBySlipId($slipId);
			}
			$html .= $this->_getSlipListHtml($slipData, $sense, "ind_".$index);
		}
		return $html;
	}

	private function _getGroupedSensesHtml($entry) {
		/* Get the citations with grouped senses */
		$index = 0;
		foreach ($entry->getUniqueSenseIds() as $slipId => $senseIds) {
			$slipData = array();
			$senseSlipIds = $entry->getSenseSlipIds($slipId);
			foreach ($senseSlipIds as $id) {
				$index++;
				$slipData[] = models\collection::getSlipInfoBySlipId($id);
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
				$translation = $this->_formatTranslation($row["translation"]);
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
						<td><a target="_blank" href="#" class="entryCitationTextLink"><small>view in text</small></td>
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
				$sense = new models\sense($senseId);
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

	private function _formatTranslation($html) {
  	$text = strip_tags($html);
  	$text = addslashes($text);
  	return $text;
	}

  public function writeBrowseTable($entryIds) {
    $tableBodyHtml = "<tbody>";
    foreach ($entryIds as $id) {
    	$entry = models\entries::getEntryById($id);
      $entryUrl = "?m=entries&a=view&id={$id}";
      $tableBodyHtml .= <<<HTML
        <tr>
          <td>{$entry->getHeadword()}</td>
          <td>{$entry->getWordclass()}</td>
          <td><a href="{$entryUrl}" title="view entry for {$entry->getHeadword()}">
            view entry
          </td>
        </tr>
HTML;
    }
    $tableBodyHtml .= "</tbody>";
    echo <<<HTML
        <table id="browseSlipsTable" data-toggle="table" data-pagination="true" data-search="true">
          <thead>
            <tr>
              <th data-sortable="true">Headword</th>
              <th data-sortable="true">Part-of-speech</th>
              <th>Link</th>
            </tr>
          </thead>
          {$tableBodyHtml}
        </table>
HTML;
  }

  private function _getSlipLink($result) {
		return <<<HTML
						<small>
                <a href="#" class="slipLink2"
                    data-toggle="modal" data-target="#slipModal"
                    data-auto_id="{$result["auto_id"]}"
                    data-headword="{$result["lemma"]}"
                    data-pos="{$result["pos"]}"
                    data-id="{$result["id"]}"
                    data-xml="{$result["filename"]}"
                    data-uri="{$result["uri"]}"
                    data-date="{$result["date_of_lang"]}"
                    data-title="{$result["title"]}"
                    data-page="{$result["page"]}"
                    data-resultindex="">
                      view
                </a>
            </small>
HTML;
  }

  private function _writeJavascript() {
  	echo <<<HTML
			<script>
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
			    var citationsLink = $(this);
			    var citationsContainerId = '#' + $(this).attr('data-type') + '_citations' + $(this).attr('data-index');
			    if ($(this).hasClass('hideCitations')) {
			      $(citationsContainerId).hide();
			      $(this).text('citations');
			      $(this).removeClass('hideCitations');
			      return;
			    }
			    var citationIndex = 0;
			    $(citationsContainerId + "> table > tbody > tr").each(function() {
			      citationIndex++;
			      var date = $(this).attr('data-date');
			      var html = '<span class="text-muted">' + date + '.</span> ';
			      var filename = $(this).attr('data-filename');
			      var wid = $(this).attr('data-id');
			      var tid = $(this).attr('data-tid');
			      var preScope  = $(this).attr('data-precontextscope');
			      var postScope = $(this).attr('data-postcontextscope');
			      var translation = $(this).attr('data-translation');
			      var tr = $(this);
			      var title = tr.prop('title');
			      var url = 'ajax.php?action=getContext&filename='+filename+'&id='+wid+'&preScope='+preScope;
			      url += '&postScope='+postScope+'&simpleContext=1';
			      $.getJSON(url, function (data) {
			        $('.spinner').show();
			        var preOutput = data.pre["output"];
			        var postOutput = data.post["output"];
			        var url = 'index.php?m=corpus&a=browse&id=' + tid + '&wid=' + wid; //title id and word id
			        tr.find('.entryCitationTextLink').attr('href', url); //add the link to text url
			        html += preOutput;
			        if (data.pre["endJoin"] != "right" && data.pre["endJoin"] != "both") {
			          html += ' ';
			        }
			        //html += '<span id="slipWordInContext">' + data.word + '</span>';
              html += '<mark>' + data.word + '</mark>'; // MM
			        if (data.post["startJoin"] != "left" && data.post["startJoin"] != "both") {
			          html += ' ';
			        }
			        html += postOutput;
			        if (translation) {
			          html += '<div><small><a href="#translation'+citationIndex+'" '; 
			          html += 'data-toggle="collapse" aria-expanded="false" aria-controls="#translation'+citationIndex+'">';
			          html += 'show/hide translation</a></small></div>';
			          html += '<div id="translation' + citationIndex + '" class="collapse"><small class="text-muted">'+translation+'</small></div>';
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
			</script>
HTML;
  }
}
