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
			case "edit":
				$this->_writeEditView();
				break;
			case "senses":
				$this->_writeSensesView();
				break;
			case "piles":
				$this->_writePilesView();
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
		models\pilecategories::writePileModal();
		$this->_writeJavascript();
	}

	private function _writeSubNav($type) {
		$listItemHtml = "";
		switch ($type) {
			case "edit":
				$listItemHtml = <<<HTML
					<li class="nav-item"><a class="nav-link" title="forms" href="?m=entries&a=view&type=forms&id={$this->_entry->getId()}">forms</a></li>
					<li class="nav-item"><a class="nav-link" title="senses" href="?m=entries&a=view&type=senses&id={$this->_entry->getId()}">senses</a></li>
					<li class="nav-item"><a class="nav-link" title="piles" href="?m=entries&a=view&type=piles&id={$this->_entry->getId()}">piles</a></li>
					<li class="nav-item"><a class="nav-link" title="slips" href="?m=entries&a=view&type=slips&id={$this->_entry->getId()}">slips</a></li>    	
					<li class="nav-item"><div class="nav-link active">edit</div></li>
HTML;
				break;
			case "senses":
				$listItemHtml = <<<HTML
					<li class="nav-item"><a class="nav-link" title="forms" href="?m=entries&a=view&type=forms&id={$this->_entry->getId()}">forms</a></li>
					<li class="nav-item"><div class="nav-link active">senses</div></li>
					<li class="nav-item"><a class="nav-link" title="piles" href="?m=entries&a=view&type=piles&id={$this->_entry->getId()}">piles</a></li>
					<li class="nav-item"><a class="nav-link" title="slips" href="?m=entries&a=view&type=slips&id={$this->_entry->getId()}">slips</a></li>    	
					<li class="nav-item"><a class="nav-link" title="edit" href="?m=entries&a=view&type=edit&id={$this->_entry->getId()}">edit</a></li>
HTML;
				break;
			case "piles":
				$listItemHtml = <<<HTML
					<li class="nav-item"><a class="nav-link" title="forms" href="?m=entries&a=view&type=forms&id={$this->_entry->getId()}">forms</a></li>
					<li class="nav-item"><a class="nav-link" title="senses" href="?m=entries&a=view&type=senses&id={$this->_entry->getId()}">senses</a></li>
					<li class="nav-item"><div class="nav-link active">piles</div></li>
					<li class="nav-item"><a class="nav-link" title="slips" href="?m=entries&a=view&type=slips&id={$this->_entry->getId()}">slips</a></li>  
					<li class="nav-item"><a class="nav-link" title="edit" href="?m=entries&a=view&type=edit&id={$this->_entry->getId()}">edit</a></li>
					  	
HTML;
				break;
			case "slips":
				$listItemHtml = <<<HTML
					<li class="nav-item"><a class="nav-link" title="forms" href="?m=entries&a=view&type=forms&id={$this->_entry->getId()}">forms</a></li>
					<li class="nav-item"><a class="nav-link" title="senses" href="?m=entries&a=view&type=senses&id={$this->_entry->getId()}">senses</a></li>
					<li class="nav-item"><a class="nav-link" title="piles" href="?m=entries&a=view&type=piles&id={$this->_entry->getId()}">piles</a></li>
			    <li class="nav-item"><div class="nav-link active">slips</div></li>	
					<li class="nav-item"><a class="nav-link" title="edit" href="?m=entries&a=view&type=edit&id={$this->_entry->getId()}">edit</a></li>
HTML;
				break;
			default:
				$listItemHtml = <<<HTML
					<li class="nav-item"><div class="nav-link active">forms</div></li>
					<li class="nav-item"><a class="nav-link" title="senses" href="?m=entries&a=view&type=senses&id={$this->_entry->getId()}">senses</a></li>
					<li class="nav-item"><a class="nav-link" title="piles" href="?m=entries&a=view&type=piles&id={$this->_entry->getId()}">piles</a></li>
					<li class="nav-item"><a class="nav-link" title="slips" href="?m=entries&a=view&type=slips&id={$this->_entry->getId()}">slips</a></li>   										
					<li class="nav-item"><a class="nav-link" title="edit" href="?m=entries&a=view&type=edit&id={$this->_entry->getId()}">edit</a></li>
HTML;
		}
		echo <<<HTML
			<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">			  
				{$listItemHtml}		    		
		  </ul>	
HTML;
	}

	private function _writeEditView() {
		echo <<<HTML
			<div>
        <h5>Edit:</h5>
        <div>
					{$this->_getSubclassHtml()}
					<div class="row form-group">
						<label class="form-label col-1" for="etymology">Etymology</label>
						<textarea id="etymology" class="form-control">{$this->_entry->getEtymology()}</textarea>
						<script>
	            CKEDITOR.replace('etymology', {
	              contentsCss: 'https://dasg.ac.uk/meanma/css/ckCSS.css',
	              customConfig: 'https://dasg.ac.uk/meanma/js/ckConfig.js'
	            });  
            </script>
					</div>
					<div class="row form-group">
						<label class="form-label col-1" for="notes">Notes</label>
						<textarea id="notes" class="form-control">{$this->_entry->getNotes()}</textarea>
						<script>
	            CKEDITOR.replace('notes', {
	              contentsCss: 'https://dasg.ac.uk/meanma/css/ckCSS.css',
	              customConfig: 'https://dasg.ac.uk/meanma/js/ckConfig.js'
	            });  
            </script>
					</div>
				</div>
				<button type="button" id="saveEntry" class="btn btn-primary">save</button>
				<a href="?m=entries&a=view&type=edit&id={$this->_entry->getId()}">
					<button type="button" class="btn btn-secondary">cancel</button>
				</a>
			</div>
HTML;
		echo $this->_writeEntryModal();
	}

	private function _getSubclassHtml() {
		$subclasses = $this->_entry->getSubclasses();
		if (!$subclasses) {
			return "";
		}
		$optionHtml = '<option value="">---</option>';
		foreach ($subclasses as $subclass) {
			$selected = $subclass == $this->_entry->getSubclass() ? "selected" : "";
			$optionHtml .= <<<HTML
				<option value="{$subclass}" {$selected}>{$subclass}</option>
HTML;
		}
		$subclassHtml = <<<HTML
      <div class="row form-group">
        <label class="form-label col-1" for="sublcass">Subclass</label>
        <select id="subclass" class="form-control col-2">
          {$optionHtml}
				</select>
			</div>
HTML;
		return $subclassHtml;
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
        <h5>Senses:</h5>
        {$this->_getSensesHtml()}
			</div>
HTML;
		$this->_writeSenseModal();
	}

	private function _writePilesView() {
		echo <<<HTML
				<div>
					<h5>Piles:</h5>
					{$this->_getPilesHtml()}
				</div>
HTML;
	}

	private function _writeSlipsView() {
		$tableBodyHtml = "<tbody>";
		$slipIds = $this->_entry->getSlipIds($this->_db);
		foreach ($slipIds as $slipId) {
			$slip = models\collection::getSlipBySlipId($slipId, $this->_db);
			$pageHtml = $slip->getPage() ? "p." . $slip->getPage() : "";
			$referenceHtml = str_replace("%p", $pageHtml, $slip->getText()->getReferenceTemplate());
			//get the citation in order of preference : draft > sense > form
			$citation = $slip->getCitationByType("draft")
				? $slip->getCitationByType("draft")
				: $slip->getCitationByType("sense");
			if (empty($citation)) {
				$citation = $slip->getCitationByType("form");
			}

			if (!empty($citation)) {
				$context = $citation->getContext();
				$tableBodyHtml .= <<<HTML
					<tr>
						<td>{$slip->getText()->getDate()}</td>
						<td>{$referenceHtml}</td>
						<td>{$context["html"]}  <strong>{$citation->getType()}</strong></td>
						<td>{$slip->getSlipLinkHtml()}</td>
					</tr>
HTML;
			}
		}
		$tableBodyHtml .= "</tbody>";

		echo <<<HTML
        <table id="browseSlipsTable" data-toggle="table" data-pagination="true" data-search="true">
          <thead>
            <tr>
              <th data-sortable="true">Date</th>
              <th data-sortable="true">Reference</th>
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
				$slipList = $this->_getSlipList($slipIds);
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

	private function _getSlipList($slipIds) {
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
					"date" => $row["date"],
					"date_internal" => $row["date_internal"],
					"title" => $row["title"],
					"page" => $row["page"]
				);
				$filenameElems = explode('_', $row["filename"]);
				//corpus slips have a filename, paper slips do not
				$textLink = $row["filename"] ? '<a target="_blank" href="#" class="entryCitationTextLink"><small>view in text</small>' : '';
				$emojiHtml = $row["filename"] ? "" : '<span data-toggle="tooltip" data-placement="top" title="paper slip">&#x1F4DD;</span>';
	/*			if (!$row["auto_id"]) {
					continue;             //bug fix
				}
	*/			$slipList .= <<<HTML
					<tr id="#slip_{$row["auto_id"]}" data-slipid="{$row["auto_id"]}"
						data-filename="{$row["filename"]}"
						data-id="{$row["id"]}"
						data-tid="{$row["tid"]}"
						data-date_display="{$row["date_display"]}"
						data-date_internal="{$row["date_internal"]}">
					<!--td data-toggle="tooltip"
						title="#{$filenameElems[0]} p.{$row["page"]}: {$row["date_display"]}"
						class="entryCitationContext"></td-->
					<td class="entryCitationContext"></td> 
					<td id="citationSlip_{$row["auto_id"]}" class="citationType"></td>
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
                    data-date_internal="{$result["date_internal"]}"
                    data-title="{$result["title"]}"
                    data-page="{$result["page"]}"
                    data-resultindex="">
                      view
                </a>
            </small>
HTML;
	}

	private function _getSensesHtml() {
		$html = "<ol id=\"senses\" class=\"senses_list\">";
		$unassignedHtml = "";
		$unassignedSlipIds = $this->_entry->getUnassignedToSense($this->_db);
		if (!empty($unassignedSlipIds)) {
			$slipList = $this->_getSlipList($unassignedSlipIds);
			$unassignedHtml = <<<HTML
				<span class="badge badge-secondary">unassigned</span>
				<small><a href="#" class="citationsLink" data-type="sense" data-index="_us">
								citations
						</a></small>
						<div id="sense_citations_us" data-loaded class="citation">
							<div class="spinner">
				        <div class="spinner-border" role="status">
				          <span class="sr-only">Loading...</span>
				        </div>
							</div>
							{$slipList}
						</div>
HTML;

		}
		$senses = $this->_entry->getTopLevelSenses($this->_db);
		$numSenses = count($senses);
		foreach ($senses as $i => $sense) {
			$html .= $this->_getSenseHtml($sense, $i, $numSenses, 0);
		}
		$html .= <<<HTML
			</ol>
			{$unassignedHtml}
HTML;
		$html .= <<<HTML
				<div class="row">
					<div class="col">
						<div class="mx-auto" style="width: 150px;">
              <a href="#" data-toggle="modal" data-target="#addSenseModal" title="add sense" id="addSense" class="btn btn-success">add sense</a>
            </div>
					</div>
				</div>
HTML;
		return $html;
	}

	/**
	 * Recursive method to generate required HTML for (sub)senses
	 * @param $sense
	 */
	private function _getSenseHtml($sense, $index, $numSenses, $depth) {
		$listTypes = array("a", "i", "A", "1");
		$listType = $listTypes[$depth];
		$nextDepth = $depth == count($listTypes) ? 0 : $depth+1;  //prevent overflow
		$subsenseHtml = "";
		//recursive call to assemble subsenseHtml
		$count = count($sense->getSubsenses());
		foreach ($sense->getSubsenses() as $i => $subsense) {
			$subsenseHtml .= $this->_getSenseHtml($subsense, $i, $count, $nextDepth);
		}
		$sid = $sense->getId();
		$slipIds = $sense->getCitationSlipIds();
		$citationHtml = "";
		if (!empty($slipIds)) {   //there are citations for this sense
			$slipList = $this->_getSlipList($slipIds);
			$citationHtml = <<<HTML
						<small><a href="#" class="citationsLink" data-type="sense" data-index="_s{$sid}">
								citations
						</a></small>
						<div id="sense_citations_s{$sid}" data-loaded class="citation">
							<div class="spinner">
				        <div class="spinner-border" role="status">
				          <span class="sr-only">Loading...</span>
				        </div>
							</div>
							{$slipList}
						</div>
HTML;
		}
		$moveUpHide = $index == 0 ? "d-none" : "";
		$moveDownHide = $index == $numSenses-1 ? "d-none" : "";
		$moveLeftHide = $sense->getSubsenseOf() ? "" : "d-none";
		$html = <<<HTML
				<li id="sense_{$sid}">
					<div class="dropdown show d-inline">
				    <a class="dropdown-toggle badge badge-success" href="#" id="dropdown_{$sid}" 
		          data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{$sense->getDefinition()}">
							{$sense->getLabel()}
		        </a> {$citationHtml} 
		        <a href="#" id="up-arrow-{$sid}" data-senseid="{$sid}" data-direction="up" class="swap-sense {$moveUpHide}">&uarr;</a>
		        <a href="#" id="down-arrow-{$sid}" data-senseid="{$sid}" data-direction="down" class="swap-sense {$moveDownHide}">&darr;</a>
		        <!--a href="#" id="left-arrow-{$sid}" data-senseid="{$sid}" data-direction="left" class="swap-sense {$moveLeftHide}">&larr;</a-->
			      <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown_{$sid}">
			        <li><a class="dropdown-item add-subsense" data-id="{$sid}" tabindex="-1" href="#">add subsense</a></li>
			      </ul>
					</div>
					<ol type="{$listType}" data-depth="{$depth}" id="subsenses_{$sid}" class="senses_list">{$subsenseHtml}</ol>
				</li>
HTML;
		return $html;
	}

	private function _getPilesHtml() {
		//orphaned (uncategorised) piles
		$orphanedPilesHtml = $this->_getOrphanPilesHtml();
		if ($orphanedPilesHtml != "") {
			$html = "<ul>" . $orphanedPilesHtml . "</ul>";
		}
		$html .= <<<HTML
			<div id="groupedPiles">
				<h6>Grouped Piles <a id="showIndividual" href="#" title="show individual piles"><small>show individual</small></a></h6> 
				<ul>
HTML;
		//grouped piles
		$html .= $this->_getGroupedPilesHtml();
		$html .= '</ul></div>';
		//individual piles
		$html .= <<<HTML
			<div id="individualPiles" class="hide">
				<h6>Individual Piles <a id="showGrouped" href="#" title="show grouped piles"><small>show grouped</small></a></h6> 
				<ul>
HTML;
		$html .= $this->_getIndividualPilesHtml();
		$html .= '</ul></div>';
		return $html;
	}

	private function _getOrphanPilesHtml() {
		/* Get any citations without piles */
		$html = "";
		$nonPileSlipIds = models\pilecategories::getNonCategorisedSlipIds($this->_entry->getId(), $this->_db);
		if (count($nonPileSlipIds)) {
			$slipData = array();
			$index = 0;
			foreach ($nonPileSlipIds as $slipId) {
				$index++;
				$slipData[] = models\collection::getSlipInfoBySlipId($slipId, $this->_db);
			}
			$html .= $this->_getSlipListHtml($slipData, array("uncategorised"), "orp_" . $index);
		}
		return $html;
	}

	private function _getSlipListHtml($slipData, $pileIds, $index) {
		$slipList = '<table class="table"><tbody>';
		foreach($slipData as $data) {
			foreach ($data as $row) {
				if (!$row["auto_id"]) {
					continue;             // no slip data so move on
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
					"date_display" => $row["date_display"],
					"date_internal" => $row["date_internal"],
					"title" => $row["title"],
					"page" => $row["page"]
				);
				$textLink = $row["filename"] ? '<a target="_blank" href="#" class="entryCitationTextLink"><small>view in text</small>' : '';
				$emojiHtml = $row["filename"] ? "" : '<span data-toggle="tooltip" data-placement="top" title="paper slip">&#x1F4DD;</span>';
				$slipList .= <<<HTML
					<tr id="#slip_{$row["auto_id"]}" data-slipid="{$row["auto_id"]}"
							data-filename="{$row["filename"]}"
							data-id="{$row["id"]}"
							data-tid="{$row["tid"]}"
							data-precontextscope="{$row["preContextScope"]}"
							data-postcontextscope="{$row["postContextScope"]}"
							data-translation="{$translation}"
							data-date_display="{$row["date_display"]}"
							data-date_internal="{$row["date_internal"]}">
						<!--td data-toggle="tooltip"
							title="#{$filenameElems[0]} p.{$row["page"]}: {$row["date_of_lang"]} : {$translation}"
							class="entryCitationContext"></td-->
						<td class="entryCitationContext"></td>
						<td id="citationSlip_{$row["auto_id"]}" class="citationType"></td>
						<td>{$emojiHtml}</td>		
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
		$pileString = "";
		if ($pileIds[0] == "uncategorised") {
			$pileString = <<<HTML
				<span data-toggle="modal" data-target="#pileModal" title="rename this pile" class="badge badge-secondary entryPile">
						uncategorised
					</span> 
HTML;
		} else {
			$pileIds = explode('|', $pileIds);
			foreach ($pileIds as $pileId) {
				$pile = new models\pile($pileId, $this->_db);
				$pileDescription = $pile->getDescription();
				$pileString .= <<<HTML
					<span data-toggle="modal" data-target="#pileModal" data-pile="{$pileId}" 
					data-pile-description="{$pile->getDescription()}" data-pile-name="{$pile->getName()}"
					data-title="{$pileDescription}" class="badge badge-success pileBadge">
						{$pile->getName()}
					</span> 
HTML;
			}
		}
		$html = <<<HTML
				<li>{$pileString} {$citationsHtml}</li>
HTML;
		return $html;
	}

	private function _getGroupedPilesHtml() {
		/* Get the citations with grouped piles */
		$index = 0;
		foreach ($this->_entry->getUniquePileIds($this->_db) as $slipId => $pileIds) {
			$slipData = array();
			$pileSlipIds = $this->_entry->getPileSlipIds($slipId);
			foreach ($pileSlipIds as $id) {
				$index++;
				$slipData[] = models\collection::getSlipInfoBySlipId($id, $this->_db);
			}
			$html .= $this->_getSlipListHtml($slipData, $pileIds, "grp_".$index);
		}
		return $html;
	}

	private function _getIndividualPilesHtml() {
		/* Get citations for individual piles */
		$individualPiles = $this->_entry->getIndividualPiles();
		$index = 0;
		foreach ($individualPiles as $pile => $slipIds) {
			$slipData = array();
			foreach ($slipIds as $slipId) {
				$index++;
				$slipData[] = models\collection::getSlipInfoBySlipId($slipId, $this->_db);
			}
			$html .= $this->_getSlipListHtml($slipData, $pile, "ind_".$index);
		}
		return $html;
	}

	private function _writeEntryModal() {
		$html = <<<HTML
        <div id="entrySavedModal" class="modal fade bd-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <h2>Entry Saved</h2>
                </div>
            </div>
          </div>
        </div>
HTML;
		return $html;
	}

	private function _writeSenseModal() {
		echo <<<HTML
        <div id="senseModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="senseModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Sense</h5>
                </div>
                <div class="modal-body">
                  <div class="form-group">
										<div class="row">		
											<label class="col-4" for="senseLabel">Label:</label>
											<input type="text" class="form-control col-7" id="senseLabel" name="senseLabel" autofocus/>             
										</div>
										<div class="row">		
											<label class="col-4" for="senseDefinition">Definition:</label>
											<textarea class="form-control col-7" id="senseDefinition" name="senseDefinition"></textarea>             
										</div>
                </div>
                <div class="modal-footer">
                  <input type="hidden" name="parentId" id="parentId" val=""/>
                  <input type="hidden" name="senseId" id="senseId" val=""/>
									<button type="button" class="btn btn-secondary" data-dismiss="modal">cancel</button>
                  <button type="button" id="saveSense" class="btn btn-primary">save</button>
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
				//enable tooltips
				$(function () {
          $('[data-toggle="tooltip"]').tooltip()
				});

				//senses
				
				//add sense
				$('#addSense').on('click', function () {
					$('#senseModal').modal();  
				});
				
				$('#saveSense').on('click', function () {
				  let url = "ajax.php";
				  let id = $('#senseId').val() == '' ? null : $('#senseId').val();
				  let label = $('#senseLabel').val();
				  let definition = $('#senseDefinition').val();
				  let parentId = $('#parentId').val();
				  let depth = parentId ? parseInt($('#subsenses_'+parentId).attr('data-depth')) : 0;
				  let listTypes = ["a", "i", "A", "1"];
				  let nextDepth = depth+1 == listTypes.length ? 0 : depth+1;
				  let data = {
				    action: "saveSense",
				    id: id,
				    entryId: '{$this->_entry->getId()}',
				    label: label,
				    definition: definition,
				    parentId: parentId
				  };				  
				  $.ajax({
					  dataType: "json",
					  method: "post",
					  url: url,
					  data: data
					})
					.done(function(response) {
					  let sid = response.id;	  
					  var html = '<li id="sense_'+sid+'"><div id="sense_'+sid+'" class="dropdown show d-inline emendation-action">';
				    html += '<a class="dropdown-toggle badge badge-success" href="#" id="dropdown_'+sid+'"'; 
				    html += ' title="'+definition+'"';
		        html += ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'+label+'</a>';
		        html += '&nbsp;<a href="#" id="up-arrow-'+sid+'" data-senseid="'+sid+'" data-direction="up" class="swap-sense">&uarr;</a>';
		        html += '&nbsp;<a href="#" id="down-arrow-'+sid+'" data-senseid="'+sid+'" data-direction="down" class="swap-sense d-none">&darr;</a>';
		        html += '&nbsp;<!--a href="#" id="left-arrow-'+sid+'" data-senseid="'+sid+'" data-direction="left" class="swap-sense">&larr;</a-->';
			      html += '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown_'+sid+'">';
			      html += '<li><a class="dropdown-item add-subsense" data-id="'+sid+'" tabindex="-1" href="#">add subsense</a></li>';
			      html += '</ul></div><ol type="'+listTypes[nextDepth]+'" data-depth="'+(nextDepth)+'" id="subsenses_'+sid+'"></ol></li>';
			      var swapId = null;
			      if (parentId) {
			        swapId = $('#subsenses_'+parentId).children().last().attr("id");
							$('#subsenses_'+parentId).append(html);     //subsense, so append to subsense list
						} else {
			        swapId = $('#senses').children().last().attr("id");
			        $('#senses').append(html);    //top level, so append to main sense list
			        $('#left-arrow-'+sid).addClass('d-none'); //and hide left arrow
						}
			      if (swapId) {
			        let elems = swapId.split('_');
			        swapId = elems[1];
			        $('#up-arrow-'+sid).removeClass('d-none');
			        $('#down-arrow-'+swapId).removeClass('d-none');
			      }
					  $('#senseModal').modal('hide');
			      $('#parentId').val('');   //reset the parentId
					})
				});
				
				//add a subsense
				$(document).on('click', '.add-subsense', function () {
				  let parentId = $(this).attr('data-id');
				  $('#parentId').val(parentId);
				  $('#senseModal').modal(); 
				});
				
				//swap a sense
				$(document).on('click', '.swap-sense', function () {
					let sid = $(this).attr('data-senseid');
					let senseLI = $('#sense_'+sid);
					let dir = $(this).attr('data-direction');
					$.getJSON('ajax.php?action=swapSense&dir='+dir+'&sid='+sid, function () {
					})
					.done(function (data) {
					  let swapId = data.id;
					  switch (dir) {  //switch on direction
					    case "up":
					      $(senseLI).insertBefore('#sense_'+swapId);
					      break;
				      case "down":
				        $(senseLI).insertAfter('#sense_'+swapId);
				        break;
			        case "left":
			          $(senseLI).insertAfter('#sense_'+swapId);
			          //if top level hide left arrow
			          if (data.parentId == null) {
			            $('#left-arrow-'+sid).addClass('d-none');
			          }
		            break;
					  }		  
						toggleArrows(sid, data.position);
						toggleArrows(swapId, data.swapPosition);
					})
				});
				
				//save the entry
				$('#saveEntry').on('click', function () {
				  var params = {
				    action: "saveEntry",
				    id: '{$this->_entry->getId()}',
				    subclass: $('#subclass').val(),
				    notes: CKEDITOR.instances['notes'].getData(),
				    etymology: CKEDITOR.instances['etymology'].getData()
				  } 
				  $.post('ajax.php', params, function () {
				  }) .done(function () {
				    $('#entrySavedModal').modal();   //show the saved messsage
				  })
				});
				
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
				  $('#groupedPiles').hide();
				  $('#individualPiles').show();
				  return false;
				});
				
				$('#showGrouped').on('click', function () {
				  $('#individualPiles').hide();
				  $('#groupedPiles').show();
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
			      return;
			    }
			    //data hasn't been loaded yet, so fetch it
			    $(citationsContainerId + "> table > tbody > tr").each(function() {
			      var tr = $(this);
			      var formsOnly = $("input[name='formsOptions']:checked").val() == "formsOnly" ? true : false;  
			      var slipId = $(this).attr('data-slipid');
			      var internalDate = $(this).attr('data-date_internal');
			      var displayDate = $(this).attr('data-date_display');
			      var html = '';
			      if (internalDate) {
			        html += '<a href="#" data-toggle="tooltip" title="'+displayDate+'" class="text-muted">' + internalDate + '.</a> ';
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
							      html += getCitationHtml("draft", data.draft, slipId); //no form or sense so write draft
							      tr.addClass("forms_draft");
							      if (formsOnly) {
							        tr.addClass('hide');
							      } else {
							        tr.removeClass('hide');
							      }
							    } else {
							      html += getCitationHtml("sense", data.sense, slipId); //no form so write sense
							      tr.addClass("forms_sense");
							      if (formsOnly) {
							        tr.addClass('hide');
							      } else {
							        tr.removeClass('hide');
							      }
							    }
							  } else {
							    html += getCitationHtml("form", data.form, slipId); //there is a form so write it							    
							    tr.addClass("forms_form");
							  }
				      } else if (type == "sense") {
				        if (!data.sense) {
							    if (!data.form) {
							      html += getCitationHtml("draft", data.draft, slipId); //no form or sense so write draft
							    } else {
							      html += getCitationHtml("form", data.form, slipId); //no sense so write form
							    }
							  } else {
							    html += getCitationHtml("sense", data.sense, slipId); //there is a sense so write it 
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
				
				function getCitationHtml(citationType, info, slipId = null) {		
				  html = '';
				  if (info.context != undefined) {
						html += info.context.html;
					}
				  if (info.referenceTemplate) { //auto generated reference
				    var pageHtml = 'x';
				    if (info.page) {    //temporary code until we work more on reference placeholders SB
				      pageHtml = 'p.' + info.page;
				    }
				    html += '<br><span class="text-muted">' + info.referenceTemplate.replace('%p', pageHtml) + '</span>';
				  } else if (info.reference) {  //manually entered reference
					  html += '<br>' + info.reference;
					}
					if (info.translation) {
				    let translation = info.translation;
					  html += getTranslationHtml(translation, info.cid);
					}
					//write the icon
					$('#citationSlip_'+slipId).html('<strong>'+citationType+'</strong>');
					return html;
				}
				
				//hide/show up/down arrows as appropriate
				function toggleArrows(sid, position) {				
					if (position['first']) {
					  $('#up-arrow-'+sid).addClass('d-none');		  
					} else {
					  $('#up-arrow-'+sid).removeClass('d-none');
					}
					if (position['last']) {
					  $('#down-arrow-'+sid).addClass('d-none');
					} else {
					  $('#down-arrow-'+sid).removeClass('d-none');
					}  
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