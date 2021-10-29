<?php

namespace models;

class corpus_slip extends slip
{
  private $_textId, $_filename, $_wid;

  public function __construct($filename, $wid, $auto_id = null, $pos) {
    $this->_filename = $filename;
    $this->_wid = $wid;
    //test if a slip already exists (if there is a slip with the same groupId, filename, id combination)
    $slipId = $auto_id ? $auto_id : collection::slipExists($_SESSION["groupId"], $filename, $wid);
    parent::__construct($slipId);
    $this->_pos = $pos;
    $this->_loadSlip();
  }

  private function _loadSlip() {
    $this->_textId = corpus_browse::getTextIdFromFilepath($this->getFilename(), $this->_db);
    $this->_slipMorph = new slipmorphfeature($this->getPOS());
    if (!$this->getId()) {  //create a new slip entry
      $this->_isNew = true;
	    $this->_headword = lemmas::getLemma($this->getWid(), $this->getFilename)[0];
      $this->extractWordClass($this->getPOS());
      //get the entry
	    $this->_entry = entries::getEntryByHeadwordAndWordclass($this->getHeadword(), $this->getWordClass());
      $sql = <<<SQL
        INSERT INTO slips (filename, text_id, id, entry_id, ownedBy) 
        	VALUES (?, ?, ?, ?, ?, ?, ?);
SQL;
      $this->_db->exec($sql, array($this->_filename, $this->getTextId(),  $this->getWid(), $this->_entry->getId(),
	      $_SESSION["user"]));
      $this->setId($this->_db->getLastInsertId());  //sets the ID on the parent TODO: revisit this urgently!!
      $this->mapssaveSlipMorph();    //save the defaults to the DB
    }
    $sql = <<<SQL
        SELECT * FROM slips 
        WHERE auto_id = :auto_id
SQL;
    $result = $this->_db->fetch($sql, array(":auto_id" => $this->getId()));
    $slipData = $result[0];
	  $this->_entry = entries::getEntryById($slipData["entry_id"], $this->_db);
    $this->populateClass($slipData);
    $this->loadSlipMorph();  //load the slipMorph data from the DB
    $this->loadSenses(); //load the sense objects
    return $this;
  }

  public function getFilename() {
    return $this->_filename;
  }

  public function getWid() {
    return $this->_wid;
  }

  public function getTextId() {
  	return $this->_textId;
  }
}