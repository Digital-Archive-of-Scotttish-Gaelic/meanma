<?php

namespace models;

class corpus_slip extends slip
{
	const SCOPE_DEFAULT = 80;

  private $_textId, $_filename, $_wid, $_pos, $_db;
  private $_starred, $_notes, $_locked, $_ownedBy, $_entryId, $_headword, $_slipStatus;
  private $_preContextScope, $_postContextScope, $_wordClass, $_lastUpdatedBy, $_lastUpdated;
  private $_isNew;
  private $_wordClasses = array(
    'noun' => array("n", "nx", "ns", "N", "Nx"),
    "verb" => array("v", "V", "vn"),
    "adjective" => array("a", "ar"),
    "preposition" => array("p", "P"),
    "adverb" => array("A"),
    "other" => array("d", "c", "z", "o", "D", "Dx", "ax", "px", "q", "nd", ""));
  private $_entry;  //an instance of models\entry
  private $_slipMorph;  //an instance of models\slipmorphfeature
  private $_senses = array();
  private $_sensesInfo = array();   //used to store sense info (in place of object data) for AJAX use
	private $_citations;  //an array of citation objects

  public function __construct($filename, $wid, $auto_id = null, $pos, $preScope = self::SCOPE_DEFAULT, $postScope = self::SCOPE_DEFAULT) {
    $this->_filename = $filename;
    $this->_wid = $wid;
    //test if a slip already exists (if there is a slip with the same groupId, filename, id combination)
    $slipId = $auto_id ? $auto_id : collection::slipExists($_SESSION["groupId"], $filename, $wid);
    parent::__construct($slipId);
    $this->_pos = $pos;
    if (!isset($this->_db)) {
      $this->_db = new database();
    }
    $this->_loadSlip($preScope, $postScope);
  }

  private function _loadSlip($preScope, $postScope) {
    $this->_textId = corpus_browse::getTextIdFromFilepath($this->getFilename(), $this->_db);
    $this->_slipMorph = new slipmorphfeature($this->_pos);
    if (!$this->getId()) {  //create a new slip entry
      $this->_isNew = true;
	    $this->_headword = lemmas::getLemma($this->getWid(), $this->getFilename)[0];
      $this->_extractWordClass($this->_pos);
      //get the entry
	    $this->_entry = entries::getEntryByHeadwordAndWordclass($this->getHeadword(), $this->getWordClass());
      $sql = <<<SQL
        INSERT INTO slips (filename, text_id, id, entry_id, preContextScope, postContextScope, ownedBy) 
        	VALUES (?, ?, ?, ?, ?, ?, ?);
SQL;
      $this->_db->exec($sql, array($this->_filename, $this->getTextId(),  $this->getWid(), $this->_entry->getId(),
	      $preScope, $postScope, $_SESSION["user"]));
      $this->setId($this->_db->getLastInsertId());  //sets the ID on the parent TODO: revisit this urgently!!
      $this->_saveSlipMorph();    //save the defaults to the DB
    }
    $sql = <<<SQL
        SELECT * FROM slips 
        WHERE auto_id = :auto_id
SQL;
    $result = $this->_db->fetch($sql, array(":auto_id" => $this->getId()));
    $slipData = $result[0];
	  $this->_entry = entries::getEntryById($slipData["entry_id"], $this->_db);
    $this->_populateClass($slipData);
    $this->_loadSlipMorph();  //load the slipMorph data from the DB
    $this->_loadSenses(); //load the sense objects
    return $this;
  }

	private function _loadSenses() {
		$sql = <<<SQL
        SELECT sense_id as id FROM slip_sense
        	WHERE slip_id = :auto_id 
SQL;
		$results = $this->_db->fetch($sql, array(":auto_id"=>$this->getId()));
		if ($results) {
			foreach ($results as $key => $value) {
				$id = $value["id"];
				$this->_senses[$id] = new sense($id, $this->_db); //create and store sense objects
				$this->_sensesInfo[$id]["name"] = $this->_senses[$id]->getName();  //store id and name for AJAX use
				$this->_sensesInfo[$id]["description"] = $this->_senses[$id]->getDescription();
			}
		}
		return $this;
	}

  private function _loadSlipMorph() {
    $this->_slipMorph->resetProps();
    $sql = <<<SQL
        SELECT * FROM slipMorph WHERE slip_id = ?
SQL;
    $results = $this->_db->fetch($sql, array($this->getId()));
    foreach ($results as $result) {
      $this->_slipMorph->setProp($result["relation"], $result["value"]);
    }
    return $this;
  }
  
  private function _saveSlipMorph() {
    $props = $this->_slipMorph->getProps();
    foreach ($props as $relation => $value) {
      $sql = <<<SQL
        INSERT INTO slipMorph(slip_id, relation, value) VALUES(?, ?, ?)
SQL;
      $this->_db->exec($sql, array($this->getId(), $relation, $value));
    }
  }

  private function _clearSlipMorphEntries() {
    $sql = <<<SQL
      DELETE FROM slipMorph WHERE slip_id = ?
SQL;
    $this->_db->exec($sql, array($this->getId()));
  }

  /**
   * Updates the results stored in the SESSION with the new slip ID
   * TODO: this no longer works within the new search engine - need to revisit SB
   */
  public function updateResults($index) {
    $_SESSION["results"][$index]["auto_id"] = $this->getId();
  }

  private function _extractWordClass($pos) {
    foreach ($this->_wordClasses as $class => $posArray) {
      if (in_array($pos, $posArray)) {
        $this->_wordClass = $class;
      }
    }
    if (empty($this->_wordClass)) {
    	$this->_wordClass = $pos;   //used for MSS which have full names instead of abbrevs for POS
    }
  }

  public function addCitation($citation) {
  	$this->_citations[$citation->getId()] = $citation;
  }

  public function getCitations() {
  	if (empty($this->_citations)) {
		  $sql = <<<SQL
			SELECT citation_id FROM slip_citation WHERE slip_id = :id ORDER BY citation_id ASC
SQL;
		  $results = $this->_db->fetch($sql, array(":id" => $this->getId()));
		  foreach ($results as $result) {
		  	$citationId = $result["citation_id"];
			  $this->_citations[$citationId] = new citation($this->_db, $citationId);
		  }
	  }
		return $this->_citations;
  }

  public function getSlipIsAttachedTiCitation($citationId) {
		return array_key_exists($citationId, $this->getCitations());
  }

  public function getScopeDefault() {
  	return self::SCOPE_DEFAULT;
  }

  public function getSlipMorph() {
    return $this->_slipMorph;
  }

  public function getFilename() {
    return $this->_filename;
  }

  public function getPOS() {
  	return $this->_pos;
  }

  public function getWid() {
    return $this->_wid;
  }

  public function getIsNew() {
    return $this->_isNew;
  }

  public function getStarred() {
    return $this->_starred;
  }

  public function getNotes() {
    return $this->_notes;
  }

  public function getPreContextScope() {
    return $this->_preContextScope;
  }

  public function getPostContextScope() {
    return $this->_postContextScope;
  }

  public function getEntryId() {
  	return $this->_entry->getId();
  }

  public function getEntry() {
  	return $this->_entry;
  }

  public function getWordClass() {
    return $this->_wordClass;
  }

  public function getHeadword() {
  	return $this->_headword;
  }

  public function getSenses() {
    return $this->_senses;
  }

  public function getSensesInfo() {
  	return $this->_sensesInfo;
  }

  public function getLocked() {
  	return $this->_locked;
  }

  public function getIsLocked() {
  	return $this->_locked == 1;
  }

  public function getSlipStatus() {
  	return $this->_slipStatus;
  }

  public function getOwnedBy() {
  	return $this->_ownedBy;
  }

  public function getLastUpdatedBy() {
    return $this->_lastUpdatedBy;
  }

  public function getLastUpdated() {
    return $this->_lastUpdated;
  }

  public function getTextId() {
  	return $this->_textId;
  }

	/**
	 * Fetches a list of all unused senses for this headword and wordclass combination
	 * @return array of sense objects
	 */
  public function getUnusedSenses() {
  	$senses = array();
		$sql = <<<SQL
			SELECT se.id AS id FROM sense se
				JOIN entry e ON e.id = se.entry_id
			  WHERE e.id = :entryId
SQL;
		$results = $this->_db->fetch($sql, array(":entryId"=>$this->getEntryId()));
		foreach ($results as $result) {
			$id = $result["id"];
			if (array_key_exists($id, $this->getSenses())) {  //skip exisiting senses for this slip
				continue;
			}
			$senses[$id] = new sense($id, $this->_db);
		}
		return $senses;
  }

  // SETTERS

	public function setPreContextScope($number) {
  	$this->_preContextScope = $number;
	}

	public function setPostContextScope($number) {
  	$this->_postContextScope = $number;
	}

  private function _populateClass($params) {
    $slipId = $this->getId() ? $this->getId() : $params["auto_id"];
    $this->setId($slipId);
    $this->_isNew = false;
    $this->_starred = $params["starred"] ? 1 : 0;
    $this->_notes = $params["notes"];
    $this->_preContextScope = $params["preContextScope"];
    $this->_postContextScope = $params["postContextScope"];
    $this->_headword = $this->_entry->getHeadword();
    $this->_wordClass = $this->_entry->getWordclass();
    $this->_entryId = $params["entryId"];
    $this->_locked = $params["locked"];
    $this->_slipStatus = $params["slipStatus"];
    $this->_ownedBy = $params["ownedBy"];
    $this->_lastUpdatedBy = $params["updatedBy"];
    $this->_lastUpdated = isset($params["lastUpdated"]) ? $params["lastUpdated"] : "";
    return $this;
  }

  public function saveSlip($params) {
    $params["updatedBy"] = $_SESSION["user"];
    $this->_populateClass($params);
    $this->_clearSlipMorphEntries();
    $this->_slipMorph->setType($this->getWordClass());
    $this->_slipMorph->populateClass($params);
    $this->_saveSlipMorph();
    $sql = <<<SQL
        UPDATE slips 
            SET text_id = ?, locked = ?, starred = ?, notes = ?, 
                entry_id = ?, preContextScope = ?, postContextScope = ?, slipStatus = ?,
             		updatedBy = ?, lastUpdated = now()
            WHERE auto_id = ?
SQL;
    $this->_db->exec($sql, array($this->getTextId(), $this->getLocked(), $this->getStarred(),
	    $this->getNotes(), $this->getEntryId(), $this->getPreContextScope(), $this->getPostContextScope(),
	    $this->getSlipStatus(), $this->getLastUpdatedBy(), $this->getId()));
    return $this;
  }

	/**
	 * Updates the pre and post context scope values in the database
	 */
	/*    !!DEPRECATED - handled in citation class now: TODO: remove all references to context scope in this class
	 *
  public function updateContexts() {
  	$sql = <<<SQL
			UPDATE slips 
				SET preContextScope = :pre, postContextScope = :post
				WHERE auto_id = :id
SQL;
  	$this->_db->exec($sql, array(":pre" => $this->getPreContextScope(), ":post" => $this->getPostContextScope(),
		  ":id" => $this->getAutoId()));
  }
	*/

  /*
   * Changes the entry for this slip when the headword or wordclass is changed
   * @param $headword
   * @param $wordclass
   */
  public function updateEntry($headword, $wordclass) {
  	if ($wordclass != $this->getWordClass()) {
		  $this->_wordClass = $wordclass;
		  //remove all the senses
		  sensecategories::deleteSensesForSlip($this->getId());
		  //hack to workaround POS issues - TODO: discuss with MM
		  $tempPOS = array("noun" => "n", "verb" => "v", "preposition" => "p", "verbal noun" => "vn", "adjective" => "a",
			  "adverb" => "A", "other" => "");
		  $this->_pos = $tempPOS[$wordclass];
		  $this->_slipMorph = new slipmorphfeature($this->_pos);  //attach the morph data for the new POS
		  $this->_clearSlipMorphEntries();
	  }
  	if ($headword != $this->getHeadword()) {
		  $this->_headword = $headword;
		  //remove all the senses
		  sensecategories::deleteSensesForSlip($this->getId());
	  }
	  $this->_entry = entries::getEntryByHeadwordAndWordclass($headword, $wordclass);
  }

  /**
   * Returns the array of word classes
   * @return array
   */
  public function getWordClasses() {
    return $this->_wordClasses;
  }
}