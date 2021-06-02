<?php

namespace views;

use models;

class corpus_browse
{
	private $_model;   // an instance of models\corpus_browse
	private $_ms;   // an instance of models\manuscript

	public function __construct($model) {
		$this->_model = $model;
	}

	public function show($action = null) {
		if ($this->_model->getType() == "ms") {
			$this->_ms = models\manuscripts::getMSById($this->_model->getId());
		}
		if ($action == "edit") {
			$this->_writeEditForm();
			return;
		}
		$user = models\users::getUser($_SESSION["user"]);
		echo <<<HTML
		<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">
HTML;
		if ($this->_model->getId()=="0") {
			echo <<<HTML
			  <li class="nav-item"><div class="nav-link active">viewing corpus</div></li>
		    <li class="nav-item"><a class="nav-link" href="?m=corpus&a=search&id=0">search corpus</a></li>
HTML;
			if ($user->getSuperuser()) {
				echo <<<HTML
			    <li class="nav-item"><a class="nav-link" href="?m=corpus&a=edit&id=0">add text</a></li>
HTML;
			}
			echo <<<HTML
				<li class="nav-item"><a class="nav-link" href="?m=corpus&a=generate&id=0">corpus wordlist</a></li>
HTML;
		}
		else {
			echo <<<HTML
			<li class="nav-item"><div class="nav-link active">viewing text #{$this->_model->getId()}</div></li>
		  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=search&id={$this->_model->getId()}">search text #{$this->_model->getId()}</a></li>
HTML;
			if ($user->getSuperuser()) {
				echo <<<HTML
			    <li class="nav-item"><a class="nav-link" href="?m=corpus&a=edit&id={$this->_model->getId()}">edit text #{$this->_model->getId()}</a></li>
HTML;
			}
			echo <<<HTML
			<li class="nav-item"><a class="nav-link" href="?m=corpus&a=generate&id={$this->_model->getId()}">text #{$this->_model->getId()} wordlist</a></li>
HTML;
		}
		echo <<<HTML
		  </ul>
HTML;
		if ($this->_model->getId() == "0") {
			$this->_showCorpus();
		}
		else {
			$this->_showText();
		}
		$this->_writeJavascript();
		if ($this->_ms) {   //a manuscript so generate the required code
			$this->_writeMSModal();
			$this->_writeMSJavascript();
		}
	}

	private function _writeEditForm() {
		$user = models\users::getUser($_SESSION["user"]);
		if (!$user->getSuperuser()) {
			$this->show();
			return;
		}
		echo <<<HTML
		<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">
HTML;
		if ($this->_model->getId()=="0") {
			echo <<<HTML
				<li class="nav-item"><a class="nav-link" href="?m=corpus&a=browse&id=0">view corpus</a></li>
				<li class="nav-item"><a class="nav-link" href="?m=corpus&a=search&id=0">search corpus</a></li>
				<li class="nav-item"><div class="nav-link active">adding text</div></li>
				<li class="nav-item"><a class="nav-link" href="?m=corpus&a=generate&id=0">corpus wordlist</a></li>
HTML;
		}
		else {
			echo <<<HTML
			  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=browse&id={$this->_model->getId()}">view text #{$this->_model->getId()}</a></li>
			  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=search&id={$this->_model->getId()}">search text #{$this->_model->getId()}</a></li>
			  <li class="nav-item"><div class="nav-link active">editing text #{$this->_model->getId()}</div></li>
				<li class="nav-item"><a class="nav-link" href="?m=corpus&a=generate&id={$this->_model->getId()}">text #{$this->_model->getId()} wordlist</a></li>
HTML;
		}
		echo <<<HTML
		</ul>
		<hr/>
HTML;

		if ($this->_model->getID() == "0") {
			$formHtml = $this->_getFormSubTextSectionHtml();
		} else if ($this->_model->getChildTextsInfo()) { //text has subTexts
			$formHtml = $this->_getFormMetadataSectionHtml() . $this->_getFormSubTextSectionHtml();
		} else if ($this->_model->getFilepath()) { //text has a filepath
			$formHtml = $this->_getFormMetadataSectionHtml() . $this->_getFormFilepathSectionHtml();
		} else {
			$formHtml = $this->_getFormMetadataSectionHtml() . $this->_getFormSubTextSectionHtml() . $this->_getFormFilepathSectionHtml();
		}
		echo <<<HTML
			<form id="corpusEdit" action="index.php?m=corpus&a=save&id={$this->_model->getID()}" method="post">
				{$formHtml}
				<button type="submit" class="btn btn-primary">save</button>
				<a href="?m=corpus&a=browse&id={$_GET["id"]}"><button type="button" class="btn btn-secondary">cancel</button></a>

			</form>

		<!-- check to see if a user tries to leave the page without saving changes -->
		<script>
			let formChanged = false;
			let corpusEdit = document.getElementById('corpusEdit');
			corpusEdit.addEventListener('change', () => formChanged = true);
			window.addEventListener('beforeunload', (event) => {
        if (formChanged) {
          event.returnValue = 'You have unsaved changes!';
        }
			});
		</script>
HTML;
	}

	private function _getFormMetadataSectionHtml() {
		$writersHtml = $this->_getWritersFormHtml();
		$levelHtml = <<<HTML
			<select name="textLevel" id="textLevel" class="form-control col-sm-4">
HTML;
		for ($i=1; $i<4; $i++) {
			$selected = $this->_model->getLevel() == $i ? "selected" : "";
			$levelHtml .= <<<HTML
				<option value="{$i}" {$selected}>{$i}</option>
HTML;
		}
		$levelHtml .= "</select>";
		$html = <<<HTML
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="textId">Text ID :</label>
						<input class="form-control col-sm-4" disabled type="text" name="textId" id="textId" value="{$this->_model->getID()}">	
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="textTtle">Title</label>
						<input class="form-control col-sm-4" type="text" name="textTitle" id="textTitle" value="{$this->_model->getTitle()}">
					</div>
					<div>
						<h4>Writers</h4>
							{$writersHtml}
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="textDate">Date</label>
						<input class="form-control col-sm-4" type="text" name="textDate" id="textDate" value="{$this->_model->getDate()}">
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="textLevel">Text Level</label>
						{$levelHtml}
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="textNotes">Text Notes</label>
						<textarea class="form-control col-sm-4" id="textNotes" name="textNotes" rows="10">{$this->_model->getNotes()}</textarea>
					</div>
HTML;
		return $html;
	}

	private function _getFormSubTextSectionHtml() {
		$prefix = ($this->_model->getID() == 0) ? "" : $this->_model->getID() . "-";
		$defaultLevel = $this->_model->getLevel() ? $this->_model->getLevel() : 3;
		$levelHtml = <<<HTML
			<select name="subTextLevel" id="subTextextLevel" class="form-control col-sm-4">
HTML;
		for ($i=1; $i<4; $i++) {
			$selected = $defaultLevel == $i ? "selected" : "";
			$levelHtml .= <<<HTML
				<option value="{$i}" {$selected}>{$i}</option>
HTML;
		}
		$levelHtml .= "</select>";
		$html = <<<HTML
				<div class="form-group row">
					<label class="col-sm-2 col-form-label" for="subTextId">SubText ID</label>
					{$prefix}<input class="form-control col-sm-4" type="text" name="subTextId" id="subTextId">
				</div>
				<div class="form-group row">
					<label class="col-sm-2 col-form-label" for="subTextTitle">SubText Title</label>
					<input class="form-control col-sm-4" type="text" name="subTextTitle" id="subTextTitle">
				</div>
				<div class="form-group row">
					<label class="col-sm-2 col-form-label" for="subTextDate">SubText Date</label>
					<input class="form-control col-sm-4" type="text" name="subTextDate" id="subTextDate">
				</div>
				<div class="form-group row">
					<label class="col-sm-2 col-form-label" for="subTextLevel">SubText Level</label>
					{$levelHtml}
				</div>
				<div class="form-group row">
					<label class="col-sm-2 col-form-label" for="textNotes">SubText Notes</label>
					<textarea class="form-control col-sm-4" id="subTextNotes" name="subTextNotes" rows="10"></textarea>
				</div>
HTML;
		return $html;
	}

	private function _getFormFilepathSectionHtml() {
		$html = <<<HTML
				<div class="form-group">
					<label for="filepath">Filepath</label>
					<input class="form-control" type="text" name="filepath" id="filepath" value="{$this->_model->getFilepath()}">
				</div>
HTML;
		return $html;
	}

	private function _showCorpus() {
		echo <<<HTML
			<table class="table">
				<tbody>
HTML;
		$texts = $this->_model->getTextList();
		foreach ($texts as $text) {
			$this->_writeRow($text);
		}
		echo <<<HTML
				</tbody>
			</table>
HTML;
	}

	private function _writeRow($text) {
		$writerHtml = $this->_formatWriters($text);
		$levelColours = array(1 => "gold", 2 => "silver", 3 => "bronze");
		$levelHtml = $text["level"] == 0 ? "" : <<<HTML
			<i class="fas fa-star {$levelColours[$text["level"]]}"></i>
HTML;
		echo <<<HTML
      <tr>
        <td>#{$text["id"]}</td>
        <td>{$levelHtml}</td>
        <td class="browseListTitle">
          <a href="?m=corpus&a=browse&id={$text["id"]}">{$text["title"]}</a>
        </td>
        <td>{$writerHtml}</td>
        <td>{$text["date"]}</td>
      </tr>
HTML;
	}

	private function _formatWriters($text) {
		$writersInfo = $text["writers"];
		if (empty($writersInfo)) {
			return;
		}
		$writerList = [];
		foreach ($writersInfo as $writerInfo) {
			$nickname = (empty($writerInfo["nickname"])) ? "" : " (" . $writerInfo["nickname"] . ")";
			$writerList[] = <<<HTML
            <a href="?m=writers&a=browse&id={$writerInfo["id"]}">
							{$writerInfo["forenames_en"]} {$writerInfo["surname_en"]}
						</a> {$nickname}
HTML;
		}
		return implode(", ", $writerList);
	}

	/**
	 * Generates alist of existing writers and an input field for a new writer ID
	 * @return string $html
	 */
	private function _getWritersFormHtml() {
		$html = "<ul>";
		$writerIds = $this->_model->getWriterIds();
		foreach($writerIds as $writerId) {
			$writer = new models\writer($writerId);
			$name = empty($writer->getForenamesGD()) || empty($writer->getSurnameGD())
				? $writer->getForenamesEN() . " " . $writer->getSurnameGD()
				: $writer->getForenamesGD() . " " . $writer->getSurnameGD();
			if (!empty($writer->getNickname())) {
				$name .= " - " . $writer->getNickname();
			}
			$html .= <<<HTML
				<li>{$name} ({$writerId})</li>
HTML;
		}
		$html .= <<<HTML
			</ul>
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="writerId">New writer ID</label>
				<input class="form-control col-sm-4" type="text" id="writerId" name="writerId">
			</div>
HTML;

		return $html;
	}

	private function _showText() {
		$textOutput = $this->_model->getTransformedText();
		$rightPanelHtml = "";
		if ($this->_ms) {     // manuscript specific panels
			$textOutput = $this->_formatMS($textOutput);
			$rightPanelHtml = <<<HTML
				<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">			
					<li class="nav-item"><a id="metaPanelSelect" class="link nav-link panel-link active">metadata</a></li>
					<li class="nav-item"><a id="wordPanelSelect" class="link nav-link panel-link">word info</a></li>			    
				  <li class="nav-item"><a id="diploPanelSelect" class="link nav-link panel-link">diplomatic</a></li>			
				  <li class="nav-item"><a id="imagePanelSelect" class="link nav-link panel-link">image</a></li>		  
				 </ul>
				 
				 <div id="metaPanel" class="panel">
				  {$this->_getMetaTableHtml()}
				 </div>
				 <div id="wordPanel" class="panel"><< please select a word</div>
				 <div id="diploPanel" class="panel"></div>
				 <div id="imagePanel" class="panel"><< please select a page</div>
HTML;
		} else {  // panels for non-manuscript texts

			if ($this->_model->getChildTextsInfo()) { // supertext so don't use metadata panel
				echo <<<HTML
					{$this->_getMetaTableHtml()}
HTML;
			} else {  // child text so populate metadata panel and write image panel
				$rightPanelHtml = <<<HTML
				<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">			
					<li class="nav-item"><a id="metaPanelSelect" class="link nav-link panel-link active">metadata</a></li>	
				  <li class="nav-item"><a id="imagePanelSelect" class="link nav-link panel-link">image</a></li>		  
				 </ul>
				 
				 <div id="metaPanel" class="panel">
					{$this->_getMetaTableHtml()}
				 </div>
				 <div id="imagePanel" class="panel"><< please select a page</div>	
HTML;
			}
		}
		echo <<<HTML
			<div class="row flex-fill" style="min-height: 0;">
				<div id="lhs" class="col-6 mh-100" style="overflow-y: scroll;">
					{$textOutput}
				</div>  <!-- end LHS -->
				<div id="rhs" class="col-6 mh-100" style="overflow-y: scroll;"> <!-- RHS panel -->
					{$rightPanelHtml}
				</div>  <!-- end RHS -->
			</div>  <!-- end row -->
HTML;
	}

	private function _getMetaTableHtml() {
		$html = <<<HTML
			<table class="table" id="meta" data-hi="{$_GET["id"]}">
				<tbody>
					<tr><td>title</td><td>{$this->_model->getTitle()}</td></tr>
					{$this->_getWritersHtml()}
					{$this->_getDateHtml()}
					{$this->_getLevelHtml()}
					{$this->_getNotesHtml()}
					{$this->_getParentTextHtml()}
					<!-- $this->_getMetadataLinkHtml()} -->
					{$this->_getChildTextsHtml()}
				</tbody>
			</table>
HTML;
		return $html;
	}

	/**
	 * Adds extra code required for the Manuscript view.
	 * @param $input the MS HTML
	 * @return string the formatted HTML
	 */
	private function _formatMS($input) {
		$output = <<<HTML
					<div>
	          <small><a href="#" onclick="$('.numbers').toggle();">[toggle numbers]</a></small>
	          <br>
					</div>
					{$input}
				
HTML;
		return $output;
	}

	private function _getLevelHtml() {
		if (!$this->_model->getLevel()) {
			return "";
		}
		$levelColours = array(1 => "gold", 2 => "silver", 3 => "bronze");
		$level = $this->_model->getLevel();
		$levelHtml = <<<HTML
			<i class="fas fa-star {$levelColours[$level]}"></i>
HTML;
		return "<tr><td>level</td><td>{$levelHtml}</td></tr>";
	}

	private function _getNotesHtml() {
		if (!$this->_model->getNotes()) {
			return "";
		}
		return "<tr><td>notes</td><td>{$this->_model->getNotes()}</td></tr>";
	}


	private function _getDateHtml() {
		if (!$this->_model->getDate()) {
			return "";
		}
		return "<tr><td>date</td><td>{$this->_model->getDate()}</td></tr>";
	}

	private function _getParentTextHtml() {
		$parentText = $this->_model->getParentText();
		$pid = $parentText->getId();
		if ($pid == "0") {
			return "";
		}
		$html = '<tr><td>parent text</td><td>';
		$html .= '<a href="?m=corpus&a=browse&id=' . $pid . '">';
		$html .= $parentText->getTitle();
		$html .= '</a></td></tr>';
		return $html;
	}

	private function _getWritersHtml() {
		if (!count($this->_model->getWriters())) {
			return "";
		}
		$html = '<tr><td>writers</td><td>';
		foreach ($this->_model->getWriters() as $writer) {
			$html .= '<a href="?m=writers&a=browse&id=' . $writer->getId() . '">';
			$html .= $writer->getForenamesEN() . ' ' . $writer->getSurnameEN();
			$html .= '</a>';
			$html .= ', ';
		}
		$html = rtrim($html);
		$html = trim($html, ",");
		$html .= '</td></tr>';
		return $html;
	}

	private function _getChildTextsHtml() {
		if (!count($this->_model->getChildTextsInfo())) {
			return "";
		}
		else {
			$levelColours = array(1 => "gold", 2 => "silver", 3 => "bronze");
			$html = '<tr><td>contents</td><td>';
			$html .= '<div class="list-group list-group-flush">';
			foreach ($this->_model->getChildTextsInfo() as $childId => $childInfo) {
				$html .= '<div class="list-group-item list-group-item-action">';
				$html .= '<i class="fas fa-star ' . $levelColours[$childInfo["level"]] . '"></i>' . ' #' . $childId .
					': <a href="?m=corpus&a=browse&id=' . $childId .'">' . $childInfo["title"];
				$html .= '</a></div>';
			}
			$html .= '</div></td></tr>';
		}
		return $html;
	}

	private function _getMetadataLinkHtml() {
		$textId = $this->_model->getId();
		$html = <<<HTML
			<tr>
				<td colspan="2">
					<small><a href="https://dasg.ac.uk/corpus/textmeta.php?text={$textId}&uT=y" target="_blank">more</a></small>
				</td>
			</tr>
HTML;
		return $html;
	}

	private function _writeMSModal() {
		echo <<<HTML
        <div class="modal fade" id="chunkModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"></h5>
              </div>
              <div class="modal-body">
                <div id="xmlView" style="display:none;"></div>
                <div id="textView"></div>
              </div>
              <div class="modal-footer">
                <button type="button" id="panelView" class="viewSwitch btn btn-success" data-dismiss="modal">panel view</button>
                <button type="button" id="toggleXmlView" class="btn btn-primary" data-action="xml">xml view</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
              </div>
            </div>
          </div>
        </div>
HTML;
	}

	private function _writeJavascript() {
		$scansFilepath = SCANS_FILEPATH;
		echo <<<HTML
    <script>
      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        if (hi = '{$_GET["wid"]}') {
          $('#'+hi).addClass('hi');
          document.getElementById(hi).scrollIntoView({behavior: 'smooth', block: 'center'})
        }
        
        $('#imagePanelSelect').on('click', function() {
			     $('.panel').hide();
			     $('#metaPanelSelect').removeClass('active');
			     $(this).addClass('active');	
			     $('#imagePanel').show();
				});
        
        $('#metaPanelSelect').on('click', function() {
			     $('.panel').hide();
			     $('#imagePanelSelect').removeClass('active');
			     $(this).addClass('active');	
			     $('#metaPanel').show();
				});
        
        $('.scanLink').on('click', function () {
          let src = '{$scansFilepath}' + $(this).attr('data-pdf');
          var html = '<embed width="100%" height="100%" src="' + src + '">';
          $('#imagePanel').html(html)
          $('.panel').hide();  
          $('#metaPanelSelect').removeClass('active');
					$('#imagePanelSelect').addClass('active');
					$('#imagePanel').show();
					
        });
        
        $('.externalLink').on('click', function () {
          let src = $(this).attr('data-url');
          var html = '<img id="pageImage" height="100%" src="' + src + '">';
          $('#rhs').html(html);
          $('#pageImage').zoomio({
            fadeduration: 500
          });
        });
      });
    </script>
HTML;
	}

	private function _writeMSJavascript() {
		echo <<<HTML
			<script>
					
				$(function() {
				   
				   $('.chunk').hover(
            function(){ $(this).css('text-decoration', 'underline'); },
            function(){ $(this).css('text-decoration', 'inherit'); }
           );
				   
				   $('#diploPanelSelect').on('click', function () {
				     $('.panel').hide();
				     $('#wordPanelSelect, #metaPanelSelect, #imagePanelSelect').removeClass('active');
				     $(this).addClass('active');				     
				     let id = '{$this->_ms->getId()}';
				     $.ajax({url: 'ajax.php?action=msGetEditionHtml&id='+id+'&mode=diplo',
				      action: "get", dataType: "html"
				     })
				     .done(function(html) {
				        $('#diploPanel').html(html);
				        $('#diploPanel').show();
				      });
				   });
				   
				   $('#wordPanelSelect').on('click', function() {
				     $('.panel').hide();
				     $('#diploPanelSelect, #metaPanelSelect, #imagePanelSelect').removeClass('active');
				     $(this).addClass('active');	
				     $('#wordPanel').show();
				   });
				   
				   $('#metaPanelSelect').on('click', function() {
				     $('.panel').hide();
				     $('#diploPanelSelect, #wordPanelSelect, #imagePanelSelect').removeClass('active');
				     $(this).addClass('active');	
				     $('#metaPanel').show();
				   });
				   
				   $('#imagePanelSelect').on('click', function() {
				     $('.panel').hide();
				     $('#diploPanelSelect, #metaPanelSelect, #wordPanelSelect').removeClass('active');
				     $(this).addClass('active');	
				     $('#imagePanel').show();
				   });
				   
				   $('.chunk').on('click', function () {
				     $('.hi').removeClass('hi');
				     $(this).addClass("hi");
				     let chunkId = $(this).attr('id');
				     let modal = $('#chunkModal');
				     $.ajax({
				      url: 'ajax.php?action=msPopulateModal&chunkId='+chunkId+'&id={$this->_ms->getId()}',
				      dataType: "json"
				     })
				     .done(function(data) {						       
				       let xml = '<pre>'+data.xml+'</pre>';
				       var html = getModalHtmlChunk(data, true);				       
				       html += '<ul>';
				       if (data.child) {
				         html += getChildChunkHtml(data.child, '');
				       }
				       html += '</ul>';
			         $('#wordPanel').html(html);  //add the html to the rhs panel
			         $('#diploPanelSelect, #metaPanelSelect, #imagePanelSelect').removeClass('active');
							 $('#wordPanelSelect').addClass('active');
							 $('.panel').hide();
			         $('#wordPanel').show();
				     })
				   });
				   
				   $(document).on('click', '#toggleXmlView', function () {
				     if ($(this).attr('data-action') == "xml") {
				       $(this).attr('data-action', 'text');
				       $(this).text('text view');
				       $('#textView').hide();
				       $('#xmlView').show();
				     } else {
				       $(this).attr('data-action', 'xml');
				       $(this).text('xml view');
				       $('#xmlView').hide();
				       $('#textView').show();
				     }
				   });
				   
				   //highlight abbreviations and ligatures
				   $(document).on('mouseover', '.mouseover', function() {
				     let id = $(this).attr('id');
				     $('.'+id).css('background-color', 'yellow');
				     $('#'+id).css('text-decoration','underline');
				   });
				  
				   //remove highlight from abbreviations and ligatures
				   $(document).on('mouseout', '.mouseover', function() {
				     let id = $(this).attr('id');
				     $('.'+id).css('background-color', 'inherit');
				     $('#'+id).css('text-decoration','inherit');
				   });
				   
				   $('.page').click(function(e){
					    e.stopImmediatePropagation();   //prevents outer link (e.g. word across pages) from overriding this one
					    var html = '';
					    var url = $(this).attr('data-facs');
					    var regex = /^((http[s]?|ftp):\/)?\/?([^:\/\s]+)((\/\w+)*\/)([\w\-\.]+[^#?\s]+)(.*)?(#[\w\-]+)?$/
					    var urlElems = regex.exec(url);
					    if (urlElems[3] == 'cudl.lib.cam.ac.uk') {  //complex case: write the viewer code
					      var paramElems = urlElems[6].split('/');
					      var mssNo = paramElems[0];
					      var pageNo = paramElems[1];
					      html = "<div style='position: relative; width: 100%; padding-bottom: 80%;'>";
					      html += "<iframe type='text/html' width='600' height='410' style='position: absolute; width: 100%; height: 100%;'";
					      html += " src='https://cudl.lib.cam.ac.uk/embed/#item="+mssNo+"&page="+pageNo+"&hide-info=true'";
					      html += " frameborder='0' allowfullscreen='' onmousewheel=''></iframe></div>";
					      $('#imagePanel').html(html);
					    }
					    else {    //simple case: just stick the url in an image tag for image viewer */
					      html = '<img id="msImage" src="' + url + '">';
								$('#imagePanel').html(html);
								$('#msImage').zoomio({
                  fadeduration: 500
                });
					    }
					    $('.panel').hide();
					    $('#diploPanelSelect, #metaPanelSelect, #wordPanelSelect').removeClass('active');
							$('#imagePanelSelect').addClass('active');
					    $('#imagePanel').show();
            });
				});
				
				function getChildChunkHtml(child, html) {
				  $.each(child, function(i, elem) {
				    html += getModalHtmlChunk(elem);
				    if (elem.child) {
				      html = getChildChunkHtml(elem.child, html);
				    }    
				  });
				  return html;
				}
				
				function getModalHtmlChunk(chunk, isTopLevel = false) {
				  var html;
				  if (isTopLevel) {
				    html = '<h1>' + chunk.headword + '</h1>';
				    html += '<ul>';
				  } else {
				    html = '<li><strong>' + chunk.headword + '</strong></li>';
				    html += '<ul>';
				  }			 	  
				  if (chunk.language != undefined) {
				    var langs = {la: "Latin", grk: "Greek", sco: "Scots", hbo: "Ancient Hebrew", jpa: "Aramaic", 
				      en: "English", und: "Unknown"};
				    let langCode = chunk.language["@attributes"]["lang"];
				    html += '<li>language: ' + langs[langCode] + '</li>';    
				  }		  
				  //get the hand info
				  if (chunk.hand != undefined) {
				    var hand = chunk.hand;
				    var handHtml = '';
				    if (chunk.handShift != undefined) {
				      hand = chunk.handShift;
				    }
				    if (hand.forename[0] != undefined) {
				      handHtml += hand.forename[0] + ' ';
				    }
				    if (hand.surname) {
				      handHtml += hand.surname[0] == undefined ? 'Anonymous (' + hand.id[0] + ')' : hand.surname[0];
				    }
				    html += '<li>scribe: <a href="?m=writers&a=browse&id=' + hand.writerId[0] + '" target="_blank">' + handHtml + '</a></li>'; 
				  }
				  if (chunk.pos) {
				    html += '<li>' + chunk.pos[0] + '</li>';
				  }
				  if (chunk.abbrevs.length) { //ligatures and abbreviations
				    html += '<li>scribal abbreviations and ligatures –</li><ul>'
				    $.each(chunk.abbrevs, function(i, abbr) {
				      let corresp = abbr.corresp ? abbr.corresp[0] : '';
				      html += '<li><a target="_blank" id="' + abbr.id[0] + '" class="mouseover" href="' + corresp + '">' + abbr.name[0] + '</a>: ';
				      html += abbr.note[0] + ' (' + abbr.cert[0] + ' certainty)</li>';
				    });
				    html += '</ul>';
				  }
				  if (chunk.partOfInsertion != undefined) {
				    html += '<li>part of insertion –</li><ul>';
				    html += '<li>[' + chunk.partOfInsertion.fullWord + '] (' + chunk.partOfInsertion.place[0] + ')</li></ul>';
				  }
				  if (chunk.supplied != undefined && chunk.supplied.length) {
				    html += '<li>text supplied by editor –</li><ul>';
				    $.each(chunk.supplied, function(i, supp) {
				      html += '<li>[' + supp[0] + '] (' + supp["@attributes"]["resp"] + ')</li>';
				    });
				    html += '</ul>';
				  }
				  if (chunk.insertions != undefined && chunk.insertions.length) {
				    html += '<li>insertions –</li><ul>';
				    $.each(chunk.insertions, function(i, insertion) {
				      html += '<li>[' + insertion[0] + '] (' + insertion["@attributes"]["hand"] + ', ';
				      html += insertion["@attributes"]["place"] + ') ';
				    });
				    html += '</ul>';
				  }
				  if (chunk.deletions != undefined && chunk.deletions.length) {
				    html += '<li>deletions –</li><ul>';
				    $.each(chunk.deletions, function(i, deletion) {
				      html += '<li>[' + deletion[0] + '] (' + deletion["@attributes"]["hand"] + ')';
				    });
				    html += '</ul>';
				  }
				  if (chunk.damaged != undefined && chunk.damaged.length) {
				    html += '<li>text supplied for lost writing surface –</li><ul>';
				    $.each(chunk.damaged, function(i, damage) {
				      html += '<li>[' + damage[0] + '] (' + damage["@attributes"]["resp"] + ', ';
				      html += damage["@attributes"]["cert"] + ' certainty)';
				    });
				    html += '</ul>';
				  }
				  if (chunk.obscure != undefined && chunk.obscure.length) {
				    html += '<li>obscured sections –</li><ul>';
				    $.each(chunk.obscure, function(i, obscure) {
				      html += '<li>[' + obscure[0] + '] (' + obscure["@attributes"]["resp"] + ', ';
				      html += obscure["@attributes"]["cert"] + ' certainty)';
				    });
				    html += '</ul>';
				  }
				  if (chunk.emendation) {
				    html += '<li>editorial emendation – \'' + chunk.emendation.sic + '\' to \'';
				    html += chunk.emendation.corr + '\' (' + chunk.emendation.resp[0] + ')</li>';
				  }
				  if (chunk.interpObscureSection) {
				    html += '<li>part of a section whose interpretation is obscure</li>'
				  }
				  if (chunk.obscureSection) {
				    let section = chunk.obscureSection;
				    html += '<li>part of an obscured section ('+ section.resp[0] + ', ' + section.cert[0] + ' certainty)</li>';
				  }
				  if (chunk.edil) {
				    html += '<li>eDil: <a target="_blank" href="' + chunk.edil[0] + '">' + chunk.lemma[0] + '</a></li>';
				  }
				  if (chunk.onomastics) {
				    html += '<li>' + chunk.onomastics.type; 
				    if (url = chunk.onomastics.url) {
				      html += ' (<a target="_blank" href="' + url[0] + '">info</a>)';
				    }
				    html += '</li>';
				  }
				  var complexText = chunk.complexFlag ? 'syntactically complex –' : 'syntactically simple';
				  html += '<li>' + complexText + '</li>';
				  if (chunk.complexFlag) {
				    html += '<ul>';
				  }
				  html += '</ul>';
				  return html;		    
				}
				
			</script>
HTML;
	}
}
