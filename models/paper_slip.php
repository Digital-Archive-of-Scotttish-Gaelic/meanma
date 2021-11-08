<?php

namespace models;

class paper_slip extends slip
{
	/**
	 * @param $entryId  : a paper slip always requires an entryId, regardless of whether is new or exisitng
	 * @param int $id  : an optional ID. If given, the paper slip will be loaded from the DB otherwise it will be
	 *  created
	 */
	public function __construct($id = null, $entryId, $wordform = null, $db) {
		parent::__construct($id, $db);
		$this->setType("paper");
		$this->_entryId = $entryId;
		if ($wordform) {
			$this->setWordform($wordform);
		}
		$this->_entry = entries::getEntryById($entryId, $this->_db);
		$this->_wordClass = $this->_entry->getWordclass();
		$this->setPOS($this->_wordClasses[$this->_wordClass][0]);   //set the first element of the wordClasses array
																																//for this wordClass as the default POS
		$this->__load();
	}

	private function __load() {
		$this->_slipMorph = new slipmorphfeature($this->getPOS());
		if (!$this->getId()) {  //create a new slip entry
			$this->_isNew = true;
			$this->_headword = $this->_entry->getHeadword();
			$sql = <<<SQL
        INSERT INTO slips (filename, text_id, id, entry_id, wordform, ownedBy) 
        	VALUES (?, ?, ?, ?, ?, ?);
SQL;
			$this->_db->exec($sql, array('', '', '', $this->_entry->getId(), $this->getWordform(),
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
		$this->populateClass($slipData);
		$this->loadSlipMorph();  //load the slipMorph data from the DB
		$this->loadSenses(); //load the sense objects
		return $this;
	}

}