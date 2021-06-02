<?php

/**
 * Class Lemmatiser_DB
 *
 * !! This has been deprecated - remains just in case it's needed for future reference !!
 */
class Lemmatiser_DB
{
  private $_inputXml;
  private $_dbh;

  public function __construct($xml) {
    $this->_inputXml = $xml;
    //connect to the database
    $this->_dbh = Database::getDatabaseHandle(DB_NAME);
  }

  /*
   * Main processor for XML generateion
   * Can deal with multiple level texts
   */
  public function getProcessedXml() {
    $text = new SimpleXMLElement($this->_inputXml);
    if (isset($text->text)) {             //check texts within texts
      $xml = "";
      foreach ($text->text as $subtext) {
        $xml .= "\n" . $this->_processTextXml($subtext);
      }
      return $xml;
    } else {
      return $this->_processTextXml($text);    //single text
    }
  }

  /*
   * Private function to process individual text
   */
  private function _processTextXml($text) {
    foreach ($text->p as $p) {
      if (isset($p->w)) {
        foreach ($p->w as $word) {
          $wordform = (string)$word;
          $hits = $this->_getLemmas($wordform);
          $lemmasGlued = implode(' ', $hits["lemma"]);
          $pos1Glued = implode(' ', $hits["pos1"]);
          $word["lemma"] = $lemmasGlued;
          $word["pos"] = $pos1Glued;
        }
      }
    }
    return $text->asXML();
  }

  private function _getLemmas($wordform) {
    $wordform = $this->_prepareWordform($wordform);
    $hits["lemma"] = array();
    $hits["pos1"] = array();
    //Searches the multidict DB in MySQL
    $query = <<<SQL
        SELECT DISTINCT lemma, pos1 FROM lemmas WHERE wordform = :wordform
SQL;
    $sth = $this->_dbh->prepare($query);
    $sth->execute(array(":wordform" => $wordform));
    $results = $sth->fetchAll();
    if (count($results)) {
      $i = 0;
      foreach ($results as $result) {
        $hits["lemma"][$i] = $result["lemma"];
        $hits["pos1"][$i] = $result["pos1"];
        $i++;
      }
    }
    return $hits;
  }

  private function _prepareWordform($wordform) {
    $wordform = mb_strtolower($wordform);
    $wordform = preg_replace('/^([a-z]{1})h([a-z]+)/', "$1$2", $wordform);
    return $wordform;
  }
}