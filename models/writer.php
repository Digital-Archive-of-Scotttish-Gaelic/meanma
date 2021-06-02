<?php

namespace models;

class writer
{

	private $_id; // obligatory
	private $_surnameGD;
	private $_forenamesGD;
	private $_surnameEN;
	private $_forenamesEN;
	private $_title;
	private $_nickname;
	private $_yearOfBirth;
	private $_yearOfDeath;
	private $_origin;
	private $_origin2;
	private $_preferredName;
	private $_notes;
	private $_lastUpdated;

  private $_db; //an instance of models\database

	public function __construct($id) {
		$this->_db = isset($this->_db) ? $this->_db : new database();
		$this->_id = $id;
		if ($this->_id) {
			$this->_load();   //loads data if there is an existing record
		}
	}

  private function _load() {
		$sql = <<<SQL
			SELECT *
				FROM writer
				WHERE id = :id
SQL;
		$results = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$writerData = $results[0];
		$this->_setSurnameGD($writerData["surname_gd"]);
		$this->_setForenamesGD($writerData["forenames_gd"]);
		$this->_setSurnameEN($writerData["surname_en"]);
		$this->_setForenamesEN($writerData["forenames_en"]);
		$this->_setTitle($writerData["title"]);
		$this->_setNickname($writerData["nickname"]);
		$this->_setYearOfBirth($writerData["yob"]);
		$this->_setYearOfDeath($writerData["yod"]);
		$this->_setOrigin($writerData["district_1_id"]);
	  $this->_setOrigin2($writerData["district_2_id"]);
		$this->_setPreferredName($writerData["preferred_name"]);
		$this->_setNotes($writerData["notes"]);
		$this->_setLastUpdated($writerData["lastUpdated"]);
	}

	// SETTERS

	private function _setSurnameGD($name) {
		$this->_surnameGD = $name;
	}

	private function _setForenamesGD($names) {
		$this->_forenamesGD = $names;
	}

	private function _setSurnameEN($name) {
		$this->_surnameEN = $name;
	}

	private function _setForenamesEN($names) {
		$this->_forenamesEN = $names;
	}

	private function _setTitle($title) {
		$this->_title = $title;
	}

	private function _setNickname($name) {
		$this->_nickname = $name;
	}

	private function _setYearOfBirth($year) {
		$this->_yearOfBirth = $year;
	}

	private function _setYearOfDeath($year) {
		$this->_yearOfDeath = $year;
	}

	private function _setOrigin($id) {
		$this->_origin = $id;
	}

	private function _setOrigin2($id) {
		$this->_origin2 = $id;
	}

	private function _setPreferredName($name) {
		$this->_preferredName = $name;
	}

	private function _setNotes($notes) {
		$this->_notes = $notes;
	}

	private function _setLastUpdated($timestamp) {
		$this->_lastUpdated = $timestamp;
	}

	// GETTERS

	public function getId() {
		return $this->_id;
	}

	public function getSurnameGD() {
		return $this->_surnameGD;
	}

	public function getForenamesGD() {
		return $this->_forenamesGD;
	}

	public function getSurnameEN() {
		return $this->_surnameEN;
	}

	public function getForenamesEN() {
		return $this->_forenamesEN;
	}

	public function getTitle() {
		return $this->_title;
	}

  public function getFullNameEN() {
		return $this->getTitle() . ' ' . $this->getForenamesEN() . ' <strong>' . $this->getSurnameEN() . '</strong>';
	}

	public function getFullNameGD() {
		return $this->getForenamesGD() . ' <strong>' . $this->getSurnameGD() . '</strong>';
	}

	public function getNickname() {
		return $this->_nickname;
	}

	public function getYearOfBirth() {
		return $this->_yearOfBirth;
	}

	public function getYearOfDeath() {
		return $this->_yearOfDeath;
	}

	public function getLifeSpan() {
		if ($this->getYearOfBirth() == "" && $this->getYearOfDeath() == "") { return ""; }
		return $this->getYearOfBirth() . '–' . $this->getYearOfDeath();
	}

	public function getOrigin() {
		return $this->_origin;
	}

	public function getOrigin2() {
		return $this->_origin2;
	}

	public function getPreferredName() {
		return $this->_preferredName;
	}

	public function getNotes() {
		return $this->_notes;
	}

	public function getLastUpdated() {
		return $this->_lastUpdated;
	}

	/**
	 * Queries the database (on the fly to aid performance),
	 *  creates and returns text objects for this writer
	 * @return array of models\text objects
	 */
	public function getTexts() {
		$texts = array();
		$sql = <<<SQL
			SELECT text_id FROM text_writer WHERE writer_id = :writerId
SQL;
		$results = $this->_db->fetch($sql, array(":writerId" => $this->getId()));
		foreach ($results as $result) {
			$texts[$result["text_id"]] = new corpus_browse($result["text_id"]);
		}
		return $texts;
	}

	/**
	 * Queries the database for text titles written by this writer
	 * @return array of database results
	 */
	public function getTextTitles() {
		$sql = <<<SQL
			SELECT title FROM text t
				JOIN text_writer tw ON tw.text_id = t.id
				WHERE tw.writer_id = :writerId
SQL;
		$results = $this->_db->fetch($sql, array(":writerId" => $this->getId()));
		return $results[0];
	}

}
