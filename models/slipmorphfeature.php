<?php

namespace models;

class slipmorphfeature
{
  private $_abbr, $_type;
  private $_props = array();
  private $_propTitles = array(
  	  "noun"=>array("numgen", "case"),
	    "verb"=>array("mode", "status", "imp_number", "fin_number",
		    "imp_person", "fin_person", "tense", "mood"),
		  "preposition"=>array("prep_mode", "prep_person", "prep_number", "prep_gender"),
	    "adjective"=>array(),
	    "adverb"=>array(),
	    "other"=>array()
  );

  public function __construct($abbr) {
    $this->_abbr = $abbr;
    //set defaults based on abbreviation
    switch ($this->_abbr) {
      case "n":
        $this->_type = "noun";
        $this->_props["numgen"] = "singular (gender unclear)";
        $this->_props["case"] = "nominative";
        break;
      case "ns":
        $this->_type = "noun";
        $this->_props["numgen"] = "plural";
        $this->_props["case"] = "nominative";
        break;
      case "nx":
        $this->_type = "noun";
        $this->_props["numgen"] = "singular (gender unclear)";
        $this->_props["case"] = "genitive";
        break;
      case "v":
        $this->_type = "verb";
	      $this->_props["mode"] = "finite";
	      $this->_props["fin_person"] = "unmarked person";
	      $this->_props["fin_number"] = "unmarked number";
	      $this->_props["status"] = "dependent";
	      $this->_props["tense"] = "unclear tense";
	      $this->_props["mood"] = "active";
        break;
      case "vn":
        $this->_type = "verb";
	        $this->_props['mode'] = "verbal noun";
        break;
      case "V":
        $this->_type = "verb";
        $this->_props["mode"] = "finite";
        $this->_props["fin_person"] = "unmarked person";
        $this->_props["fin_number"] = "unmarked number";
        $this->_props["status"] = "independent";
        $this->_props["tense"] = "unclear tense";
        $this->_props["mood"] = "active";
        break;
	    case "p":
	    	$this->_type = "preposition";
	    	$this->_props["prep_mode"] = "basic";
	    	break;
	    case "P":
		    $this->_props["prep_mode"] = "conjugated";
		    $this->_props["prep_person"] = "third person";
		    $this->_props["prep_number"] = "singular";
		    $this->_props["prep_gender"] = "masculine";
		    break;
	    case 'a':
	    	$this->_type = "adjective";
	    	break;
	    case 'A':
	    	$this->_type = "adverb";
	    	break;
	    case 'x':
	    	$this->_type = "other"; //TODO check with MM on this and the above
	      break;
    }
  }

  public function getType() {
    return $this->_type;
  }

  public function setType($type) {
    $this->_type = $type;
  }

  public function getProps() {
    return $this->_props;
  }

  public function setProp($relation, $value) {
    $this->_props[$relation] = $value;
  }

  public function resetProps() {
    $this->_props = [];
  }

  public function populateClass($params) {
    $this->resetProps();
    if ($this->_propTitles[$this->_type]) {
	    foreach ($this->_propTitles[$this->_type] as $relation) {
		    if (!empty($params[$relation])) {
			    $this->setProp($relation, $params[$relation]);
		    }
	    }
    }
  }
}