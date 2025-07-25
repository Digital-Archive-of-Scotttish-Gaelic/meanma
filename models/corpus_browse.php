<?php

namespace models;

class corpus_browse // models a corpus text or subtext
{

  private $_id; // the id number for the text in the corpus (obligatory)
	private $_parentText; // the parent text of this text (optional) – an instance of models\corpus_browse
  private $_title; // the title of the text (optional)
  private $_date, $_displayDate, $_publicationDate;
	private $_level, $_notes;
	private $_referenceTemplate;  // the template used to generate a text reference
  private $_type; //used to flag manuscripts ("ms")
  private $_filepath; // the path to the text XML (simple texts only)
  private $_transformedText; // simple texts only
  private $_writers = array();  //array of models\writer objects
	private $_writerIds = array();  //array of writer IDs for quicker performance when required

	private $_db;   // an instance of models\database

	public function __construct($id, $db) {
		$this->_db = $db;
		$this->_id = $id;
		if ($id != "0") { // not the root corpus node, i.e. a text
			$this->_load();
		}
	}

	/**
	 * Populates the object from the DB
	 */
	private function _load() {
		$sql = <<<SQL
			SELECT title, partOf, filepath, date, date_display, date_publication, level, notes, type, reference
				FROM text
				WHERE id = :id
SQL;
		$results = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$textData = $results[0];
		$this->_setTitle($textData["title"]);
		if ($parentTextId = $textData["partOf"]) {    // create a parent text
			$this->_setParentText($parentTextId);
		}
		else {
			$this->_setParentText("0"); // the root corpus
		}
		if ($filepath = $textData["filepath"]) {
			$this->_setFilepath($filepath);
		}
		if ($date = $textData["date"]) {
			$this->_setDate($date);
		}
		if ($displayDate = $textData["date_display"]) {
			$this->_setDisplayDate($displayDate);
		}
		if ($publicationDate = $textData["date_publication"]) {
			$this->_setPublicationDate($publicationDate);
		}
		if ($level = $textData["level"]) {
			$this->_setLevel($level);
		}
		if ($notes = $textData["notes"]) {
			$this->_setNotes($notes);
		}
		if ($type = $textData["type"]) {
			$this->_setType($type);
		}
		if ($reference = $textData["reference"]) {
			$this->_setReferenceTemplate($reference);
		}
		$this->_setWriters();
	}

	// SETTERS

	/**
	 * Creates a new text instance for parent text
	 * @param $id
	 */
	private function _setParentText($id) {
		$this->_parentText = new corpus_browse($id, $this->_db);
	}

	private function _setTitle($title) {
		$this->_title = $title;
	}

	private function _setFilepath($filepath) {
		$this->_filepath = $filepath;
	}

	private function _setDate($date) {
		$this->_date = $date;
	}

	private function _setDisplayDate($date) {
		$this->_displayDate = $date;
	}

	private function _setPublicationDate($date) {
		$this->_publicationDate = $date;
	}

	private function _setLevel($level) {
		$this->_level = $level;
	}

	private function _setNotes($notes) {
		$this->_notes = $notes;
	}

	private function _setType($type) {
		$this->_type = $type;
	}

	private function _setReferenceTemplate($reference) {
		$this->_referenceTemplate = $reference;
	}

	/**
	 * Populates the array of models\writer objects for this text
	 */
	private function _setWriters() {
		$sql = <<<SQL
			SELECT writer_id
				FROM text_writer
				WHERE text_id = :id
SQL;
		$results = $this->_db->fetch($sql, array(":id" => $this->getId()));
		foreach ($results as $result) {
			$this->_writerIds[] = $result["writer_id"];
			$this->_writers[] = new writer($result["writer_id"]);
		}
	}

	// GETTERS
	public function getId() {
		return $this->_id;
	}

	public function getTitle() {
		return $this->_title;
	}

	public function getDate() {
		return $this->_date;
	}

	public function getDisplayDate() {
		return $this->_displayDate;
	}

	public function getPublicationDate() {
		return $this->_publicationDate;
	}

	public function getLevel() {
		return $this->_level;
	}

	public function getNotes() {
		return $this->_notes;
	}

	public function getType() {
		return $this->_type;
	}

	public function getReferenceTemplate() {
		return $this->_referenceTemplate;
	}

	/**
	 * @return models\corpus_browse object
	 */
	public function getParentText() {
		return $this->_parentText;
	}

  public function getWriters() {
    return $this->_writers;
  }

  public function getWriterIds() {
		return $this->_writerIds;
  }

  public function getFilepath() {
    return $this->_filepath;
  }

  public function getTransformedText() {
    $this->_transformedText = @$this->_applyXSLT();
    return $this->_transformedText;
  }

  private function _applyXSLT() {
		$xslFilepath = $this->getType() == "ms" ? "xsl/semiDiplomatic.xsl" : "xsl/corpus.xsl";
		$xmlFilepath = INPUT_FILEPATH . $this->getFilepath();
    if ($this->getFilepath() != '') {
    	try {
        if (!$text = new \SimpleXMLElement($xmlFilepath, 0, true)) {
        	throw new \Exception("Text contents not found");
        }
	    } catch (\Exception $e) {
    		echo $e->getMessage();
		    return;
	    }
      $xsl = new \DOMDocument;
      $xsl->load($xslFilepath);
      $proc = new \XSLTProcessor;
      $proc->importStyleSheet($xsl);
      return $proc->transformToXML($text);
    }
  }

	/**
	 * Get child text info (on the fly to cut down on memory overhead)
	 * @return array of associative info ("id" => array("title", "level")
	 */
	public function getChildTextsInfo() {
		$childTextsInfo = array();
		$sql = <<<SQL
			SELECT id, title, date, date_display, level FROM text WHERE partOf = :id ORDER BY CAST(id AS UNSIGNED) ASC
SQL;
		$results = $this->_db->fetch($sql, array(":id" => $this->getId()));
		foreach ($results as $result) {
			$childTextsInfo[$result["id"]] = array("title" => $result["title"], "date" => $result["date"],
				"dateDisplay" => $result["date_display"], "level" => $result["level"]);
			//check for writers of child texts
			$writerSql = <<<SQL
				SELECT w.id AS id, surname_en 
					FROM writer w
					JOIN text_writer tw ON tw.writer_id = w.id AND text_id = :id 
SQL;
			$writerResults = $this->_db->fetch($writerSql, array(":id" => $result["id"]));
			if (!empty($writerResults)) {
				$writersInfo = array();
				foreach ($writerResults as $w) {
					$writersInfo[$w["id"]] = $w["surname_en"];
				}
				$childTextsInfo[$result["id"]]["writers"] = $writersInfo;
			}
		}
		return $childTextsInfo;
	}

  /**
   * Queries the DB for a list of text info
   * @return array of text and writer information
   */
  public function getTextList() {
    $sql = <<<SQL
      SELECT * FROM text ORDER BY CAST(id AS UNSIGNED) ASC
SQL;
    foreach ($this->_db->fetch($sql) as $textResult) {
      $textsInfo[$textResult["id"]] = $textResult;
      $sql = <<<SQL
        SELECT * FROM writer w
          JOIN text_writer ON writer_id = w.id
          WHERE text_id = :textId
SQL;
      $writerResults = $this->_db->fetch($sql, array(":textId" => $textResult["id"]));
      $textsInfo[$textResult["id"]]["writers"] = $writerResults;
    }
    return $textsInfo;
  }

	/**
	 * Saves text info to the database
	 * @param array $data the post data from the form
	 */
  public function save($data) {
  	if (!isset($data["filepath"])) {
  		$data["filepath"] = "";
	  }
  	//add a subText if required
		if (!empty($data["subTextId"])) {
			$this->_insertSubText($data);
		}
		//save the metadata
	  if (!empty($data["textTitle"])) { //ensure there are form data to be saved
			$sql = <<<SQL
				UPDATE text SET title = :title, date = :date, date_display = :date_display, date_publication = :date_publication, 
				                filepath = :filepath, level = :level, notes = :notes, reference = :reference
					WHERE id = :id
SQL;
			$this->_db->exec($sql, array(":id"=>$this->getId(), ":title"=>$data["textTitle"], ":date"=>$data["textDate"],
				":date_display"=>$data["textDisplayDate"], ":date_publication"=>$data["textPublicationDate"],
				":filepath"=>$data["filepath"], ":level"=>$data["textLevel"], ":notes"=>$data["textNotes"],
				":reference"=>$data["textReferenceTemplate"]));
			//save new writer ID
		  if ($data["writerId"]) {
			  $sql = <<<SQL
					INSERT INTO text_writer (text_id, writer_id) VALUES(:textId, :writerId)
SQL;
			  $this->_db->exec($sql, array(":textId" => $this->getId(), ":writerId" => $data["writerId"]));
	    }
	  }
  }

	/**
	 * Saves a new subtext record to the database
	 * @param array $data the form data for the new subtext record
	 */
	private function _insertSubText($data) {
		$partOf = "";
		//check if top level text or not
		if ($this->getId() == 0) {  //top level text
			$id = $data["subTextId"];
		} else {       //not a top level text
			$id = $this->getId() . "-" . $data["subTextId"];
			$partOf = $this->getId();
		}
		$sql = <<<SQL
			INSERT INTO text (id, title, partOf, filepath, date, date_display, date_publication, level)
				VALUES(:id, :title, :partOf, :filepath, :date, :dateDisplay, :datePublication, :level)
SQL;
		$this->_db->exec($sql, array(
			":id"=>$id, ":title"=>$data["subTextTitle"], ":partOf"=>$partOf, ":filepath"=>$data["filepath"],
				":date"=>$data["subTextDate"], ":dateDisplay"=>$data["subTextDisplayDate"],
				":datePublication"=>$data["subTextPublicationDate"], ":level"=>$data["subTextLevel"]));
	}

	public static function getTextIdFromFilepath($filepath, $db) {
		$sql = <<<SQL
			SELECT id AS text_id FROM text WHERE filepath = :filepath
SQL;
		$result = $db->fetch($sql, array(":filepath" => $filepath));
		return $result[0]["text_id"];
	}
}
