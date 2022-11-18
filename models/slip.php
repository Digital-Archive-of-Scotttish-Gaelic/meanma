<?php

namespace models;

class slip
{
	const SCOPE_DEFAULT = 80;

	private $_id; //the slip ID (called 'auto_id' in the DB)
	protected $_type; //used to differentiate between types of slip, e.g. paper or corpus
	protected $_db; //an instance of models\database
	protected $_textId = null;
	protected $_text = null; //an instance of models\corpus_browse
	protected $_reference = null; //used for lexicographers to manually store a reference as HTML
	protected $_filename = null;
	protected $_wid = null;
	protected $_pos, $_wordform;
	protected $_senseId; //an optional ID to identify subsense record
	protected $_locked = 0;
	protected $_starred, $_notes, $_ownedBy, $_entryId, $_headword, $_slipStatus;
	protected $_wordClass, $_lastUpdatedBy, $_lastUpdated;
	protected $_isNew;
	protected $_wordClasses = array(
		"noun" => array("n", "nx", "ns", "N", "Nx"),
		"noun phrase" => array("nphr"),
		"verb" => array("v", "V", "vn"),
		"adjective" => array("a", "ar"),
		"preposition" => array("p", "P"),
		"adverb" => array("A"),
		"other" => array("d", "c", "z", "o", "D", "Dx", "ax", "px", "q", "nd", ""));
	protected $_entry;  //an instance of models\entry
	protected $_slipMorph;  //an instance of models\slipmorphfeature
	protected $_piles = array();
	protected $_pilesInfo = array();   //used to store pile info (in place of object data) for AJAX use
	protected $_citations;  //an array of citation objects

	public function __construct($id = null, $db) {
		$this->_id = $id;
		$this->_db = $db;
	}

	//GETTERS

	public function getId() {
		return $this->_id;
	}

	public function getDb() {
		return $this->_db;
	}

	/**
	 * ! This should be deprecated once automatic reference templates are in place
	 * @return string
	 */
	public function getReference() {
		return $this->_reference;
	}

	/**
	 * Will refactor this once old manual references are deprecated
	 */
	public function getReferenceTemplate() {
		$sql = "SELECT reference FROM text WHERE id = :id";
		$result = $this->_db->fetch($sql, array(":id"=>$this->getTextId()));
		return $result[0]["reference"];
	}

	/**
	 * Parses the reference template and returns a completed reference
	 */
	public function getReferenceFromTemplate() {
		$template = $this->getReferenceTemplate();
		$reference = str_ireplace("%p", "p.{$this->getPage()}", $template);
		return $reference;
	}

	public function getType() {
		return $this->_type;
	}

	public function getPOS() {
		return $this->_pos;
	}

	public function getWordform() {
		return $this->_wordform;
	}

	public function getScopeDefault() {
		return self::SCOPE_DEFAULT;
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

	public function getText() {
		if (!$this->_text) {
			$this->_text = new corpus_browse($this->getTextId(), $this->_db);
		}
		return $this->_text;
	}

	public function getSenseId() {
		if (!$this->_senseId) {
			$this->_senseId = null;
		}
		return $this->_senseId;
	}

	public function getSense() {
		if ($this->getSenseId()) {
			return new sense($this->_db, $this->getSenseId());
		}
		return null;
	}

	public function getSlipIsAttachedTiCitation($citationId) {
		return array_key_exists($citationId, $this->getCitations());
	}

	/**
	 * Fetches a list of all unused piles for this headword and wordclass combination
	 * @return array of sense objects
	 */
	public function getUnusedPiles() {
		$piles = array();
		$sql = <<<SQL
			SELECT se.id AS id FROM sense se
				JOIN entry e ON e.id = se.entry_id
			  WHERE e.id = :entryId
SQL;
		$results = $this->_db->fetch($sql, array(":entryId"=>$this->getEntryId()));
		foreach ($results as $result) {
			$id = $result["id"];
			if (array_key_exists($id, $this->getPiles())) {  //skip existing piles for this slip
				continue;
			}
			$piles[$id] = new pile($id, $this->_db);
		}
		return $piles;
	}

	public function getSlipMorph() {
		return $this->_slipMorph;
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

	public function getEntryId() {
		return $this->_entry->getId();
	}

	public function getEntry() {
		return $this->_entry;
	}

	public function getWordClass() {
		return $this->_wordClass;
	}

	/**
	 * Returns the array of word classes
	 * @return array
	 */
	public function getWordClasses() {
		return $this->_wordClasses;
	}

	public function getHeadword() {
		return $this->_headword;
	}

	public function getPiles() {
		return $this->_piles;
	}

	public function getPilesInfo() {
		return $this->_pilesInfo;
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

	//SETTERS

	protected function setId($id) {
		$this->_id = $id;
	}

	protected function setType($type) {
		$this->_type = $type;
	}

	protected function setPOS($pos) {
		$this->_pos = $pos;
	}

	public function setWordform($form) {
		$this->_wordform = $form;
	}

	// METHODS

	protected function populateClass($params) {
		$slipId = $this->getId() ? $this->getId() : $params["auto_id"];
		$this->setId($slipId);
		$this->_isNew = false;
		$this->_starred = $params["starred"] ? 1 : 0;
		$this->_notes = $params["notes"];
		$this->_textId = $params["text_id"];
		$this->_reference = $params["reference"];
		$this->_headword = $this->_entry->getHeadword();
		$this->_wordClass = $this->_entry->getWordclass();
		$this->_entryId = $params["entryId"];
		$this->_senseId = $params["subsense_id"];
		$this->setWordform($params["wordform"]);
		$this->_locked = $params["locked"];
		$this->_slipStatus = $params["slipStatus"];
		$this->_ownedBy = $params["ownedBy"];
		$this->_lastUpdatedBy = $params["updatedBy"];
		$this->_lastUpdated = isset($params["lastUpdated"]) ? $params["lastUpdated"] : "";
		return $this;
	}

	public function saveSlip($params) {
		$params["updatedBy"] = $_SESSION["user"];
		$this->populateClass($params);
		$this->_clearSlipMorphEntries();
		$this->_slipMorph->setType($this->getWordClass());
		$this->_slipMorph->populateClass($params);
		$this->saveSlipMorph();
		//ensure locked has some value
		$locked = $this->getLocked() ? $this->getLocked() : 0;
		$sql = <<<SQL
        UPDATE slips 
            SET text_id = ?, reference = ?, locked = ?, starred = ?, notes = ?, 
                entry_id = ?, subsense_id = ?, wordform = ?, slipStatus = ?, 
             		updatedBy = ?, lastUpdated = now()
            WHERE auto_id = ?
SQL;
		$this->_db->exec($sql, array($this->getTextId(), $this->getReference(), $locked, $this->getStarred(),
			$this->getNotes(), $this->getEntryId(), $this->getSenseId(), $this->getWordform(),
			$this->getSlipStatus(), $this->getLastUpdatedBy(), $this->getId()));
		return $this;
	}

	public function unassignSense() {
		$sql = "UPDATE slips SET subsense_id = null WHERE auto_id = {$this->getId()}";
		$this->_db->exec($sql);
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
			pilecategories::deletePilesForSlip($this->getId());
			//hack to workaround POS issues - TODO: discuss with MM
			$tempPOS = array("noun" => "n", "noun phrase" => "nphr", "verb" => "v", "preposition" => "p", "verbal noun" => "vn",
				"adjective" => "a", "adverb" => "A", "other" => "");
			$this->setPOS($tempPOS[$wordclass]);
			$this->_slipMorph = new slipmorphfeature($this->getPOS());  //attach the morph data for the new POS
			$this->_clearSlipMorphEntries();
		}
		if ($headword != $this->getHeadword()) {
			$this->_headword = $headword;
			//remove all the senses
			pilecategories::deletePilesForSlip($this->getId());
		}
		$this->_entry = entries::getEntryByHeadwordAndWordclass($headword, $wordclass, $this->_db);
	}

	protected function loadPiles() {
		$sql = <<<SQL
        SELECT sense_id as id FROM slip_sense
        	WHERE slip_id = :auto_id 
SQL;
		$results = $this->_db->fetch($sql, array(":auto_id"=>$this->getId()));
		if ($results) {
			foreach ($results as $key => $value) {
				$id = $value["id"];
				$this->_piles[$id] = new pile($id, $this->_db); //create and store pile objects
				$this->_pilesInfo[$id]["name"] = $this->_piles[$id]->getName();  //store id and name for AJAX use
				$this->_pilesInfo[$id]["description"] = $this->_piles[$id]->getDescription();
			}
		}
		return $this;
	}

	protected function loadSlipMorph() {
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

	protected function saveSlipMorph() {
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

	protected function extractWordClass($pos) {
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

	/**
	 * Returns the first citation of type $type in the _citations array
	 * @param $type string type
	 * @return citation object
	 */
	public function getCitationByType($type) {
		$citations = $this->getCitations();
		foreach ($citations as $citation) {
			if ($citation->getType() == $type) {
				return $citation;
			}
		}
	}

	/**
	 * Returns ALL citations of type $type in the _citations array
	 * @param $type string type
	 * @return array of citation objects
	 */
	public function getCitationsByType($type) {
		$citations = $this->getCitations();
		$citationsByType = array();
		foreach ($citations as $citation) {
			if ($citation->getType() == $type) {
				$citationsByType[] = $citation;
			}
		}
		return $citationsByType;
	}

	/**
	 * @return string HTML anchor with link for opening the modal for this slip
	 */
	public function getSlipLinkHtml() {
		$slipLinkText = $this->getId();
		$html = <<<HTML
        <a href="#" data-url="class="slipLink2"
            data-toggle="modal" data-target="#slipModal"
            data-auto_id="{$this->getId()}"
            data-headword="{$this->getHeadword()}"
            data-page="{$this->getPage()}"
            data-pos="{$this->getPOS()}"
            data-id="{$this->getWid()}"
            data-filename="{$this->getFilename()}"
            data-entryid="{$this->getEntryId()}"
            data-date_internal="{$this->getText()->getDate()}"
        >
            {$slipLinkText}
        </a>
HTML;
		return $html;
	}

	/*
	 * TEMP SB
	 * Convenience method until we code this properly using page numbers in the slips table
	 */
	public function getPage() {
		$sql = <<<SQL
			SELECT page FROM lemmas WHERE filename = ? AND id = ?
SQL;
		$result = $this->_db->fetch($sql, array($this->getFilename(), $this->getWid()));
		if ($result) {
			return $result[0]["page"];
		}
	}
}