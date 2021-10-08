<?php

namespace views;
use models;

class slip
{
  private $_slip;   //an instance of the Slip class
	private $_citations;  //an array of citation objects; instance property to prevent unnecessary duplicate DB calls
  public function __construct($slip) {
    $this->_slip = $slip;
	  $this->_citations = $this->_slip->getCitations();
  }

  public function show($action) {
		switch ($action) {
			case "edit":
				$this->_writeEditForm();
				break;
		}
  }

  private function _writeEditForm() {
  	$user = models\users::getUser($_SESSION["user"]);
	  $locked = $this->_slip->getLocked();
		$lockedHtml = $user->getSuperuser() ? $this->_getLockedDiv($locked) : '';
  	$checked = $this->_slip->getStarred() ? "checked" : "";
  	$statusOptionHtml = "";
  	for ($i=1; $i<11; $i++) {
  		$selected = $i == $this->_slip->getSlipStatus() ? "selected" : "";
  		$statusOptionHtml .= <<<HTML
				<option value="{$i}" {$selected}>{$i}</option>
HTML;
	  }
    echo <<<HTML
				<div class="row flex-fill" style="min-height: 0;">
					<div id="lhs" class="col-6 mh-100" style="overflow-y: scroll; border: 1px solid red;">
						{$this->_writeCitations()}
						<!-- {$this->_writeCollocatesView()} -->
        </div>  <!-- end LHS -->
        
				<div id="rhs" class="col-6 mh-100" style="overflow-y: scroll; border: 1px solid green;"> <!-- RHS panel -->
	        <div class="form-group" id="slipChecked">
	          <div class="form-check form-check-inline">
	            <input class="form-check-input" type="checkbox" name="starred" id="slipStarred" {$checked}>
	            <label class="form-check-label" for="slipStarred">checked</label>
	          </div>
	        </div>
	        <div class="form-group row">
						<label for="slipStatus" class="col-form-label col-sm-1">Status:</label>
						<select id="slipStatus">
							{$statusOptionHtml}
						</select>
					</div>
	        <div>
	          <small><a href="#morphoSyntactic" id="toggleMorphoSyntactic" data-toggle="collapse" aria-expanded="true" aria-controls="morphoSyntactic">
	            show/hide morphosyntax
	          </a></small>
	        </div>
	        <div id="morphoSyntactic" class="collapse editSlipSectionContainer show">
	          <div class="form-group row">
	            <label class="col-form-label col-sm-1" for="slipHeadword">Headword:</label>
	            <input class="col-sm-3 form-control" type="text" id="slipHeadword" name="slipHeadword" value="{$this->_slip->getHeadword()}"> 
	          </div>
            {$this->_writePartOfSpeechSelects()}
				</div> <!-- end morphoSyntactic -->
				{$this->_writeSenseCategories()}
				<div style="margin: 0 0 10px 10px;">
          <small><a href="#notesSection" id="toggleNotes" data-toggle="collapse" aria-expanded="true" aria-controls="notesSection">
            show/hide notes
          </a></small>
        </div>
        <div id="notesSection" class="form-group collapse show">
          <label for="slipNotes">Notes:</label>
          <textarea class="form-control" name="slipNotes" id="slipNotes" rows="3">{$this->_slip->getNotes()}</textarea>
          <script>
            CKEDITOR.replace('slipNotes', {
              contentsCss: 'https://dasg.ac.uk/meanma/css/ckCSS.css',
              customConfig: 'https://dasg.ac.uk/meanma/js/ckConfig.js'
            });
          </script>
        </div>
        <div class="form-group">
          <div class="input-group">
            <input type="hidden" name="filename" value="{$_REQUEST["filename"]}">
            <input type="hidden" name="id" value="{$_REQUEST["wid"]}">
            <input type="hidden" id="locked" name="locked" value="{$locked}";
            <input type="hidden" id="auto_id" name="auto_id" value="{$this->_slip->getAutoId()}">
            <input type="hidden" id="pos" name="pos" value="{$_REQUEST["pos"]}">
            <input type="hidden" id="preContextScope" name="preContextScope" value="{$this->_slip->getPreContextScope()}">
            <input type="hidden" id="postContextScope" name="postContextScope" value="{$this->_slip->getPostContextScope()}">
            <input type="hidden" id="textId" name="textId" value="{$this->_slip->getTextId()}">
            <input type="hidden" name="action" value="save">
            {$lockedHtml}
            <div class="mx-2">
              <button name="close" class="windowClose btn btn-secondary">close</button>
              <button name="submit" id="savedClose" class="btn btn-primary">save</button>
             </div>
          </div>
        </div>
				{$this->_writeUpdatedBy()}
				{$this->_writeCitationEditModal()}
				{$this->_writeTranslationEditModal()}
        {$this->_writeFooter()}
			</div>  <!-- end RHS -->
		</div> <!-- end container -->
		{$this->_writeSavedModal()}
HTML;
    models\sensecategories::writeSenseModal();
  }

  private function _getLockedDiv($locked) {
  	$lockHide = $unlockHide = "";
	  if ($locked) {
		  $unlockHide = "d-none";
	  } else {
		  $lockHide = "d-none";
	  }

  	$html = <<<HTML
			<div>
        <a data-toggle="tooltip" title="Click to unlock" class="{$lockHide} lockBtn locked btn btn-large btn-danger" href="#">
        <i class="fa fa-lock" aria-hidden="true"></i></a>
        <a data-toggle="tooltip" title="Click to lock" class="{$unlockHide} lockBtn unlocked btn btn-large btn-success" href="#">
        <i class="fa fa-unlock" aria-hidden="true"></i></a>
			</div>
HTML;
  	return $html;
  }

  private function _writeUpdatedBy() {
    $email = $this->_slip->getLastUpdatedBy();
    if (!$email) {
      return;
    }
    $user = models\users::getUser($email);
    $time = $this->_slip->getLastUpdated();
    $html = <<<HTML
        <div>
            <p>Last updated {$time} by {$user->getFirstName()} {$user->getLastName()}</p>
        </div>
HTML;
    return $html;
  }

  private function _writeSavedModal() {
    $html = <<<HTML
        <div id="slipSavedModal" class="modal fade bd-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <h2>Slip Saved</h2>
                </div>
            </div>
          </div>
        </div>
HTML;
    return $html;
  }

	private function _writeCitationEditModal() {
		$html = <<<HTML
        <div id="citationEditModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="citationEditModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <h5>Adjust citation context</h5>
			              <div>
											<a class="updateContext btn-link" id="decrementPre"><i class="fas fa-minus"></i></a>
											<a class="updateContext btn-link" id="incrementPre"><i class="fas fa-plus"></i></a>
			              </div>
			              <span data-citationid="" data-citationcount="" data-precontextscope="" data-postcontextscope="" id="citationContext" class="citationContext">
			              </span>
			              <div>
			                <a class="updateContext btn-link" id="decrementPost"><i class="fas fa-minus"></i></a>
											<a class="updateContext btn-link" id="incrementPost"><i class="fas fa-plus"></i></a>
			              </div>
			              <div style="height: 20px;">
			                <a href="#" class="float-right" id="resetContext">reset context</a>
										</div>
										<div class="row">		
											<label class="col-2" for="citationType">Type:</label>
			                <select id="citationType" name="citationType" class="form-control col-3">
			                  <option value="long">long</option>
			                  <option value="short">short</option>
			                </select>               
										</div>
                </div>
                <div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
                  <button type="button" id="saveCitation" class="btn btn-primary">save</button>
								</div>
            </div>
          </div>
        </div>
HTML;
		return $html;
	}

	private function _writeTranslationEditModal() {
		$translationTypeHtml = '<select id="translationType" name="translationType" class="form-control col-1">';
		foreach (models\translation::$types as $translationType) {
			$translationTypeHtml .= <<<HTML
				<option value="{$translationType}">{$translationType}</option>
HTML;
		}
		$translationTypeHtml .= "</select>";
		$html = <<<HTML
        <div id="translationEditModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="translationEditModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <h5>Translation</h5>
			              <textarea class="form-control" name="citationTranslation" id="citationTranslation" rows="3">
										</textarea>
				            <script>
				              CKEDITOR.replace('citationTranslation', {
				                contentsCss: 'https://dasg.ac.uk/meanma/css/ckCSS.css',
				                customConfig: 'https://dasg.ac.uk/meanma/js/ckConfig.js'
				              });
				            </script>
					          <div>
					            <label for="translationType">Translation type:</label>
					            {$translationTypeHtml}
										</div>
                </div>
                <div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
                  <button type="button" id="saveTranslation" data-translationid="" data-citationid="" class="btn btn-primary">save</button>
								</div>
            </div>
          </div>
        </div>
HTML;
		return $html;
	}

  private function _writeFooter() {
    $pos = new models\partofspeech($_REQUEST["pos"]);
    $label = $_REQUEST["pos"] ? " ({$pos->getLabel()})" : "";
    $html = <<<HTML
        <div>
            slip ID:<span id="auto_id">{$this->_slip->getAutoId()}</span><br>
            POS tag:<span id="slipPOS">{$_REQUEST["pos"]}{$label}</span><br><br>
            filename: <span id="slipFilename">{$this->_slip->getFilename()}</span><br>
            id: <span id="wordId">{$_REQUEST["wid"]}</span><br>
        </div>

				{$this->_writeJavascript()}
HTML;
    return $html;
  }

  private function _writePartOfSpeechSelects() {
    $html = $this->_writeWordClassesSelect();
    $props = $this->_slip->getSlipMorph()->getProps();  //the morph data
    $relations = array("numgen", "case", "mode", "fin_person", "imp_person", "fin_number",
	    "imp_number", "status", "tense", "mood", "prep_mode", "prep_person", "prep_number", "prep_gender");
    $options["numgen"] = array("masculine singular", "feminine singular", "plural", "singular (gender unclear)",
      "feminine dual", "unclear");
    $options["case"] = array("nominative", "genitive", "dative", "unclear");
    $options["mode"] = array("unclear mode", "imperative", "finite", "verbal noun");
	  $options["imp_person"] = array("second person", "first person", "third person", "unclear person");
    $options["fin_person"] = array("unmarked person", "first person", "second person", "third person");
    $options["imp_number"] = array("singular", "plural", "unclear number");
    $options["fin_number"] = array("unmarked number", "singular", "plural");
    $options["status"] = array("unclear status", "dependent", "independent", "relative");
    $options["tense"] = array("unclear tense", "present", "future", "past", "conditional");
    $options["mood"] = array("active", "impersonal", "unclear mood");
    //prepositions
	  $options["prep_mode"] = array("basic", "augmented", "conjugated", "possessive", "unclear mode");
	  $options["prep_person"] = array("first person", "second person", "third person", "unclear person");
	  $options["prep_number"] = array("singular", "plural", "unclear number");
	  $options["prep_gender"] = array("masculine", "feminine", "unclear gender");
		//create the HTML options for each relation
	  $optionsHtml = array();
    foreach ($relations as $relation) {
    	$optionsHtml[$relation] = "";
    	foreach ($options[$relation] as $option) {
    		$selected = $option == $props[$relation] ? "selected" : "";
    		$optionsHtml[$relation] .= <<<HTML
					<option value="{$option}" {$selected}>{$option}</option>
HTML;
	    }
    }
    $nounSelectHide = $this->_slip->getWordClass() == "noun" ? "" : "hide";
    $verbSelectHide = $this->_slip->getWordClass() == "verb" ? "" : "hide";
    $prepSelectHide = $this->_slip->getWordClass() == "preposition" ? "" : "hide";
	  $impVerbSelectHide = $props["mode"] == "imperative" ? "" : "hide";
	  $finVerbSelectHide = $props["mode"] == "finite" ? "" : "hide";
    $verbalNounHide = $props["mode"] == "verbal noun" ? "hide" : "";
    $conjPosPrepHide = $props["prep_mode"] != "conjugated" && $props["prep_mode"] != "possessive" ? "hide" : "";
    $genderPrepHide = "hide";
    //show/hide gender dropdown
    if ($conjPosPrepHide != "hide") {
    	$genderPrepHide = ($props["prep_person"] != "third person") || ($props["prep_number"] != "singular") ? "hide" : "";
    }
    $html .= <<<HTML
        <div>
          <h5>Morphological information</h5>
            <div id="prepSelects" class="{$prepSelectHide}">
              <div class="row form-group form-inline">
                <label for="posPrepMode" class="col-form-label col-sm-1">Mode:</label>
                <select name="prep_mode" id="posPrepMode" class="form-control col-2">
                  {$optionsHtml["prep_mode"]}
                </select>
              </div>
              <span id="conjPosPrepOptions" class="{$conjPosPrepHide}">
                <div class="row form-group form-inline">
                  <label for="posPrepPerson" class="col-form-label col-sm-1">Person:</label>
                  <select name="prep_person" id="posPrepPerson" class="form-control col-2">
                    {$optionsHtml["prep_person"]}
                  </select>
                </div>
                <div class="row form-group form-inline">
                  <label for="posPrepNumber" class="col-form-label col-sm-1">Number:</label>
                  <select name="prep_number" id="posPrepNumber" class="form-control col-2">
                    {$optionsHtml["prep_number"]}
                  </select>
                </div>
                  <span id="genderPrepOptions" class="{$genderPrepHide}">
                    <div class="row form-group form-inline">
                      <label for="posPrepGender" class="col-form-label col-sm-1">Gender:</label>
                      <select name="prep_gender" id="posPrepGender" class="form-control col-2">
                        {$optionsHtml["prep_gender"]}
											</select>
										</div>
									</span>
								</span>
            </div>
            <div id="nounSelects" class="{$nounSelectHide}">
                <div class="row form-group form-inline">
	                <label for="posNumberGender" class="col-form-label col-sm-1">Number:</label>
	                <select name="numgen" id="posNumberGender" class="form-control col-2">
	                  {$optionsHtml["numgen"]}
	                </select>
	              </div>
	              <div class="row form-group form-inline">
	                <label for="posCase" class="col-form-label col-sm-1">Case:</label>
	                <select name="case" id="posCase" class="form-control col-2">
	                  {$optionsHtml["case"]}
	                </select>
	              </div>
            </div>
            <div id="verbSelects" class="{$verbSelectHide}">
                <div class="row form-group form-inline">
	                <label for="posMode" class="col-form-label col-sm-1">Mode:</label>
	                <select name="mode" id="posMode" class="form-control col-2">
	                  {$optionsHtml["mode"]}
	                </select>
	              </div>
                <span id="nonVerbalNounOptions" class="{$verbalNounHide}">
                  <span id="imperativeVerbOptions" class="{$impVerbSelectHide}">
                    <div class="row form-group form-inline">
	                    <label for="posImpPerson" class="col-form-label col-sm-1">Person:</label>
		                  <select name="imp_person" id="posImpPerson" class="form-control col-2">
		                    {$optionsHtml["imp_person"]}
		                  </select>
		                </div>
		                <div class="row form-group form-inline">
		                  <label for="posImpNumber" class="col-form-label col-sm-1">Number:</label>
		                  <select name="imp_number" id="posImpNumber" class="form-control col-2">
		                    {$optionsHtml["imp_number"]}
		                  </select>
		                </div>
	                </span>
	                <span id="finiteVerbOptions" class="{$finVerbSelectHide}">
	                  <div class="row form-group form-inline">
	                    <label for="posFinPerson" class="col-form-label col-sm-1">Person:</label>
		                  <select name="fin_person" id="posFinPerson" class="form-control col-2">
		                    {$optionsHtml["fin_person"]}
		                  </select>
		                </div>
		                <div class="row form-group form-inline">
		                  <label for="posFinNumber" class="col-form-label col-sm-1">Number:</label>
		                  <select name="fin_number" id="posFinNumber" class="form-control col-2">
		                    {$optionsHtml["fin_number"]}
		                  </select>
		                </div>
		                <div class="row form-group form-inline">
		                  <label for="posStatus" class="col-form-label col-sm-1">Status:</label>
		                  <select name="status" id="posStatus" class="form-control col-2">
		                    {$optionsHtml["status"]}
		                  </select>
		                </div>
		                <div class="row form-group form-inline">
	                    <label for="posTense" class="col-form-label col-sm-1">Tense:</label>
	                    <select name="tense" id="posTense" class="form-control col-2">
	                      {$optionsHtml["tense"]}
	                    </select>
	                  </div>
	                  <div class="row form-group form-inline">
	                    <label for="posMood" class="col-form-label col-sm-1">Mood:</label>
	                    <select name="mood" id="posMood" class="form-control col-2">
	                      {$optionsHtml["mood"]}
	                    </select>
	                  </div>
                  </span>
                </span>
            </div>
        </div>
HTML;
		return $html;
  }

  private function _writeWordClassesSelect() {
    $classes = $this->_slip->getWordClasses();
    $optionHtml = "";
    foreach ($classes as $class => $posArray) {
      $selected = $class == $this->_slip->getWordClass() ? "selected" : "";
      $optionHtml .= <<<HTML
        <option value="{$class}" {$selected}>{$class}</option>
HTML;
    }
    $html = <<<HTML
        <div id="wordClassSelect" class="form-group form-inline">
          <label for="wordClass" class="col-form-label"><h5>Part-of-speech:</h5></label>
          <select name="wordClass" id="wordClass" class="form-control col-3">
            {$optionHtml}
          </select>
        </div>
HTML;
    return $html;
  }

  private function _writeSenseCategories() {
  	$unusedSenses = $this->_slip->getUnusedSenses();
		$savedSenses = $this->_slip->getSenses();
    $dropdownHtml = '<option data-category="">-- select a category --</option>';
    foreach ($unusedSenses as $sense) {
    	$senseId = $sense->getId();
    	$senseName = $sense->getName();
      $dropdownHtml .= <<<HTML
        <option data-sense="{$senseId}" data-sense-description="{$sense->getDescription()}" 
          data-sense-name="{$senseName}" value="{$senseId}">{$senseName}</option>
HTML;
    }
    $savedCatHtml = "";
    foreach ($savedSenses as $sense) {
    	$senseId = $sense->getId();
    	$senseName = $sense->getName();
    	$senseDescription = $sense->getDescription();
      $savedCatHtml .= <<<HTML
        <li class="badge badge-success senseBadge" data-title="{$senseDescription}"
          data-toggle="modal" data-target="#senseModal" data-slip-id="{$this->_slip->getAutoId()}"
          data-sense="{$senseId}" data-sense-name="{$senseName}" data-sense-description="{$senseDescription}">
					{$senseName}
				</li>
HTML;
    }
    $html = <<<HTML
				<div style="margin-left: 10px;">
					<small><a href="#senses" id="toggleSenses" data-toggle="collapse" aria-expanded="true" aria-controls="senses">
            show/hide senses
          </a></small>
        </div>
        <div id="senses" class="editSlipSectionContainer collapse show">
          <h5>Sense Categories</h5>
          <div class="form-group row">
            <div class="col-md-3">
                  <label for="senseCategorySelect">Choose existing sense category:</label>
            </div>
            <div>
                <select id="senseCategorySelect">{$dropdownHtml}</select>
            </div>
            <div class="col-md-1">
                  <button type="button" class="form-control btn btn-primary" id="chooseSenseCategory">Add</button>
              </div>
          </div>
          <div class="form-group row">
              <div class="col-md-3">
                  <label for="senseCategory">Assign to new sense category:</label>
              </div>
              <div class="col-md-2">
									<label for="newSenseName">Name</label>
                  <input type="text" class="form-control" id="newSenseName">
              </div>
              <div class="col-md-3">
                  <label for="newSenseDefinition">Definition</label>
                  <input type="text" class="form-control" id="newSenseDefinition">
							</div>
              <div class="col-md-1">
                  <button type="button" class="form-control btn btn-primary" id="addSense">Add</button>
              </div>
          </div>
          <div>
            <ul id="senseCategories">
                {$savedCatHtml}
            </ul>
          </div>
        </div>
HTML;
    return $html;
  }

  private function _writeCitations() {
    $html = <<<HTML
			<div><h3>Citations</h3><ul id="citationList" style="list-style-type:none;">
HTML;
    $citations = $this->_citations;
    foreach ($citations as $citation) {
    	$cid = $citation->getId();
	    $transHtml = <<<HTML
				<span style="text-muted"><a href="#" class="transToggle" data-citationid="{$cid}"><small>show/hide translation(s)</small></a></span>
				<div id="transContainer_{$cid}" style="display: none;">
					<ul id="transList_{$cid}" style="list-style-type: none; margin:5px 10px;">
HTML;
    	if ($translations = $citation->getTranslations()) {
				foreach ($translations as $translation) {
					$tid = $translation->getId();
					$content = strip_tags($translation->getContent(), "<mark><b><strong><><i>");
					$transHtml .= <<<HTML
						<li xmlns="http://www.w3.org/1999/html">
							<span id="trans_{$tid}">{$content}</span> <em><span id="transType_{$tid}">({$translation->getType()})</span></em>&nbsp;
              <a href="#" id="editTrans_{$tid}" class="editTrans" data-translationid="{$tid}">edit</a>
						</li>
HTML;
				}
	    }
    	$transHtml .= <<<HTML
						<li>
							<a href="#" class="addTranslationLink" data-citationid="{$cid}" title="add translation" style="font-size: 15px;"><i class="fas fa-plus" style="color: #007bff;">
							</i></a>
						</li>
					</ul> <!-- close the transList -->
				</div>  <!-- close the transContainer -->
HTML;
			$html .= <<<HTML
				<li style="border-top: 1px solid gray;">
					<span id="citation_{$citation->getId()}">
						{$citation->getContext()["html"]}
					</span>
					<em>
						<span id="citationType_{$citation->getId()}">
							({$citation->getType()})
						</span>
					</em>
					<a href="#" class="editCitation" data-citationid="{$citation->getId()}" data-toggle="modal" data-target="#citationEditModal">edit</a>
				</li>
				<li>{$transHtml}</li>
HTML;
    }
    $html .= <<<HTML
				</ul></div>
	      <div class="col-1">
					<a href="#" class="addCitationLink" data-citationid="-1" data-toggle="modal" data-target="#citationEditModal" title="add citation" style="font-size: 30px;">
						<i class="fas fa-plus" style="color: #007bff;"></i>
					</a>
				</div>
HTML;
    return $html;
  }

	private function _writeCollocatesView() {
		$handler = new models\xmlfilehandler($this->_slip->getFilename());
		$preScope = $this->_slip->getPreContextScope();
		$postScope = $this->_slip->getPostContextScope();
		$context = $handler->getContext($this->_slip->getId(), $preScope, $postScope, true, false);

		$contextHtml = $context["pre"]["output"];
		if ($context["pre"]["endJoin"] != "right" && $context["pre"]["endJoin"] != "both") {
			$contextHtml .= ' ';    //  <div style="display:inline;">
		}
		$contextHtml .= <<<HTML
			<span>{$context["word"]}</span>
HTML;
		if ($context["post"]["startJoin"] != "left" && $context["post"]["startJoin"] != "both") {
			$contextHtml .= ' ';  //  <div style="display:inline;">
		}
		$contextHtml .= $context["post"]["output"];
		$html = <<<HTML
            <div id="slipCollocatesContainer" class="hide editSlipSectionContainer">
              <div class="floatRight">
                <a class="btn btn-success" href="#" id="showCitationView">citation view</a>
              </div>
              <h5>Tag citation collocates</h5>
              <span class="citationContext">
                {$contextHtml}
              </span>
            </div>
HTML;
		return $html;
	}

  private function _writeJavascript() {
    $html = <<<HTML
        <script>  
          $(function () {        
            
            //hide/show translation container
            $(document).on('click', '.transToggle', function () {
              let cid = $(this).attr('data-citationid');
              $('#transContainer_'+cid).toggle();
            });
            
            //populate editCitation modal on button click
            $(document).on('show.bs.modal', '#citationEditModal', function (event) {
              var modal = $(this);
              var editLink = $(event.relatedTarget);
              let cid = editLink.attr('data-citationid');
              let slipId = {$this->_slip->getAutoId()};
              $.getJSON('ajax.php?action=loadCitation&id='+cid+'&slipId='+slipId)
              .done(function(data) {
                $('#citationContext').attr('data-precontextscope', data.preScope);
                $('#citationContext').attr('data-postcontextscope', data.postScope);
                $('#citationContext').attr('data-citationid', data.id);
                $('#citationContext').html(data.context['html']);
              });
            });
            
            //save the citation from the modal
            $('#saveCitation').on('click', function() {
              let context = $('#citationContext');
              let cid = context.attr('data-citationid');
              let html = context.html();
              let preScope = context.attr('data-precontextscope');
              let postScope = context.attr('data-postcontextscope');
              let type = $('#citationType').val();      
              $.ajax('ajax.php?action=saveCitation&id='+cid+'&preScope='+preScope+'&postScope='+postScope+'&type='+type)
              .done(function () {
                //check if citation is already in list
                if ($('#citation_'+cid).length) {   //citation existis so update it 
                  $('#citation_'+cid).html(html);
                  $('#citationType_'+cid).html('('+type+')');
                } else {                           //citation does not yet exist so add it
                    var citHtml = '<li><span id="citation_'+cid+'">'+html+'</span>';
                    citHtml += '<em><span id="citationType_'+cid+'">&nbsp;('+type+')&nbsp;</span></em>';
                    citHtml += '<a href="#" class="editCitation" data-citationid="'+cid+'" data-toggle="modal" data-target="#citationEditModal">edit</a>';
                    citHtml += '</li>';
                    $('#citationList').append(citHtml);
                }     
                $('#citationEditModal').modal('hide');
              });
            });
            
/*            
            //save translation on focus out from translation CKEditor
            CKEDITOR.instances['slipTranslation'].on("blur", function() {
              saveTranslation();  
						});
 */           
            //add translation
            $('.addTranslationLink').on('click', function () {
              let citationId = $(this).attr('data-citationid');
              $.getJSON('ajax.php?action=createTranslation&citationId='+citationId)
              .done(function(data) {
                $('#saveTranslation').attr('data-citationid', citationId);
                $('#saveTranslation').attr('data-translationid', data.id);
                //append a placeholder to the citation's translation list 
                var html = '<li><span id="trans_'+data.id+'"></span> <em><span id="transType_'+data.id+'"></span></em>&nbsp;';
                html += '<a href="#" id="editTrans_'+data.id+'" class="editTrans" data-translationid="'+data.id+'">edit</a>';
                $('#transList_'+citationId).append(html);
                CKEDITOR.instances.citationTranslation.setData(''); //clear the translation content for new empty translation
								$('#translationEditModal').modal('show');
								return false;
							});
            });
          
            $(document).on('click', '.editTrans', function () {
              let tid = $(this).attr('data-translationid');  
              $.getJSON('ajax.php?action=loadTranslation&id='+tid)
              .done(function (data) {
                  //set the IDs required for save
                $('#saveTranslation').attr('data-citationid', data.cid);
                $('#saveTranslation').attr('data-translationid', tid);
                  //update the content html
                CKEDITOR.instances.citationTranslation.setData(data.content);
                  //update the translationType select
                $('#translationType').val(data.type);
                $('#translationEditModal').modal('show');
              });
            });
    
            //save translation
            $('#saveTranslation').on('click', function() {  
              let citationId = $(this).attr('data-citationid');    
              let translationId = $(this).attr('data-translationid');
              let content = CKEDITOR.instances.citationTranslation.getData();
              let type = $('#translationType').val();
              //update the translation info in the slip edit list
              $('#trans_'+translationId).html(content);
              $('#transType_'+translationId).html('('+type+')');
              $('#translationEditModal').modal('hide');
              //update the database 
              let params = {
                url: 'ajax.php',
                method: 'post',
                data: {
                  action: 'saveTranslation',
                  citationId: citationId,
	                translationId: translationId,
	                content: content,
	                type: type
                }
              }
              $.ajax(params)    
            });
            
            //load translation
            $(document).on('click', '.translationLink', function () {
              let tid = $(this).attr('data-tid');
              $('#slipTranslation').attr('data-translationid', tid);
              loadTranslation(tid);
            });
            
            //add citation 
    /*        $('.addCitationLink').on('click', function () {
              $.getJSON('ajax.php?action=createCitation&slipId={$this->_slip->getAutoId()}')
              .done(function(data) {
                let citationId = data.id;
                $('#citationContext').attr('data-citationid', citationId);
					      $('#citationContext').attr('data-precontextscope', data.prescope);
					      $('#citationContext').attr('data-postcontextscope', data.postscope);
					      $('#preContextScope').val(data.prescope);
					      $('#postContextScope').val(data.postscope);
                var citationCount = $('#citationContext').attr('data-citationcount');
                citationCount++;
                $('#citationContext').attr('data-citationcount', citationCount);
                $('#citationContext').attr('data-citationid', citationId);
                updateCitation(data);          
                  //write the citation badge
                html = '<li class="list-group-item d-flex justify-content-between align-items-center" style="border: none;background-color: #efefef;">';
								html += '<a href="#" data-cid="'+citationId+'" class="citationLink">';
								html += '<span class="badge badge-primary badge-pill">'+citationCount+'</span></a></li>';
                $('#citationLinks').append(html);
                createTranslation(citationId);
                return false;
              });      
            });
     */       
            //update the citation based on a citationLink click
      /*      $(document).on('click', '.citationLink', function () {
              let citationId = $(this).attr('data-cid');
              $('#citationContext').attr('data-citationid', citationId);
              $.getJSON('ajax.php?action=loadCitation&id='+citationId)
              .done(function(data) {
                updateCitation(data);		
                if (data.translationCount) {
                    //add the translation links
                  let transIds = data.translationIds.split('|'); 
                  var linkHtml = "";
                  for (var i=0; i<data.translationCount; i++) {
                    let index = i+1;
                    linkHtml += '<li class="list-group-item d-flex justify-content-between align-items-center" style="border:none; background-color: white;">';
										linkHtml += '<a href="#" data-tid="'+transIds[i]+'" class="translationLink">';
										linkHtml += '<span class="badge badge-primary badge-pill">'+index+'</span></a></li>';
                  }
                  $('#translationLinks').html(linkHtml);
                    //add the first translation content to the textarea
                  CKEDITOR.instances.slipTranslation.setData(data.firstTranslationContent);
                    //set the first translation type
                  $('#translationType').val(data.firstTranslationType);
                } else {
                  addTranslationLink();
                }
              });
              return false;
            });
        */    
            /*
              Increment and Decrement button handlers - update the context  
             */
            $('.updateContext').on('click', function () {         
					    var preScope = $('#citationContext').attr('data-precontextscope');
					    var postScope = $('#citationContext').attr('data-postcontextscope');
					    var filename = $('#slipFilename').text();
					    var id = $('#wordId').text();
					    switch ($(this).attr('id')) {
					      case "decrementPre":
					        preScope--;
					        if (preScope == 0) {
					          $('#decrementPre').addClass("disabled");
					        }
					        break;
					      case "incrementPre":
					        preScope++;
					        $('#decrementPre').removeClass("disabled");
					        break;
					      case "decrementPost":
					        postScope--;
					        if (postScope == 0) {
					          $('#decrementPost').addClass("disabled");
					        }
					        break;
					      case "incrementPost":
					        postScope++;
					        $('#decrementPost').removeClass("disabled");
					        break;
					    }
					    $('#citationContext').attr('data-precontextscope', preScope);
					    $('#citationContext').attr('data-postcontextscope', postScope);
					    $('#preContextScope').val(preScope);
					    $('#postContextScope').val(postScope); 
					    writeCitationContext(filename, id);
					  });
					 }); 
          
		        //update the citation context on click of token
		        $(document).on('click', '.contextLink',  function () {
		          $(this).tooltip('hide')
		          var filename = $('#slipFilename').text();
              var id = $('#wordId').text();
		          var preScope = $('#preContextScope').val();
		          var postScope = $('#postContextScope').val();		          
		          if ($(this).hasClass('pre')) {
		            preScope = $(this).attr('data-position');
		          } else {
		            postScope = $(this).attr('data-position');
		          }		          
		          $('#citationContext').attr('data-precontextscope', preScope);
					    $('#citationContext').attr('data-postcontextscope', postScope);
					    $('#preContextScope').val(preScope);
					    $('#postContextScope').val(postScope);
					    writeCitationContext(filename, id);
		        });
		        
		        //reset the context
		        $('#resetContext').on('click', function () {
              let slipId = {$this->_slip->getAutoId()}
              let filename = '{$this->_slip->getFilename()}';
              let id = '{$this->_slip->getId()}';
              let type = $('#citationType').val();
              var preScope = {$this->_slip->getScopeDefault()};
              var postScope = {$this->_slip->getScopeDefault()};        
              $.getJSON("ajax.php?action=getContext&slipId="+slipId+"&type="+type+"&preScope="+preScope+"&postScope="+postScope, function (data) {
					      //handle reaching the start/end of the document
					      if (data.prelimit) {
					        preScope = data.prelimit;
					      } 
					      if (data.postlimit) {
					        postScope = data.postlimit;
					      } 
					    })
					      .done(function () {
					         $('#citationContext').attr('data-precontextscope', preScope);
					         $('#citationContext').attr('data-postcontextscope', postScope);
					         $('#preContextScope').val(preScope);
					         $('#postContextScope').val(postScope);
					         writeCitationContext(filename, id);
					      });
		        });
		        
						//lock slip functionality
            $('.lockBtn').on('click', function (e) {
              e.preventDefault();
              $(this).addClass('d-none');
              $(this).siblings().removeClass('d-none');
              if ($(this).hasClass('unlocked')) {
                $('#locked').val('1');
              } else {
                $('#locked').val('0');
              }  
            });
            
            $('#showCitationView').on('click', function () {
              $('#slipCollocatesContainer').hide();
              $('#slipContextContainer').show();
            });

            $('#showCollocatesView').on('click', function () {
              $('#slipContextContainer').hide();
              $('#slipCollocatesContainer').show();
            });

            /*
              Show the collocate dropdown
             */
            $('.collocateLink').on('click', function () {
              $('.dropdown-item').removeClass('disabled');  //clear any previous entries
              var wordId = $(this).parent().attr('data-wordid');
              var filename = '{$this->_slip->getFilename()}';
              var url = 'ajax.php?action=getGrammarInfo&id='+wordId+'&filename='+filename;
              $.getJSON(url, function(data) {
                $('.collocateHeadword').text(data.lemma);
                if (data.grammar) {
                  var id = data.grammar.replace(' ', '_') + '_' + wordId;
                  $('#'+id).addClass('disabled');
                }
              });
            });

            /*
              Save the collocate grammar info
             */
            $('.collocateGrammar').on('click', function () {
              var wordId = $(this).parents('div.collocate').attr('data-wordid');
              $(this).parent().siblings('.collocateLink').addClass('existingCollocate');
              var filename = '{$this->_slip->getFilename()}';
              var headwordId = $('#slipWordInContext').attr('data-headwordid');
              var slipId = '{$this->_slip->getAutoId()}';
              var url = 'ajax.php?action=saveLemmaGrammar&id='+wordId+'&filename='+filename;
              url += '&headwordId='+headwordId+'&slipId='+slipId+'&grammar='+$(this).text();
              $.getJSON(url, function(data) {
                $('.collocateHeadword').text(data.lemma);
              });
            });

            /**
            * Senses
						*/  
            $("#chooseSenseCategory").on('click', function () {
              var elem = $( "#senseCategorySelect option:selected" );
              var sense = elem.text();
              if (!elem.attr('data-sense')) {
                return false;
              }
              var senseId = elem.attr('data-sense');
              var senseName = elem.attr("data-sense-name");
              var senseDescription = elem.attr('data-sense-description');
              var html = '<li class="badge badge-success senseBadge" data-sense="' + senseId + '"';
              html += ' data-toggle="modal" data-target="#senseModal"';
              html += ' data-title="' + senseDescription +  '" data-sense-name="' + senseName + '">' + sense + '</li>';
              $('#senseCategories').append(html);
              elem.remove();
              var data = {action: 'saveSlipSense', slipId: '{$this->_slip->getAutoId()}',
                senseId: senseId}
              $.post("ajax.php", data, function (response) {
                console.log(response);        //TODO: add some response code on successful save
              });
            });

            $(document).on('click', '#addSense', function () {
              var newSenseName = $('#newSenseName').val();
              var newSenseDefinition = $('#newSenseDefinition').val();
              if (newSenseName == "") {
                return false;
              }
              $('#newSenseName').val('');
              $('#newSenseDefinition').val('');
              var data = {action: 'addSense', slipId: '{$this->_slip->getAutoId()}',
                name: newSenseName, description: newSenseDefinition, entryId: '{$this->_slip->getEntryId()}'
              }
              $.getJSON("ajax.php", data, function (response) {
                var html = '<li class="badge badge-success senseBadge" data-sense="' + response.senseId + '"';
                html += ' data-title="' + response.senseDescription +'"';
                html += ' data-slip-id="{$this->_slip->getAutoId()}"';
                html += ' data-sense-name="' + newSenseName + '" data-sense-description="' + newSenseDefinition + '"';
                html += ' data-toggle="modal" data-target="#senseModal"';
                html += '>' + newSenseName + '</li>';
                $('#senseCategories').append(html);
              });
            });

            /*
              ** Change of wordclass or headword
             */
            $('#wordClass,#slipHeadword').on('change', function() {
              let check = confirm('Changing the headword and/or wordclass will remove any senses. Are you sure you want to proceed?');
              let previousHeadword = '{$this->_slip->getHeadword()}';
              let previousWordclass = '{$this->_slip->getWordClass()}';        
              if (!check) {
                $('#slipHeadword').val(previousHeadword)
                $('#wordClass').val(previousWordclass);
                return;
              }
              let wordclass = $('#wordClass').val();
              let headword = $('#slipHeadword').val();
              var changedField = 'wordclass';   //just a default, used for message in issues
              var changedValue = wordclass;
              if (headword != previousHeadword) {
                changedField = 'headword';
                changedValue = headword;
              }
              switch (wordclass) {
                case "verb":
                  $('#verbSelects').show();
                  $('#nonVerbalNounOptions').show();
                  $('#nounSelects').hide();
                  $('#prepSelects').hide();
                  break;
                case "noun":
                  $('#nounSelects').show();
                  $('#verbSelects').hide();
                  $('#prepSelects').hide();
                  break;
                case "preposition":
                  $('#prepSelects').show();
                  $('#nounSelects').hide();
                  $('#verbSelects').hide();
                  break;
                default:
                  $('#nounSelects').hide();
                  $('#verbSelects').hide();
                  $('#prepSelects').hide();
              }
              //update the sense categories 
              $('.senseBadge').remove();
              $('#senseCategorySelect').empty();
              $('#senseCategorySelect').append('<option data-category="">-- select a category --</option>');
              var url = 'ajax.php?action=getSenseCategoriesForNewWordclass';
              url += '&filename={$this->_slip->getFilename()}&id={$this->_slip->getId()}&auto_id={$this->_slip->getAutoId()}';
              url += '&pos={$this->_slip->getPOS()}&headword=' + headword + '&wordclass=' + wordclass;
              $.getJSON(url, function (data) {
                  $.each(data, function (index, sense) {
                    var html = '<option data-sense="' + index + '" data-sense-description="' + sense.description + '"';
                    html += ' data-sense-name="' + sense.name + '" value="' + index + '">' + sense.name + '</option>';
                    $('#senseCategorySelect').append(html);
                  });
              })
              .done(function () {   //raise and save an issue with the slip and headword/wordclass information
                    var params = {
                      description: 'The ' + changedField + ' for §{$this->_slip->getAutoId()} has been changed to <strong>' + changedValue + '</strong>',
                      userEmail: '{$_SESSION["user"]}', status: 'new', updated: ''}; 
                    $.getJSON('ajax.php?action=raiseIssue', params, function(response) {
                      console.log(response.message);
                  });
               });
            });

            $('#posMode').on('change', function() {
              var mode = $(this).val();
              if(mode == "verbal noun" || mode == "unclear mode") {
                $('#nonVerbalNounOptions').hide();
              } else {
                $('#nonVerbalNounOptions').show();
              }
              if (mode == "imperative") {
                $('#imperativeVerbOptions').show();
                $('#finiteVerbOptions').hide();
              } else if (mode == "finite") {
                $('#finiteVerbOptions').show();
                $('#imperativeVerbOptions').hide();
              }
            });
            
            $('#posPrepMode').on('change', function () {
              var mode = $(this).val();
              if (mode == "conjugated" || mode == "possessive") {
                $('#conjPosPrepOptions').show();
              } else {
                $('#conjPosPrepOptions').hide();
              }
            });
            
            $('#posPrepPerson').on('change', function () {
              var person = $(this).val();
              var number = $('#posPrepNumber').val();
              if (person == 'third person' && number == 'singular') {
                $('#genderPrepOptions').show();
              } else {
                $('#genderPrepOptions').hide();
              }
            });
            
            $('#posPrepNumber').on('change', function () {
              var number = $(this).val();
              var person = $('#posPrepPerson').val();
              if (person == 'third person' && number == 'singular') {
                $('#genderPrepOptions').show();
              } else {
                $('#genderPrepOptions').hide();
              }
            });
          
          function updateCitation(data) {
              let context = data.context;
                //update the context scope
              $('#citationContext').attr('data-precontextscope', data.preScope);
              $('#citationContext').attr('data-postcontextscope', data.preScope);
              $('#incrementPre').addClass(context.preIncrementDisable);
              $('#incrementPost').addClass(context.postIncrementDisable);
                //update the context html
              $('#citationContext').attr('data-citationid', data.id);              
              $('#citationContext').html(context.html);
                //update the citationType select
              $('#citationType').val(data.type);             
            }

 /*           
            //new translation badge 
            function addTranslationLink(translationId = '', index = 0) {
                //write a new translation badge
              var html = '<li class="list-group-item d-flex justify-content-between align-items-center" style="border:none; background-color: white;">';
              html += '<a href="#" data-tid="'+translationId+'" class="translationLink">';
							html += '<span class="badge badge-primary badge-pill">'+index+'</span></a></li>';
							$('#translationLinks').append(html);
                //clear the translation editor
              CKEDITOR.instances.slipTranslation.setData(''); //clear the translation content for new empty translation
                //clear the stored translation ID
            }
 */           
            function writeCitationContext(filename, id) {
					    var html = '';
					    let citationId = $('#citationContext').attr('data-citationid');
					    let preScope  = $('#citationContext').attr('data-precontextscope');
					    let postScope = $('#citationContext').attr('data-postcontextscope');
					    let slipId = {$this->_slip->getAutoId()};
					    let citationType = $('#citationType').val();
					    var url = "ajax.php?action=getContext";
					    url += "&citationId="+citationId+"&slipId="+slipId+"&type="+citationType+"&preScope="+preScope+"&postScope="+postScope;
					    url += "&filename="+filename+"&id="+id; 
					    $.getJSON(url, function (data) {					      
					      //handle zero pre/post context sizes
					      if (preScope == 0) {
					        $('#decrementPre').addClass("disabled");
					      } else {
					        $('#decrementPre').removeClass("disabled");
					      }
					      if (postScope == 0) {
					        $('#decrementPost').addClass("disabled");
					      } else {
					        $('#decrementPost').removeClass("disabled");
					      }
					      //handle reaching the start/end of the document
					      if (data.preIncrementDisable) {
					        $('#incrementPre').addClass("disabled");
					      } else {
					        $('#incrementPre').removeClass("disabled");
					      }
					      if (data.postIncrementDisable) {
					        $('#incrementPost').addClass("disabled");
					      } else {
					        $('#incrementPost').removeClass("disabled");
					      }      
					      $('#citationContext').html(data.html);
					      $('#slip').show();
					    });
					  }
        </script>
HTML;
    return $html;
  }
}
