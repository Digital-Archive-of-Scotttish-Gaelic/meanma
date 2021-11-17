<?php

namespace models;

class corpus_slip extends slip
{
  public function __construct($filename, $wid, $auto_id = null, $pos, $db) {
    $this->_filename = $filename;
    $this->_wid = $wid;
    //test if a slip already exists (if there is a slip with the same groupId, filename, id combination)
    $slipId = $auto_id ? $auto_id : collection::slipExists($_SESSION["groupId"], $filename, $wid, $db);
    parent::__construct($slipId, $db);
    $this->setType("corpus");
    $this->_pos = $pos;
    $this->_load();
  }

  private function _load() {
    $this->_textId = corpus_browse::getTextIdFromFilepath($this->getFilename(), $this->_db);
    $this->_slipMorph = new slipmorphfeature($this->getPOS());
    if (!$this->getId()) {  //create a new slip entry
      $this->_isNew = true;
	    $this->_headword = lemmas::getLemma($this->getWid(), $this->getFilename())[0];
      $this->extractWordClass($this->getPOS());
      //get the entry
	    $this->_entry = entries::getEntryByHeadwordAndWordclass($this->getHeadword(), $this->getWordClass(), $this->_db);
      $sql = <<<SQL
        INSERT INTO slips (filename, text_id, id, entry_id, ownedBy) 
        	VALUES (?, ?, ?, ?, ?);
SQL;
      $this->_db->exec($sql, array($this->_filename, $this->getTextId(),  $this->getWid(), $this->_entry->getId(),
	      $_SESSION["user"]));
      $this->setId($this->_db->getLastInsertId());  //sets the ID on the parent
      $this->saveSlipMorph();    //save the defaults to the DB
    }
    $sql = <<<SQL
        SELECT * FROM slips 
        WHERE auto_id = :auto_id
SQL;
    $result = $this->_db->fetch($sql, array(":auto_id" => $this->getId()));
    $slipData = $result[0];
		$slipData["text_id"] = $this->getTextId();
	  $this->_entry = entries::getEntryById($slipData["entry_id"], $this->_db);
    $this->populateClass($slipData);
    $this->loadSlipMorph();  //load the slipMorph data from the DB
    $this->loadSenses(); //load the sense objects
    return $this;
  }
}