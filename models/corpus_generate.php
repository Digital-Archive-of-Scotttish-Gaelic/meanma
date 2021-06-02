<?php

namespace models;

class corpus_generate
{

  private $_id; // the id number for the text in the corpus (obligatory)
  private $_filepaths = []; // an array of XML filepaths
  private $_lexemes = []; // an array of headwords
  private $_names = []; // an array of names

	private $_db;   // an instance of models\database

	public function __construct($id) {
		$this->_db = isset($this->_db) ? $this->_db : new database();
		$this->_id = $id;
		$this->_filepaths = $this->_getFilepaths($id);
    $this->_lexemes = $this->_getLexemes();
    $this->_names = $this->_getNames();
	}

  private function _getFilepaths($id) {
    $sql = <<<SQL
      SELECT filepath
        FROM text
        WHERE id = :id
SQL;
    $results = $this->_db->fetch($sql, array(":id" => $id));
    $textData = $results[0];
    if ($textData["filepath"]) {
      return [$textData["filepath"]];
    }
    else if ($id=="0") {
      $oot = [];
      $sql = <<<SQL
        SELECT filepath
          FROM text
SQL;
      $results = $this->_db->fetch($sql, array());
      foreach ($results as $nextResult) {
        if ($nextResult["filepath"]) {
          $oot[] = $nextResult["filepath"];
        }
      }
      return $oot;
    }
    else {
      $oot = [];
      $sql = <<<SQL
        SELECT id
          FROM text
          WHERE partOf = :id
SQL;
      $results = $this->_db->fetch($sql, array(":id" => $id));
      foreach ($results as $nextResult) {
        $oot2 = array_merge($oot,$this->_getFilepaths($nextResult["id"]));
        $oot = $oot2;
      }
      return $oot;
    }
  }

  private function _getLexemes() {
    $oot = [];
    foreach ($this->_filepaths as $nextFilepath) {
      $sql = <<<SQL
        SELECT lemma, pos
          FROM lemmas
          WHERE filename = :fp
SQL;
      $results = $this->_db->fetch($sql, array(":fp" => $nextFilepath));
      foreach ($results as $nextResult) {
        $lemma = $nextResult["lemma"];
        $pos = $nextResult["pos"];
        if (substr($pos,0,1)=='n') {
          $oot[] = $lemma . '|' . 'noun';
        }
        else if (substr($pos,0,1)=='v' || substr($pos,0,1)=='V') {
          $oot[] = $lemma . '|' . 'verb';
        }
        else if (substr($pos,0,1)=='a') {
          $oot[] = $lemma . '|' . 'adjective';
        }
        else if (substr($pos,0,1)=='A') {
          $oot[] = $lemma . '|' . 'adverb';
        }
        else if (substr($pos,0,1)=='p' || substr($pos,0,1)=='P') {
          $oot[] = $lemma . '|' . 'preposition';
        }
      }
    }
    //usort($oot,'models\functions::gdSort');
    $oot2 = [];
    foreach ($oot as $nextLexeme) {
      if ($oot2[$nextLexeme]) {
        $oot2[$nextLexeme]++;
      }
      else {
        $oot2[$nextLexeme] = 1;
      }
    }
    arsort($oot2);
    return $oot2;
  }

  private function _getNames() {
    $oot = [];
    foreach ($this->_filepaths as $nextFilepath) {
      $xml = simplexml_load_file('../xml/' . $nextFilepath);
      $xml->registerXPathNamespace('dasg', 'https://dasg.ac.uk/corpus/');
      $results = $xml->xpath('//dasg:n');
      foreach ($results as $nextResult) {
        $oot[] = $nextResult->asXML();
      }
    }
    return $oot;
  }


	// SETTERS

	// GETTERS

	public function getId() {
		return $this->_id;
	}

  public function getFilepaths() {
    return $this->_filepaths;
  }

  public function getLexemes() {
    return $this->_lexemes;
  }

  public function getNames() {
    return $this->_names;
  }


}
