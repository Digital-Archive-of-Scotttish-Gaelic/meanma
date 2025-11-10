<?php


namespace models;


class hand
{
	private $_id, $_element;

	/**
	 * hand constructor.
	 * @param $id
	 */
	public function __construct($id) {
		$xml = simplexml_load_file(INPUT_FILEPATH . "corpus.xml");
		$xml->registerXPathNamespace('tei','https://dasg.ac.uk/corpus/');
		$this->_id = $id;
		$results = $xml->xpath("//tei:handNote[@xml:id='{$id}']");
		$this->_element = $results[0];
	}

	public function getId() {
		return $this->_id;
	}

	public function getSurname() {
		return $this->_element->surname;
	}

	public function getForename() {
		return $this->_element->forename;
	}

	public function getCentury() {
		return $this->_element->date;
	}

	public function getAffiliation() {
		return $this->_element->affiliation;
	}

	public function getRegion() {
		return $this->_element->region;
	}

	public function getNote() {
		return $this->_element->note;
	}

	public function getWriterId() {
		$db = new database();
		$result = $db->fetch("SELECT id FROM writer WHERE surname_en = :handId", array(":handId" => $this->getId()));
		return $result[0];
	}
}