<?php


class FileHandler
{
  private $_filename, $_filenameElems, $_textId;

  public function __construct($filename)
  {
    $this->_filename = $filename;
  }

  public function parseFile() {
    $fileContents = "";
    $this->_filenameElems = explode(' ', $this->_filename);
    $this->_textId = $this->_filenameElems[0];

    $xml = new XMLOutput();

    //$fileContents = htmlspecialchars($header->get($textId), ENT_HTML5, ENT_NOQUOTES, 'UTF-8') ;
    $fileContents .= $xml->getHeader($this->_textId);
    $text = file_get_contents(INPUT_FILEPATH . $this->_filename);
    $tokeniser = new Tokeniser();
    $fileContents .= $tokeniser->run($text);
    $fileContents .= $xml->getFooter();
    file_put_contents(OUTPUT_FILEPATH . $this->_getOutputFilename(), $fileContents);
  }

  private function _getOutputFilename()
  {
    $outputFilename = $this->_textId;
    foreach ($this->_filenameElems as $elem) {
      if ($elem == $this->_textId) {
        continue;
      }
      $outputFilename .= '_' . $elem;
    }
    $outputFilename = str_replace(
      array("_teacsa", "'", "à", "è", "ì", "ò", "ù", "À", "È", "Ì", "Ò", "Ù"),
      array("", "_", "aa", "ee", "ii", "oo", "uu", "AA", "EE", "II", "OO", "UU"),
      $outputFilename);
    return str_replace(".txt", ".xml", $outputFilename);
  }
}