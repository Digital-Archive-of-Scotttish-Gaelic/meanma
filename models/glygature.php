<?php


namespace models;


class glygature
{
	private $_element;  //the element with the ID passed to the constructor

	public function __construct($id) {
		$xml = simplexml_load_file(INPUT_FILEPATH . "804_mss/corpus.xml");
		$xml->registerXPathNamespace('tei','https://dasg.ac.uk/corpus/');
		$results = $xml->xpath("//tei:glyph[@xml:id='{$id}']");
		$this->_element = $results[0];
	}

	public function getName() {
		return $this->_element->glyphName;
	}

	public function getNote() {
		return $this->_element->note;
	}

	public function getCorresp() {
		return $this->_element->attributes()->corresp;
	}
}