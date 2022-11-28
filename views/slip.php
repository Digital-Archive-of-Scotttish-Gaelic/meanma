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
  	$filename = $this->_slip->getFilename() ? $this->_slip->getFilename() : $_REQUEST["filename"];
	  $locked = $this->_slip->getLocked() ? $this->_slip->getLocked() : 0;
		$lockedHtml = $user->getSuperuser() ? $this->_getLockedDiv($locked) : '';
		$lockedHtml = "";   //hide this for now
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
					<div id="lhs" class="col-6 mh-100" style="overflow-y: scroll;">
						{$this->_writeCitations()}
        </div>  <!-- end LHS -->
        
				<div id="rhs" class="col-6 mh-100" style="overflow-y: scroll;"> <!-- RHS panel -->
	        <div class="form-group row float-right" id="slipChecked">
	          <div class="form-check form-check-inline">
	            <input class="form-check-input" type="checkbox" name="starred" id="slipStarred" {$checked}>
	            <label class="form-check-label" for="slipStarred">checked</label>
	          </div>
	        </div>
	        <!--div class="form-group row" style="clear:right;">
						<label for="slipStatus" class="col-form-label col-sm-2">Status:</label>
						<select class="form-control col-1" id="slipStatus">
							{$statusOptionHtml}
						</select>
					</div-->
					</div-->
	        <div class="row" style="clear:right;">
	          <small><a href="#morphoSyntactic" id="toggleMorphoSyntactic" data-toggle="collapse" aria-expanded="true" aria-controls="morphoSyntactic">
	            show/hide morphosyntax
	          </a></small>
	        </div>
	        <div id="morphoSyntactic" class="collapse editSlipSectionContainer show">
	          <div class="form-group row">
	            <label class="col-sm-2 col-form-label" for="slipHeadword">Headword:</label>
	            <input class="col-4 form-control" type="text" id="slipHeadword" name="slipHeadword" value="{$this->_slip->getHeadword()}"> 
	          </div>
            {$this->_writePartOfSpeechSelects()}
				</div> <!-- end morphoSyntactic -->
				{$this->_writeSenseSelect()}
				{$this->_writePileCategories()}
				<div style="margin: 0 0 10px 10px;">
          <small><a href="#notesSection" id="toggleNotes" data-toggle="collapse" aria-expanded="true" aria-controls="notesSection">
            show/hide notes
          </a></small>
        </div>
        <div id="notesSection" class="form-group collapse show">
          <label class="form-label" for="slipNotes">Notes:</label>
          <textarea class="form-control" name="slipNotes" id="slipNotes" rows="3">{$this->_slip->getNotes()}</textarea>
          <script>
            CKEDITOR.replace('slipNotes', {
              contentsCss: 'https://dasg.ac.uk/meanma/css/ckCSS.css',
              customConfig: 'https://dasg.ac.uk/meanma/js/ckConfig.js'
            });
          </script>
        </div>
        <div id="referenceSection" class="form-group collapse show">
          <label class="form-label" for="reference">Reference:</label>
          <textarea class="form-control" name="reference" id="reference" rows="2">{$this->_slip->getReference()}</textarea>
          <script>
            CKEDITOR.replace('reference', {
              contentsCss: 'https://dasg.ac.uk/meanma/css/ckCSS.css',
              customConfig: 'https://dasg.ac.uk/meanma/js/ckConfig.js',
              stylesSet : 'my_styles'
            });
            //add the small caps option for author names
            CKEDITOR.stylesSet.add( 'my_styles', [
              { name: 'small caps', element: 'span', styles: { 'font-variant': 'small-caps' } } 
						]);
          </script>
        </div>
        <div class="form-group">
          <div class="input-group">
            <input type="hidden" name="filename" value="{$filename}">
            <input type="hidden" name="id" value="{$_REQUEST["wid"]}">
            <input type="hidden" id="locked" name="locked" value="{$locked}";
            <input type="hidden" id="auto_id" name="auto_id" value="{$this->_slip->getId()}">
            <input type="hidden" id="pos" name="pos" value="{$_REQUEST["pos"]}">
            <input type="hidden" id="textId" name="textId" value="{$this->_slip->getTextId()}">
            <input type="hidden" id="subsense_id" name="subsense_id" value="{$this->_slip->getSenseId()}">
            <input type="hidden" name="action" value="save">
            {$lockedHtml}
            <div class="mx-2">
              <button name="close" class="windowClose btn btn-secondary">cancel</button>
              <button name="submit" id="savedClose" class="btn btn-primary">save</button>
             </div>
          </div>
        </div>
				{$this->_writeUpdatedBy()}
				{$this->_writeCitationEditModal()}
				{$this->_writeEmendationModal()}
				{$this->_writeDeletionModal()}
				{$this->_writeTranslationEditModal()}
        {$this->_writeFooter()}
        {$this->_writeEnterTextIdModal()}
			</div>  <!-- end RHS -->
		</div> <!-- end container -->
		{$this->_writeSavedModal()}
HTML;
    models\pilecategories::writePileModal();
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
  	//set the input fields depending on slip type
		if ($this->_slip->getType() == "corpus") {    //slip type is corpus
			$inputHtml = <<<HTML
				<h5>Adjust citation context</h5>
        <div>
					<a class="updateContext btn-link" id="decrementPre"><i class="fas fa-minus"></i></a>
					<a class="updateContext btn-link" id="incrementPre"><i class="fas fa-plus"></i></a>
        </div>
        <span data-citationid="" data-sliptype="corpus" data-entryid="{$this->_slip->getEntryId()}" data-precontextscope="" data-postcontextscope="" id="citationContext" class="citationContext">
        </span>
        <div>
          <a class="updateContext btn-link" id="decrementPost"><i class="fas fa-minus"></i></a>
					<a class="updateContext btn-link" id="incrementPost"><i class="fas fa-plus"></i></a>
        </div>
        <div class="float-right" style="height: 20px;">
          <a href="#" id="resetContext">reset context</a>
				</div>
				<input type="hidden" class="form-control" name="wordform" id="wordform" placeholder="wordform" value="{$this->_slip->getWordform()}"/>
HTML;

		} else {            //slip type is paper
			$inputHtml = <<<HTML
				<div>					
					<div class="form-group">
	          <textarea class="form-control" name="preContextString" id="preContextString" rows="2"></textarea>
	          <script>
	            CKEDITOR.replace('preContextString', {
	              extraPlugins: 'editorplaceholder',
                editorplaceholder: 'pre context here...',
	              contentsCss: 'https://dasg.ac.uk/meanma/css/ckCSS.css',
	              customConfig: 'https://dasg.ac.uk/meanma/js/ckConfig.js',
	              autoParagraph: false
	            });
	          </script>
	        </div>
	        
	        <div class="form-group">
						<input type="text" class="form-control" name="wordform" id="wordform" placeholder="wordform" value="{$this->_slip->getWordform()}"/>
					</div>
					<div class="form-group">
	          <textarea class="form-control" name="postContextString" id="postContextString" rows="2"></textarea>
	          <script>
	            CKEDITOR.replace('postContextString', {
	              extraPlugins: 'editorplaceholder',
                editorplaceholder: 'post context here...',
	              contentsCss: 'https://dasg.ac.uk/meanma/css/ckCSS.css',
	              customConfig: 'https://dasg.ac.uk/meanma/js/ckConfig.js',
	              autoParagraph: false
	            });
	          </script>
	        </div>
					<span data-citationid="" data-entryid="{$this->_slip->getEntryId()}" data-sliptype="paper" id="citationContext" class="citationContext"/>
				</div>
HTML;
		}
		$html = <<<HTML
        <div id="citationEditModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="citationEditModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    {$inputHtml}
										<div class="row form-group" style="clear:right;">		
											<label class="col-4" for="citationType">Citation type:</label>
			                <select id="citationType" name="citationType" class="form-control col-4">
			                  <option value="draft">draft</option>
			                  <option value="sense">sense</option>
			                  <option value="form">form</option>
			                </select>               
										</div>
                </div>
                <div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-dismiss="modal">cancel</button>
                  <button type="button" id="saveCitation" class="btn btn-primary">save</button>
								</div>
            </div>
          </div>
        </div>
HTML;
		return $html;
	}

	private function _writeEmendationModal() {
		$html = <<<HTML
        <div id="emendationModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="emendationModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body" style="font-size:1.2rem;">
                  <div id="emendationContext"></div>
                </div>
                <span data-citationid="" data-entryid="{$this->_slip->getEntryId()}" data-precontextscope="" data-postcontextscope="" id="emendationContext" class="emendationContext">
                <div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
                  <button type="button" id="saveEmendation" data-emendationid="" data-citationid="" class="btn btn-primary invisible">save</button>
								</div>
            </div>
          </div>
        </div>
HTML;
		return $html;
	}

	private function _writeDeletionModal() {
		$html = <<<HTML
        <div id="deletionModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="deletionModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body" style="font-size:1.2rem;">
                  <div id="deletionContext"></div>
                </div>
                <span id="deletionContext" data-startindex="" class="deletionContext">
                <div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
                  <button type="button" id="saveDeletion" data-deletionid="" data-citationid="" class="btn btn-primary invisible">save</button>
								</div>
            </div>
          </div>
        </div>
HTML;
		return $html;
	}

	private function _writeTranslationEditModal() {
		$translationTypeHtml = '<select id="translationType" name="translationType" class="form-control col-4">';
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
                  <div class="row form-group">
			              <textarea name="citationTranslation" id="citationTranslation" rows="2">
										</textarea>
				            <script>
				              CKEDITOR.replace('citationTranslation', {
				                extraPlugins: 'editorplaceholder',
                        editorplaceholder: 'translation text here...',
	                      contentsCss: 'https://dasg.ac.uk/meanma/css/ckCSS.css',
	                      customConfig: 'https://dasg.ac.uk/meanma/js/ckConfig.js',
	                      autoParagraph: false
				              });
				            </script>
			            </div>
			            <div class="row">		
										<label class="col-4" for="translationType">Translation type:</label>
										{$translationTypeHtml}           
									</div>
                </div>
                <div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-dismiss="modal">cancel</button>
                  <button type="button" id="saveTranslation" data-translationid="" data-citationid="" class="btn btn-primary">save</button>
								</div>
            </div>
          </div>
        </div>
HTML;
		return $html;
	}

	private function _writeEnterTextIdModal() {
		$html = <<<HTML
        <div id="enterTextIdModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="enterTextIdModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Text ID</h5>
								</div>
                <div class="modal-body">
                    <label for="enterTextId">Please enter the text ID:</label>
                    <input type="text" class="form-control" id="enterTextId"/>
                </div>
                <div class="modal-footer">
                  <button type="button" id="saveTextId" data-slipid="{$this->_slip->getId()}" class="btn btn-primary">save</button>
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
            slip ID: <span id="auto_id">{$this->_slip->getId()}</span><br>
            text ID: <span id="textId">{$this->_slip->getTextId()}</span><br>
        </div>

				{$this->_writeJavascript()}
HTML;
    return $html;
  }

  private function _writePartOfSpeechSelects() {
    $html = $this->_writeWordClassesSelect();
    $props = $this->_slip->getSlipMorph()->getProps();  //the morph data
    $relations = array("number", "gender", "case", "mode", "fin_person", "imp_person", "fin_number",
	    "imp_number", "status", "tense", "mood", "prep_mode", "prep_person", "prep_number", "prep_gender",
	    "form", "noun_type");
    $options["number"] = array("singular", "plural", "dual†", "unclear");
    $options["gender"] = array("masculine", "feminine", "neuter†", "unclear");
    $options["case"] = array("nominative", "accusative†", "genitive", "dative", "vocative", "unclear");
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
	  //adjectives
	  $options["form"] = array("attributive", "predicative and adverbial", "comparative", "superlative");
	  $options["noun_type"] = array("masculine singular", "masculine dual", "feminine singular", "feminine dual",
		  "plural");
		//create the HTML options for each relation
	  $optionsHtml = array();
    foreach ($relations as $relation) {
    	$optionsHtml[$relation] = '<option value="">----</option>';
    	foreach ($options[$relation] as $option) {
    		$selected = $option == $props[$relation] ? "selected" : "";
    		$optionsHtml[$relation] .= <<<HTML
					<option value="{$option}" {$selected}>{$option}</option>
HTML;
	    }
    }
    $nounSelectHide = $this->_slip->getWordClass() ==  "noun" ? "" : "hide";
	  $nounPhraseSelectHide = $this->_slip->getWordClass() == "noun phrase" ? "" : "hide";
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
    //adjectives
    $adjectiveSelectHide = "hide";
    if ($this->_slip->getWordClass() == "adjective") {
	    $adjectiveSelectHide = "";
	    $adjNounTypeHide = ($props["form"] == "attributive") ? "" : "hide";
	    $adjCaseHide = $adjNounTypeHide;
    }
    $html .= <<<HTML
        <div>
          <!--h5>Morphological information</h5-->
            <div id="prepSelects" class="{$prepSelectHide}">
              <div class="row form-group">
                <label for="posPrepMode" class="col-form-label col-2">Mode:</label>
                <select name="prep_mode" id="posPrepMode" class="form-control col-2">
                  {$optionsHtml["prep_mode"]}
                </select>
              </div>
              <span id="conjPosPrepOptions" class="{$conjPosPrepHide}">
                <div class="row form-group">
                  <label for="posPrepPerson" class="col-form-label col-sm-2">Person:</label>
                  <select name="prep_person" id="posPrepPerson" class="form-control col-2">
                    {$optionsHtml["prep_person"]}
                  </select>
                </div>
                <div class="row form-group">
                  <label for="posPrepNumber" class="col-form-label col-sm-2">Number:</label>
                  <select name="prep_number" id="posPrepNumber" class="form-control col-2">
                    {$optionsHtml["prep_number"]}
                  </select>
                </div>
                  <span id="genderPrepOptions" class="{$genderPrepHide}">
                    <div class="row form-group">
                      <label for="posPrepGender" class="col-form-label col-sm-2">Gender:</label>
                      <select name="prep_gender" id="posPrepGender" class="form-control col-2">
                        {$optionsHtml["prep_gender"]}
											</select>
										</div>
									</span>
								</span>
            </div>
            <div id="nounSelects" class="{$nounSelectHide}">
                <div class="row form-group">
	                <label for="posNumber" class="col-form-label col-sm-2">Number:</label>
	                <select name="number" id="posNumber" class="form-control col-4">
	                  {$optionsHtml["number"]}
	                </select>
	              </div>
	              <div class="row form-group">
	                <label for="posCase" class="col-form-label col-sm-2">Case:</label>
	                <select name="case" id="posCase" class="form-control col-2">
	                  {$optionsHtml["case"]}
	                </select>
	              </div>
	              <div class="row form-group">
	                <label for="posGender" class="col-form-label col-sm-2">Gender:</label>
	                <select name="gender" id="posGender" class="form-control col-4">
	                  {$optionsHtml["gender"]}
	                </select>
	              </div>
            </div>
            <div id="nounPhraseSelects" class="{$nounPhraseSelectHide}">
                <div class="row form-group">
	                <label for="posNPNumber" class="col-form-label col-sm-2">Number:</label>
	                <select name="number" id="posNPNumber" class="form-control col-4">
	                  {$optionsHtml["number"]}
	                </select>
	              </div>             
	              <div class="row form-group">
	                <label for="posNPCase" class="col-form-label col-sm-2">Case:</label>
	                <select name="case" id="posNPCase" class="form-control col-2">
	                  {$optionsHtml["case"]}
	                </select>
	              </div>
	              <div class="row form-group">
	                <label for="posNPGender" class="col-form-label col-sm-2">Gender:</label>
	                <select name="gender" id="posNPGender" class="form-control col-4">
	                  {$optionsHtml["gender"]}
	                </select>
	              </div>
            </div>
            <div id="verbSelects" class="{$verbSelectHide}">
                <div class="row form-group">
	                <label for="posMode" class="col-form-label col-sm-2">Mode:</label>
	                <select name="mode" id="posMode" class="form-control col-2">
	                  {$optionsHtml["mode"]}
	                </select>
	              </div>
                <span id="nonVerbalNounOptions" class="{$verbalNounHide}">
                  <span id="imperativeVerbOptions" class="{$impVerbSelectHide}">
                    <div class="row form-group">
	                    <label for="posImpPerson" class="col-form-label col-sm-2">Person:</label>
		                  <select name="imp_person" id="posImpPerson" class="form-control col-4">
		                    {$optionsHtml["imp_person"]}
		                  </select>
		                </div>
		                <div class="row form-group">
		                  <label for="posImpNumber" class="col-form-label col-sm-2">Number:</label>
		                  <select name="imp_number" id="posImpNumber" class="form-control col-4">
		                    {$optionsHtml["imp_number"]}
		                  </select>
		                </div>
	                </span>
	                <span id="finiteVerbOptions" class="{$finVerbSelectHide}">
	                  <div class="row form-group">
	                    <label for="posFinPerson" class="col-form-label col-sm-2">Person:</label>
		                  <select name="fin_person" id="posFinPerson" class="form-control col-2">
		                    {$optionsHtml["fin_person"]}
		                  </select>
		                </div>
		                <div class="row form-group">
		                  <label for="posFinNumber" class="col-form-label col-sm-2">Number:</label>
		                  <select name="fin_number" id="posFinNumber" class="form-control col-2">
		                    {$optionsHtml["fin_number"]}
		                  </select>
		                </div>
		                <div class="row form-group">
		                  <label for="posStatus" class="col-form-label col-sm-2">Status:</label>
		                  <select name="status" id="posStatus" class="form-control col-2">
		                    {$optionsHtml["status"]}
		                  </select>
		                </div>
		                <div class="row form-group">
	                    <label for="posTense" class="col-form-label col-sm-2">Tense:</label>
	                    <select name="tense" id="posTense" class="form-control col-2">
	                      {$optionsHtml["tense"]}
	                    </select>
	                  </div>
	                  <div class="row form-group">
	                    <label for="posMood" class="col-form-label col-sm-2">Mood:</label>
	                    <select name="mood" id="posMood" class="form-control col-2">
	                      {$optionsHtml["mood"]}
	                    </select>
	                  </div>
                  </span>
                </span>
            </div>
            <div id="adjectiveSelects" class="{$adjectiveSelectHide}">
                <div class="row form-group">
	                <label for="posForm" class="col-form-label col-sm-2">Form:</label>
	                <select name="form" id="posForm" class="form-control col-4">
	                  {$optionsHtml["form"]}
	                </select>
	              </div>
	              <span id="adjectiveNounType" class="{$adjNounTypeHide}">
		              <div class="row form-group">
		                <label for="posNounType" class="col-form-label col-sm-2">Following noun type:</label>
		                <select name="noun_type" id="posNounType" class="form-control col-4">
		                  {$optionsHtml["noun_type"]}
		                </select>
		              </div>
	              </span>
	              <span id="adjectiveCase" class="{$adjCaseHide}">
		              <div class="row form-group">
		                <label for="posAdjCase" class="col-form-label col-sm-2">Case:</label>
		                <select name="case" id="posAdjCase" class="form-control col-4">
		                  {$optionsHtml["case"]}
		                </select>
		              </div>
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
        <div id="wordClassSelect" class="row form-group">
          <label for="wordClass" class="col-sm-2 col-form-label">POS:</label>
          <select name="wordClass" id="wordClass" class="form-control col-sm-3">
            {$optionHtml}
          </select>
        </div>
HTML;
    return $html;
  }

	private function _writeSenseSelect() {
		$senses = $this->_slip->getEntry()->getTopLevelSenses($this->_slip->getDb());
		$selectedHtml = $this->_slip->getSense() ? $this->_slip->getSense()->getLabel() : "-- select a sense --";
		$unassignDisplay = $this->_slip->getSense() ? "" : 'style="display: none;"';
		$dropdownHtml = <<<HTML
			<div class="dropdown show">
        <a class="btn btn-secondary dropdown-toggle sense-displayed" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          {$selectedHtml}
        </a>
        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
HTML;
		foreach ($senses as $sense) {
			$dropdownHtml .= $this->_getSenseOptions($sense);
		}
		$dropdownHtml .= <<<HTML
        </ul>
      </div>
HTML;
		$html = <<<HTML
				<div style="margin-left: 10px;">
					<small><a href="#sense" id="toggleSense" data-toggle="collapse" aria-expanded="true" aria-controls="sense">
            show/hide sense
          </a></small>
        </div>
        <div id="sense" class="editSlipSectionContainer collapse show">
          <div class="form-group row">
            <div class="col-sm-2">
                  <label for="senseSelect" class="col-form-label">Sense:</label>
            </div>
            <div class="col">
                {$dropdownHtml}
            </div>
            <div class="col">
              <button class="btn btn-warning sense-unassign" {$unassignDisplay}>unassign sense</button>
						</div>
          </div>
        </div>
HTML;
		return $html;
	}

	/**
	 * Recursive method to assemble sense options for senses and subsenses
	 * @param $sense
	 */
	private function _getSenseOptions($sense) {
		if (!empty($sense->getSubsenses())) {   //there are subsenses, so write the dropdown link
			$html = <<<HTML
				<li class="dropdown-submenu">
					<a class="dropdown-item senseSelect" data-id="{$sense->getId()}" dropdown-toggle" href="#">
						{$sense->getLabel()}
					</a>
					<ul class="dropdown-menu">
HTML;
			foreach ($sense->getSubsenses() as $subsense) {
				$html .= $this->_getSenseOptions($subsense);  //recursive call to get subsenses
			}
			$html .= "</ul>";
		} else {    //this is a sense so write it
			$html .= <<<HTML
				<li><a class="dropdown-item senseSelect" data-id="{$sense->getId()}" href="#">{$sense->getLabel()}</a></li>
HTML;

		}
		$html .= "</li>";
		return $html;
	}

  private function _writePileCategories() {
  	$unusedPiles = $this->_slip->getUnusedPiles();
		$savedPiles = $this->_slip->getPiles();
    $dropdownHtml = '<option data-category="">-- select a pile --</option>';
    foreach ($unusedPiles as $pile) {
    	$pileId = $pile->getId();
    	$pileName = $pile->getName();
      $dropdownHtml .= <<<HTML
        <option data-pile="{$pileId}" data-pile-description="{$pile->getDescription()}" 
          data-pile-name="{$pileName}" value="{$pileId}">{$pileName}</option>
HTML;
    }
    $savedCatHtml = "";
    foreach ($savedPiles as $pile) {
    	$pileId = $pile->getId();
    	$pileName = $pile->getName();
    	$pileDescription = $pile->getDescription();
      $savedCatHtml .= <<<HTML
        <li class="badge badge-success pileBadge" data-title="{$pileDescription}"
          data-toggle="modal" data-target="#pileModal" data-slip-id="{$this->_slip->getId()}"
          data-pile="{$pileId}" data-pile-name="{$pileName}" data-pile-description="{$pileDescription}">
					{$pileName}
				</li>
HTML;
    }
    $html = <<<HTML
				<div style="margin-left: 10px;">
					<small><a href="#piles" id="togglePiles" data-toggle="collapse" aria-expanded="true" aria-controls="piles">
            show/hide piles
          </a></small>
        </div>
        <div id="piles" class="editSlipSectionContainer collapse show">
          <!--h5>Pile Categories</h5-->
          <div class="form-group row">
            <div class="col-sm-2">
                  <label for="pileCategorySelect" class="col-form-label">Choose existing pile:</label>
            </div>
            <div>
                <select class="form-control" id="pileCategorySelect">{$dropdownHtml}</select>
            </div>
            <div class="col-sm-2">
                  <button type="button" class="form-control btn btn-primary" id="choosePileCategory">Add</button>
              </div>
          </div>
          <div class="form-group row">
              <div class="col-sm-2">
                  <label for="pileCategory" class="col-form-label">Assign to new pile:</label>
              </div>
              <div class="col-sm-2">
									<label class="col-form-label" for="newPileName">Name</label>
                  <input type="text" class="form-control" id="newPileName">
              </div>
              <div class="col-sm-3">
                  <label class="col-form-label" for="newPileDefinition">Definition</label>
                  <input type="text" class="form-control" id="newPileDefinition">
							</div>
              <div class="col-sm-2">
                  <button type="button" class="form-control btn btn-primary" id="addPile">Add</button>
              </div>
          </div>
          <div>
            <ul id="pileCategories">
                {$savedCatHtml}
            </ul>
          </div>
        </div>
HTML;
    return $html;
  }

  private function _writeCitations() {
    $html = <<<HTML
			<div><ul id="citationList" style="list-style-type:none;">
HTML;
    $citations = $this->_citations;
    foreach ($citations as $citation) {
    	$cid = $citation->getId();
    	$deleteCitHtml = '<a href="#" class="deleteCitation danger float-right" data-cid="' . $cid . '"><small>delete citation</small></a>';
	    $transHtml = <<<HTML
				<span style="text-muted"><a href="#" class="transToggle" data-citationid="{$cid}"><small>show/hide translation(s)</small></a></span>
				<div id="transContainer_{$cid}" style="display: none;">
					<ul id="transList_{$cid}" style="list-style-type: none; margin:5px 10px;">
HTML;
    	if ($translations = $citation->getTranslations()) {
				foreach ($translations as $translation) {
					$tid = $translation->getId();
					$deleteTransHtml = '<a href="#" class="deleteTranslation danger float-right" data-tid="' . $tid . '"><small>delete translation</small></a>';
					$content = strip_tags($translation->getContent(), "<mark><b><strong><><i>");
					$transHtml .= <<<HTML
						<li class="translationContainer_{$tid}">
							<span id="trans_{$tid}">{$content}</span> <em><span id="transType_{$tid}">({$translation->getType()})</span></em>&nbsp;
              <a href="#" id="editTrans_{$tid}" class="editTrans" data-translationid="{$tid}">edit</a>
              {$deleteTransHtml}
						</li>
HTML;
				}
	    }
    	$transHtml .= <<<HTML
					</ul> <!-- close the transList -->
					<div>
						<a href="#" class="addTranslationLink" data-citationid="{$cid}" title="add translation" style="font-size: 15px;"><i class="fas fa-plus" style="color: #007bff;"></i></a>
					</div>
				</div>  <!-- close the transContainer -->
HTML;
    	$citationString = ($this->_slip->getType() == "corpus")
		    ? $citation->getContext()["html"]
		    : $citation->getPreContextString() . ' <mark class="hi">' . $this->_slip->getWordform() . '</mark> ' . $citation->getPostContextString();
			$html .= <<<HTML
				<li class="citationContainer_{$cid}" style="border-top: 1px solid gray;">
					<span id="citation_{$citation->getId()}">
						{$citationString}
					</span>
					<em>
						<span id="citationType_{$citation->getId()}">
							({$citation->getType()})
						</span>
					</em>
					<a href="#" class="editCitation" data-citationid="{$citation->getId()}" data-toggle="modal" data-target="#citationEditModal"><small>context</small></a>
					<a href="#" class="editEmendation" data-citationid="{$citation->getId()}" data-toggle="modal" data-target="#emendationModal"><small>insertions</small></a>
					<a href="#" class="editDeletion" data-citationid="{$citation->getId()}" data-toggle="modal" data-target="#deletionModal"><small>ellipses</small></a>
					{$deleteCitHtml}
				</li>
				<li class="citationContainer_{$cid}">{$transHtml}</li>
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

  private function _writeJavascript() {
    $html = <<<HTML
        <script>  
          $(function () {      
            
            //demand a Text ID if none is found 
            if ($('#textId').val() == '') {
              $('#enterTextIdModal').modal({
                show: true,
                backdrop: 'static'
              });
            }
            
            //senses
            $('.senseSelect').on('click', function () {
                $('#subsense_id').val($(this).attr("data-id"));
                $('#dropdownMenuLink').text($(this).text());
                $('.sense-unassign').show();
            });
            
            //unassign sense
						$('.sense-unassign').on('click', function () {
						  $.ajax('ajax.php?action=unassignSense&id={$this->_slip->getId()}');
						  $('.sense-unassign').hide();
						  $('.sense-displayed').text('-- select a sense --');
						  $('#subsense_id').val(null);
						});
            
            //delete citation
            $(document).on('click', '.deleteCitation', function () {
              if (!confirm('Are you sure you want to delete this citation?    ** This will delete all translations for this citation. **')) {
                return;
              }  
              let cid = $(this).attr('data-cid');
              $('.citationContainer_'+cid).remove();
              $.ajax('ajax.php?action=deleteCitation&id='+cid);
            });
            
            //delete translation
            $(document).on('click', '.deleteTranslation', function () {
              if (!confirm('Are you sure you want to delete this translation?')) {
                return;
              }  
              let tid = $(this).attr('data-tid');
              $('.translationContainer_'+tid).remove();
              $.ajax('ajax.php?action=deleteTranslation&id='+tid);
            });
            
            //save text ID for slip
            $('#saveTextId').on('click', function () {
              let textId = $('#enterTextId').val();
              if (!textId) {
                alert("You must enter a text ID");
                $('#enterTextId').focus();
                return;
              }
              let slipId = $(this).attr('data-slipid');
              $.getJSON('ajax.php?action=addTextIdToSlip&slipId='+slipId+'&textId='+textId, function (data){
                console.log('success');
              })
              .done(function () {
                $('#textId').val(textId);
                $('#enterTextIdModal').modal('hide'); 
              });
            });
            
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
              let slipId = {$this->_slip->getId()};
              $.getJSON('ajax.php?action=loadCitation&id='+cid+'&slipId='+slipId)
              .done(function(data) {
                $('#citationContext').attr('data-citationid', data.id);
                if (data.context) {   //corpus slip
                  $('#citationContext').attr('data-precontextscope', data.preScope);
                  $('#citationContext').attr('data-postcontextscope', data.postScope);
                  $('#citationContext').html(data.context['html']);
                  //check for context limits
                  if (data.context.preIncrementDisable) {
                    $('#incrementPre').addClass("disabled");
                  }
                  if (data.context.postIncrementDisable) {
                    $('#incrementPost').addClass("disabled");
                  }
                } else {              //paper slip
                  CKEDITOR.instances["preContextString"].setData(data.preContextString); 
                  CKEDITOR.instances["postContextString"].setData(data.postContextString); 
                }
                $('#citationType').val(data.type);
              });
            });
            
            //populate deletion modal on button click.
            $(document).on('show.bs.modal', '#deletionModal', function (event) {
              var modal = $(this);
              var editLink = $(event.relatedTarget);
              let cid = editLink.attr('data-citationid');
              let slipId = {$this->_slip->getId()};
              $.getJSON('ajax.php?action=loadCitation&id='+cid+'&slipId='+slipId+'&edit=2&context=false')
              .done(function(data) {
                $('#deletionContext').attr('data-citationid', data.id);
                if (data.context) {   //corpus slip
                  $('#deletionContext').html(data.context['html']);
                } 
              });
            });
            
            //repopulate citation on deletion modal close
            $(document).on('hide.bs.modal', '#deletionModal', function (event) {
              let cid = $('#deletionContext').attr('data-citationid');
              let slipId = {$this->_slip->getId()};
              $.getJSON('ajax.php?action=loadCitation&id='+cid+'&slipId='+slipId+'&edit=0&context=false')
              .done(function(data) {
                  $('#citation_'+cid).html(data.context['html']);
              });
            });
            
            //add a new deletion
            $(document).on('click', '.new-deletion', function() {
              let tokenIdStart = $(this).closest('.deletion-select').attr('id');
              let index = $(this).attr('data-index');
              $('#deletionContext').attr('data-startindex', index);
              $('#deletionContext').attr('data-starttokenid', tokenIdStart);
              let cid = $('#deletionContext').attr('data-citationid');
              $('#'+tokenIdStart + '> .collocateLink').addClass('deletionStart'); //mark the deletion start-point for the user
              $.getJSON('ajax.php?action=createDeletion&cid='+cid+'&tid='+tokenIdStart)
              .done(function(data) {
                $('.new-deletion').addClass('disabled');
                $('.end-deletion').removeClass('disabled');
                let deletionId = data.id;                
                $('#saveDeletion').attr('data-deletionid', deletionId); //store for recall on select deletion end
              });    
            }); 
            
            //save the deletion on click of end point
            $(document).on('click', '.end-deletion', function () {
              var startId = ''; //only used to swap start/end point if need be
              let deletionId = $('#saveDeletion').attr('data-deletionid');
              let tokenIdEnd = $(this).closest('.deletion-select').attr('id');
              let cid = $('#deletionContext').attr('data-citationid');
              let index = $(this).attr('data-index');
              let startIndex = $('#deletionContext').attr('data-startindex');
              //check if the end of the deletion is before the start
              if (index < startIndex) {
                //it is, so swap the start and end tokenIds in the database
                startId = tokenIdEnd;
                tokenIdEnd = $('#deletionContext').attr('data-starttokenid');
              }
              $.getJSON('ajax.php?action=updateDeletion&id='+deletionId+'&tid='+tokenIdEnd+'&startId='+startId)
              .done(function() {
                $('.new-deletion').removeClass('disabled');
                $('.end-deletion').addClass('disabled');
                //refresh the context with the deletion
                $.getJSON('ajax.php?action=loadCitation&id='+cid+'&slipId={$this->_slip->getId()}&edit=2&context=false')
	              .done(function(data) {
	                 $('#deletionContext').html(data.context['html']);
	              });
              });
            });
            
            //delete a deletion
            $(document).on('click', '.delete-deletion', function() {
              if (confirm('Are you sure you want to delete this ellipsis?')) {
                let id = $(this).attr('data-id');
                let cid = $('#deletionContext').attr('data-citationid');
                $.ajax('ajax.php?action=deleteDeletion&id='+id)
                  .done(function() {
                    //refresh the context with the deletion
	                  $.getJSON('ajax.php?action=loadCitation&id='+cid+'&slipId={$this->_slip->getId()}&edit=2&context=false')
		                .done(function(data) {
		                  $('#deletionContext').html(data.context['html']);
		                });    
                });
              }
            });
            
            //populate emendation modal on button click.
            $(document).on('show.bs.modal', '#emendationModal', function (event) {
              var modal = $(this);
              var editLink = $(event.relatedTarget);
              let cid = editLink.attr('data-citationid');
              let slipId = {$this->_slip->getId()};
              $.getJSON('ajax.php?action=loadCitation&id='+cid+'&slipId='+slipId+'&edit=1&context=false')
              .done(function(data) {
                $('#emendationContext').attr('data-citationid', data.id);
                if (data.context) {   //corpus slip
                  $('#emendationContext').attr('data-precontextscope', data.preScope);
                  $('#emendationContext').attr('data-postcontextscope', data.postScope);
                  $('#emendationContext').html(data.context['html']);
                } 
              });
            });
            
            //repopulate citation on emendation modal close
            $(document).on('hide.bs.modal', '#emendationModal', function (event) {
              let cid = $('#emendationContext').attr('data-citationid');
              let slipId = {$this->_slip->getId()};
              $.getJSON('ajax.php?action=loadCitation&id='+cid+'&slipId='+slipId+'&edit=0&context=false')
              .done(function(data) {
                  $('#citation_'+cid).html(data.context['html']);
              });
            });
            
            //add a new emendation
            $(document).on('click', '.new-emendation', function() {
              let type = $(this).text();
              let placement = $(this).parent().parent().attr('data-placement');
              let tokenId = $(this).closest('.emendation-select').attr('id');
              let cid = $('#emendationContext').attr('data-citationid');
              $.getJSON('ajax.php?action=createEmendation&cid='+cid+'&type='+type+'&tid='+tokenId+'&pos='+placement)
              .done(function(data) {
                let emendationId = data.id;                
                switch (placement) {
                case "before":
                  $('#'+tokenId).before(addDropdownHtml(type, emendationId));
                  break;
                case "after":
                  $('#'+tokenId).after(addDropdownHtml(type, emendationId));
                  break;
              } 
              });    
            }); 
            
            function addDropdownHtml(type, emendationId) {
              let text = '[' + type + ']';
              var html = '<div id="emendation_'+emendationId+'" class="dropdown show d-inline emendation-action">';
		          html += '<a class="dropdown-toggle collocateLink" href="#" id="dropdown_'+emendationId+'" ';
		          html += 'data-type="'+type+'" data-content=""';
		          html += 'data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'+text+'</a>';
			        html += '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown_'+emendationId+'">';
			        html += '<li><a class="dropdown-item edit-emendation" data-id="'+emendationId+'" tabindex="-1" href="#">edit</a></li>';
			        html += '<li><a class="dropdown-item delete-emendation" data-id="'+emendationId+'"  tabindex="-1" href="#">delete</a></li></ul>';
							html += '</div>';
							html += '<div id="edit_emendation_'+emendationId+'" class="hide">';
							html += '<input type="text" class="emendation_input" id="edit_'+emendationId+'" value="">';
							html += '</div> ';
							return html;
            }
            
            //edit an emendation
            $(document).on('click', '.edit-emendation', function() {
              let id = $(this).attr('data-id');
              //hide the dropdown and menu
              $('#emendation_'+id).removeClass('show d-inline');
              $('#emendation_'+id).hide();
              $('#dropdown_'+id).dropdown('hide');
              //show the edit box container
              $('#edit_emendation_'+id).removeClass('hide');
              $('#edit_emendation_'+id).addClass('inline-block');
              $('#edit_'+id).focus();
            });
            
            //update an emendation
            $(document).on('blur', '.emendation_input', function() {
              let content = $(this).val();
              let id = $(this).attr('id');
              let parts = id.split('_');
              let emendationId = parts[1];
              let type = $('#dropdown_'+emendationId).attr('data-type');  //get the type from the <a> tag
              $('#dropdown_'+emendationId).attr('data-content', content); //store the content in the <a> tag
              
              console.log('type : ' + type);
              
              let displayType = (type == "other") ? '' : type + ' ';
              
              console.log('displayType : ' + displayType );
              
              let displayedText = (content) ? '[' + displayType + content + ']' : '[' + type + ']'; 
              
              
              console.log(displayedText);
              
              $.ajax('ajax.php?action=updateEmendation&id='+emendationId+'&content='+content);          
              //hide the input field container
              $('#edit_emendation_'+emendationId).removeClass('inline-block');
              $('#edit_emendation_'+emendationId).addClass('hide');
              //update the displayed text in dropdown's <a> tag
              $('#emendation_'+emendationId+' > a').text(displayedText);
              //show the dropdown
              $('#emendation_'+emendationId).addClass('show d-inline');
              $('#emendation_'+emendationId).show();
            });
            
            //delete an emendation
            $(document).on('click', '.delete-emendation', function() {
              if (confirm('Are you sure you want to delete this emendation?')) {
                let id = $(this).attr('data-id');
                $.ajax('ajax.php?action=deleteEmendation&id='+id);
                $('#emendation_'+id).remove();
              }
            });
            
            //save the citation from the modal
            $('#saveCitation').on('click', function() {
              let context = $('#citationContext');
              let slipType = context.attr('data-sliptype');
              var html, preContextString, postContextString;
              var preScope = 0;
              var postScope = 0;
              if (slipType == 'corpus') {             //corpus slip
	              html = context.html();
	              preScope = context.attr('data-precontextscope');
	              postScope = context.attr('data-postcontextscope');
              } else {                                                    //paper slip
                preContextString = CKEDITOR.instances['preContextString'].getData();
                postContextString = CKEDITOR.instances['postContextString'].getData();
                html = preContextString + ' <mark class="hi">' + $('#wordform').val() + '</mark> ' + postContextString;
              }
              let cid = context.attr('data-citationid');           
              let type = $('#citationType').val();     
              var url = 'ajax.php?action=saveCitation&id='+cid+'&preScope='+preScope+'&postScope='+postScope+'&type='+type;
              url += '&slipType='+slipType;
              $.ajax({
                method: "post",
                url: url,
                data: {
                  preContextString: preContextString,
                  postContextString: postContextString
                }
              })
              .done(function (data) {
                //check if citation is already in list
                if ($('#citation_'+cid).length) {   //citation exists so update it 
                  $('#citation_'+cid).html(html);
                  $('#citationType_'+cid).html('('+type+')');
                } else {                           //citation does not yet exist so add it                 
                    var citHtml = '<li class="citationContainer_'+cid+'" style="border-top: 1px solid gray;"><span id="citation_'+cid+'">'+html+'</span>';
                    citHtml += '<em><span id="citationType_'+cid+'">&nbsp;('+type+')&nbsp;</span></em>';
                    citHtml += '<a href="#" class="editCitation" data-citationid="'+cid+'" data-toggle="modal" data-target="#citationEditModal"><small>context </small></a>';
                    citHtml += '<a href="#" class="editEmendation" data-citationid="'+cid+'" data-toggle="modal" data-target="#emendationModal"><small> insertions </small></a>';
                    citHtml += '<a href="#" class="editDeletion" data-citationid="'+cid+'" data-toggle="modal" data-target="#deletionModal"><small> ellipses </small></a>';
                    citHtml += '<a href="#" class="deleteCitation danger float-right" data-cid="'+cid+'"><small>delete citation</small></a>';
                    // delete code here
                    citHtml += '</li><li class="citationContainer_'+cid+'">';
                    citHtml += '<span style="text-muted"><a href="#" class="transToggle" data-citationid="'+cid+'"><small>show/hide translation(s)</small></a></span>';
										citHtml += '<div id="transContainer_'+cid+'" style="display: none;">';
										citHtml += '<ul id="transList_'+cid+'" style="list-style-type: none; margin:5px 10px;">';
										citHtml += '<li><a href="#" class="addTranslationLink" data-citationid="'+cid+'" title="add translation" style="font-size: 15px;"><i class="fas fa-plus" style="color: #007bff;">';
										citHtml += '</i></a></li></ul></li> <!-- close the transList --></div>  <!-- close the transContainer -->';
                    $('#citationList').append(citHtml);
                }     
                $('#citationEditModal').modal('hide');
              });
            });
                    
            //add translation
            $(document).on('click', '.addTranslationLink', function () {
              let citationId = $(this).attr('data-citationid');
              $.getJSON('ajax.php?action=createTranslation&citationId='+citationId)
              .done(function(data) {
                $('#saveTranslation').attr('data-citationid', citationId);
                $('#saveTranslation').attr('data-translationid', data.id);
                //append a placeholder to the citation's translation list 
                var html = '<li class="translationContainer_'+data.id+'"><span id="trans_'+data.id+'"></span> <em><span id="transType_'+data.id+'"></span></em>&nbsp;';
                html += '<a href="#" id="editTrans_'+data.id+'" class="editTrans" data-translationid="'+data.id+'">edit</a>';
                html += '<a href="#" class="deleteTranslation danger float-right" data-tid="'+data.id+'"><small>delete translation</small></a>';
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
              };
              $.ajax(params);
            });
            
            //load translation
            $(document).on('click', '.translationLink', function () {
              let tid = $(this).attr('data-tid');
              $('#slipTranslation').attr('data-translationid', tid);
              loadTranslation(tid);
            });
            
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
              let slipId = {$this->_slip->getId()}
              let filename = '{$this->_slip->getFilename()}';
              let id = '{$this->_slip->getWid()}';
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
              var headwordId = $('.slipWordInContext').attr('data-headwordid');
              var slipId = '{$this->_slip->getId()}';
              var url = 'ajax.php?action=saveLemmaGrammar&id='+wordId+'&filename='+filename;
              url += '&headwordId='+headwordId+'&slipId='+slipId+'&grammar='+$(this).text();
              $.getJSON(url, function(data) {
                $('.collocateHeadword').text(data.lemma);
              });
            });

            /**
            * Piles
						*/  
            $("#choosePileCategory").on('click', function () {
              var elem = $( "#pileCategorySelect option:selected" );
              var pile = elem.text();
              if (!elem.attr('data-pile')) {
                return false;
              }
              var pileId = elem.attr('data-pile');
              var pileName = elem.attr("data-pile-name");
              var pileDescription = elem.attr('data-pile-description');
              var html = '<li class="badge badge-success pileBadge" data-pile="' + pileId + '"';
              html += ' data-toggle="modal" data-target="#pileModal"';
              html += ' data-title="' + pileDescription +  '" data-pile-name="' + pileName + '">' + pile + '</li>';
              $('#pileCategories').append(html);
              elem.remove();
              var data = {action: 'saveSlipPile', slipId: '{$this->_slip->getId()}',
                pileId: pileId}
              $.post("ajax.php", data, function (response) {
                console.log(response);        //TODO: add some response code on successful save
              });
            });

            $(document).on('click', '#addPile', function () {
              var newPileName = $('#newPileName').val();
              var newPileDefinition = $('#newPileDefinition').val();
              var entryId = $('#citationContext').attr('data-entryid');      
              if (newPileName == "") {
                return false;
              }
              $('#newPileName').val('');
              $('#newPileDefinition').val('');
              var data = {action: 'addPile', slipId: '{$this->_slip->getId()}',
                name: newPileName, description: newPileDefinition, entryId: entryId
              }
              $.getJSON("ajax.php", data, function (response) {
                var html = '<li class="badge badge-success pileBadge" data-pile="' + response.pileId + '"';
                html += ' data-title="' + response.pileDescription +'"';
                html += ' data-slip-id="{$this->_slip->getId()}"';
                html += ' data-pile-name="' + newPileName + '" data-pile-description="' + newPileDefinition + '"';
                html += ' data-toggle="modal" data-target="#pileModal"';
                html += '>' + newPileName + '</li>';
                $('#pileCategories').append(html);
              });
            });

            /*
              ** Change of wordclass or headword
             */
            $('#wordClass,#slipHeadword').on('change', function() {
              let check = confirm('Changing the headword and/or wordclass will remove any senses. Are you sure you want to proceed?');
              let previousHeadword = "{$this->_slip->getHeadword()}";
              let previousWordclass = "{$this->_slip->getWordClass()}";        
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
                  $('#nounPhraseSelects').hide();
                  $('#prepSelects').hide();
                  $('#adjectiveSelects').hide();
                  break;
                case "noun":
                  $('#nounSelects').show();
                  $('#nounPhraseSelects').hide();
                  $('#verbSelects').hide();
                  $('#prepSelects').hide();
                  $('#adjectiveSelects').hide();
                  break;
                case "noun phrase":              
                  $('#nounPhraseSelects').show();
                  $('#nounSelects').hide();
                  $('#verbSelects').hide();
                  $('#prepSelects').hide();
                  $('#adjectiveSelects').hide();
                  break;
                case "preposition":
                  $('#prepSelects').show();
                  $('#nounSelects').hide();
                  $('#nounPhraseSelects').hide();
                  $('#verbSelects').hide();
                  $('#adjectiveSelects').hide();
                  break;
                case "adjective":
                  $('#nounSelects').hide();
                  $('#nounPhraseSelects').hide();
                  $('#verbSelects').hide();
                  $('#prepSelects').hide();
                  $('#adjectiveSelects').show();
                  break;
                default:
                  $('#nounSelects').hide();
                  $('#nounPhraseSelects').hide();
                  $('#verbSelects').hide();
                  $('#prepSelects').hide();
              }
              //update the pile categories 
              $('.pileBadge').remove();
              $('#pileCategorySelect').empty();
              $('#pileCategorySelect').append('<option data-category="">-- select a category --</option>');
              var url = 'ajax.php?action=getPileCategoriesForNewWordclass';
              url += '&slipType={$this->_slip->getType()}&entryId={$this->_slip->getEntryId()}';
              url += '&filename={$this->_slip->getFilename()}&id={$this->_slip->getWid()}&auto_id={$this->_slip->getId()}';
              url += '&text_id={$this->_slip->getTextId()}';
              url += '&pos={$this->_slip->getPOS()}&headword=' + headword + '&wordclass=' + wordclass;
							var entryId;              
              $.getJSON(url, function (data) { 
                  entryId = data.entryId;
                  $('#citationContext').attr('data-entryid', entryId);
                  $.each(data.pileInfo, function (index, pile) {
                    var html = '<option data-pile="' + index + '" data-pile-description="' + pile.description + '"';
                    html += ' data-pile-name="' + pile.name + '" value="' + index + '">' + pile.name + '</option>';
                    $('#pileCategorySelect').append(html);
                  });
              })
              .done(function () {   //raise and save an issue with the slip and headword/wordclass information
                    var params = {
                      description: 'The ' + changedField + ' for §{$this->_slip->getId()} has been changed to <strong>' + changedValue + '</strong>',
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
            
            $('#posForm').on('change', function () {
              var form = $(this).val();
              if (form == "attributive") {
                $('#adjectiveNounType').removeClass('hide');
                $('#adjectiveCase').removeClass('hide');
              } else {
                $('#adjectiveNounType').addClass('hide');
                $('#adjectiveCase').addClass('hide');
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
					    let citationId = $('#citationContext').attr('data-citationid');
					    let preScope  = $('#citationContext').attr('data-precontextscope');
					    let postScope = $('#citationContext').attr('data-postcontextscope');
					    let slipId = {$this->_slip->getId()};
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
