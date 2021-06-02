<?php

namespace models;

class slip
{
	const SCOPE_DEFAULT = 80;

  private $_auto_id, $_filename, $_id, $_pos, $_db;
  private $_starred, $_translation, $_notes, $_locked, $_ownedBy, $_entryId, $_headword, $_status;
  private $_preContextScope, $_postContextScope, $_wordClass, $_lastUpdatedBy, $_lastUpdated;
  private $_isNew;
  private $_wordClasses = array(
    'noun' => array("n", "nx", "ns", "N", "Nx"),
    "verb" => array("v", "V", "vn"),
    "adjective" => array("a", "ar"),
    "preposition" => array("p", "P"),
    "adverb" => array("A"),
    "other" => array("d", "c", "z", "o", "D", "Dx", "ax", "px", "q"));
  private $_entry;  //an instance of models\entry
  private $_slipMorph;  //an instance of models\slipmorphfeature
  private $_senses = array();
  private $_sensesInfo = array();   //used to store sense info (in place of object data) for AJAX use

  public function __construct($filename, $id, $auto_id = null, $pos, $preScope = self::SCOPE_DEFAULT, $postScope = self::SCOPE_DEFAULT) {
    $this->_filename = $filename;
    $this->_id = $id;
    $this->_headword = lemmas::getLemma($this->_id, $this->_filename)[0];
    //test if a slip already exists (if there is a slip with the same groupId, filename, id combination)
    $this->_auto_id = $auto_id ? $auto_id : collection::slipExists($_SESSION["groupId"], $filename, $id);
    $this->_pos = $pos;
    if (!isset($this->_db)) {
      $this->_db = new database();
    }
    $this->_loadSlip($preScope, $postScope);
  }

  private function _loadSlip($preScope, $postScope) {
    $this->_slipMorph = new slipmorphfeature($this->_pos);
    if (!$this->getAutoId()) {  //create a new slip entry
      $this->_isNew = true;
      $this->_extractWordClass($this->_pos);
      //get the entry
	    $this->_entry = entries::getEntryByHeadwordAndWordclass($this->getHeadword(), $this->getWordClass());
      $sql = <<<SQL
        INSERT INTO slips (filename, id, entry_id, preContextScope, postContextScope, ownedBy) VALUES (?, ?, ?, ?, ?, ?);
SQL;
      $this->_db->exec($sql, array($this->_filename, $this->_id, $this->_entry->getId(), $preScope, $postScope,
	      $_SESSION["user"]));
      $this->_auto_id = $this->_db->getLastInsertId();
      $this->_saveSlipMorph();    //save the defaults to the DB
    }
    $sql = <<<SQL
        SELECT * FROM slips 
        WHERE auto_id = :auto_id
SQL;
    $result = $this->_db->fetch($sql, array(":auto_id" => $this->_auto_id));
    $slipData = $result[0];
	  $this->_entry = entries::getEntryById($slipData["entry_id"]);
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
		$results = $this->_db->fetch($sql, array(":auto_id"=>$this->getAutoId()));
		if ($results) {
			foreach ($results as $key => $value) {
				$id = $value["id"];
				$this->_senses[$id] = new sense($id); //create and store sense objects
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
    $results = $this->_db->fetch($sql, array($this->getAutoId()));
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
      $this->_db->exec($sql, array($this->getAutoId(), $relation, $value));
    }
  }

  private function _clearSlipMorphEntries() {
    $sql = <<<SQL
      DELETE FROM slipMorph WHERE slip_id = ?
SQL;
    $this->_db->exec($sql, array($this->_auto_id));
  }

  /**
   * Updates the results stored in the SESSION with the new auto_id
   * TODO: this no longer works within the new search engine - need to revisit SB
   */
  public function updateResults($index) {
    $_SESSION["results"][$index]["auto_id"] = $this->getAutoId();
  }

  private function _extractWordClass($pos) {
    foreach ($this->_wordClasses as $class => $posArray) {
      if (in_array($pos, $posArray)) {
        $this->_wordClass = $class;
      }
    }
  }

  public function getScopeDefault() {
  	return self::SCOPE_DEFAULT;
  }

  public function getSlipMorph() {
    return $this->_slipMorph;
  }

  public function getAutoId() {
    return $this->_auto_id;
  }

  public function getFilename() {
    return $this->_filename;
  }

  public function getPOS() {
  	return $this->_pos;
  }

  public function getId() {
    return $this->_id;
  }

  public function getIsNew() {
    return $this->_isNew;
  }

  public function getStarred() {
    return $this->_starred;
  }

  public function getTranslation() {
    return $this->_translation;
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

  public function getStatus() {
  	return $this->_status;
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
			$senses[$id] = new sense($id);
		}
		return $senses;
  }

  private function _populateClass($params) {
    $this->_auto_id = $this->getAutoId() ? $this->getAutoId() : $params["auto_id"];
    $this->_isNew = false;
    $this->_starred = $params["starred"] ? 1 : 0;
    $this->_translation = $params["translation"];
    $this->_notes = $params["notes"];
    $this->_preContextScope = $params["preContextScope"];
    $this->_postContextScope = $params["postContextScope"];
    $this->_wordClass = $this->_entry->getWordclass();
    $this->_entryId = $params["entryId"];
    $this->_locked = $params["locked"];
    $this->_status = $params["status"];
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
            SET locked = ?, starred = ?, translation = ?, notes = ?, 
                entry_id = ?, preContextScope = ?, postContextScope = ?, status = ?,
             		updatedBy = ?, lastUpdated = now()
            WHERE auto_id = ?
SQL;
    $this->_db->exec($sql, array($this->getLocked(), $this->getStarred(), $this->getTranslation(),
	    $this->getNotes(), $this->getEntryId(), $this->getPreContextScope(), $this->getPostContextScope(),
	    $this->getStatus(), $this->getLastUpdatedBy(), $this->getAutoId()));
    return $this;
  }

  /*
   * Changes the entry for this slip when the headword or wordclass is changed
   * @param $headword
   * @param $wordclass
   */
  public function updateEntry($headword, $wordclass) {
  	if ($wordclass != $this->getWordClass()) {
		  $this->_wordClass = $wordclass;
		  //remove all the senses
		  sensecategories::deleteSensesForSlip($this->getAutoId());
		  //hack to workaround POS issues - TODO: discuss with MM
		  $tempPOS = array("noun" => "n", "verb" => "v", "preposition" => "p", "verbal noun" => "vn", "adjective" => "a",
			  "adverb" => "A", "other" => "x"); //TODO check with MM if "x" for other is an OK hack
		  $this->_pos = $tempPOS[$wordclass];
		  $this->_slipMorph = new slipmorphfeature($this->_pos);  //attach the morph data for the new POS
		  $this->_clearSlipMorphEntries();
	  }
	  $this->_headword = $headword;
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