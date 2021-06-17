<?php

namespace models;

class xmlfilehandler
{

  private $_filename; //the filepath of the XML document
	private $_xml;  //SimpleXMLElement: content of the XML document

	private $_collocateIds; //
	private $_preScope; //int : the number of tokens in the pre context
	private $_postScope;  //int: the number of tokens in the post context

  public function __construct($filename) {
  	if ($filename != $this->_filename) {  //check if the file has already been loaded
  		$this->_filename = $filename;
  		$this->_xml = simplexml_load_file(INPUT_FILEPATH . $this->_filename);
		  $this->_xml->registerXPathNamespace('dasg','https://dasg.ac.uk/corpus/');
	  }
  }

  public function getFilename() {
    return $this->_filename;
  }

  public function getUri() {
    $xpath = '/dasg:text/@ref';
    $out = $this->_xml->xpath($xpath);
    return (string)$out[0];
  }

	/**
	 * @param $id : word ID
	 * @param int $preScope : the number of tokens for the pre context (default = 20 for results view)
	 * @param int $postScope : the number of tokens for the post context (default = 20 for results view)
	 * @param bool $normalisePunc : (deprecated?) flag to set whether to output with punctuation normalised
	 * @param false $tagCollocates : flag to set whether to output with HTML markup for handling collocates
	 * @param false $tagContext : flag to set whether the output should be HTML markup with tokens clickable by user to trim context
	 * @return associative array of strings:
	 *  id : wordId in XML doc
	 *  filename : path of XML document
	 *  headwordId (deprecate?)
	 *  [pre] context (array),
	 *    output : literal string of pre context
	 *    startJoin (deprecate?) : possible values : left, right, both, none
	 *    endJoin : (make boolean?)
	 *  [prelimit] : how many words to start of XML from start of pre context - used for +/- buttons in slip edit form
	 *  word : wordform
	 *  [post] context (array)
	 *    output : literal string of post context
	 *    startJoin
	 *    endJoin (deprecate?)
	 *    limit
	 *  [postlimit] : not sure what this does
	 */
	public function getContext($id, $preScope = 20, $postScope = 20, $normalisePunc = true, $tagCollocates = false, $tagContext = false) {
		$this->_preScope = $preScope;
		$this->_postScope = $postScope;
		$context = array();
		$context["id"] = $id;   //now takes place of ["headwordId"] as well as ["id"]
		$context["filename"] = $this->getFilename();
		// echo "<br>" . $this->_filename . " : {$id}";    // handy for debugging XML issues SB
		// run xpath on p or lg or h or list element - possibly revert after MSS project
		$xpath = <<<XPATH
			//dasg:w[@id='{$id}']/ancestor::*[name()='p' or name()='lg' or name()='h' or name()='list']
XPATH;
		$subXML = $this->_xml->xpath($xpath)[0];
		$subXML = new \SimpleXMLElement($subXML->asXML());
		$xpath = <<<XPATH
			//w[@id='{$id}']/preceding::*[(name()='w' and not(descendant::w)) or name()='pc' or name()='o']
XPATH;
		//for future: how do we add linebreaks for verse??
		$words = $subXML->xpath($xpath);
		/* preContext processing */
		$context["pre"] = array("output"=>"");
		if ($preScope) {
			$pre = array_slice($words, -$preScope);


			//check if we're one token away from the start of the document
			$nextIndex = $preScope + 1;
			$limitCheck = array_slice($words, -$nextIndex);
			if (count($limitCheck) != count($pre)+1) {
				$context["prelimit"] = count($pre);
			}


			if ($normalisePunc) {
				$context["pre"] = $this->_normalisePunctuation($pre, $tagCollocates, $tagContext, $section = "pre");
			} else {
				$context["pre"]["output"] = implode(' ', $pre);
			}
		}
		/* -- */
		$xpath = "//dasg:w[@id='{$id}']";
		$word = $this->_xml->xpath($xpath);
		$wordString = functions::cleanForm($word[0]);   //strips tags and whitespace
		$context["word"] = ($tagCollocates || $tagContext)
			? '<div style="display:inline; margin-left:4px;"><mark>' . $wordString . '</mark></div>'
			: $wordString;

		$xpath = "//w[@id='{$id}']/following::*[not(name()='s') and not(name()='p') and not(name()='note')]";
		$words = $subXML->xpath($xpath);
		/* postContext processing */
		$context["post"] = array("output"=>"");
/*		if ($postScope) {
			$post = array_slice($words,0, $postScope);
			//check if we're one token away from the end of the document
			$nextIndex = $postScope + 1;
			$limitCheck = array_slice($words, 0, $nextIndex);
			if (count($limitCheck) != count($post)+1) {
				$context["postlimit"] = count($post);
			}
			if ($normalisePunc) {
				$context["post"] = $this->_normalisePunctuation($post, $tagCollocates, $tagContext, $section = "post");
			} else {
				$context["post"]["output"] = implode(' ', $post);
			}
			//check if the scope has reached the end of the document
			if (count($post) < $postScope) {
				$context["post"]["limit"] = count($post);
			}
		}*/
		return $context;
	}

  public function getContext_old($id, $preScope = 20, $postScope = 20, $normalisePunc = true, $tagCollocates = false, $tagContext = false) {
  	$this->_preScope = $preScope;
  	$this->_postScope = $postScope;
    $context = array();
    $context["id"] = $id;   //now takes place of ["headwordId"] as well as ["id"]
    $context["filename"] = $this->getFilename();
	 // echo "<br>" . $this->_filename . " : {$id}";    // handy for debugging XML issues SB
	  // run xpath on p or lg or h or list element - possibly revert after MSS project
	  $xpath = <<<XPATH
			//dasg:w[@id='{$id}']/ancestor::*[name()='p' or name()='lg' or name()='h' or name()='list']
XPATH;
    $subXML = $this->_xml->xpath($xpath)[0];
    $subXML = new \SimpleXMLElement($subXML->asXML());
    $xpath = <<<XPATH
			//w[@id='{$id}']/preceding::*[(name()='w' and not(descendant::w)) or name()='pc' or name()='o']
XPATH;
    //for future: how do we add linebreaks for verse??
    $words = $subXML->xpath($xpath);
    /* preContext processing */
    $context["pre"] = array("output"=>"");
    if ($preScope) {
      $pre = array_slice($words, -$preScope);
      //check if we're one token away from the start of the document
      $nextIndex = $preScope + 1;
      $limitCheck = array_slice($words, -$nextIndex);
      if (count($limitCheck) != count($pre)+1) {
        $context["prelimit"] = count($pre);
      }
      if ($normalisePunc) {
        $context["pre"] = $this->_normalisePunctuation($pre, $tagCollocates, $tagContext, $section = "pre");
      } else {
        $context["pre"]["output"] = implode(' ', $pre);
	    }
    }
    /* -- */
    $xpath = "//dasg:w[@id='{$id}']";
    $word = $this->_xml->xpath($xpath);
	  $wordString = functions::cleanForm($word[0]);   //strips tags and whitespace
    $context["word"] = ($tagCollocates || $tagContext)
	    ? '<div style="display:inline; margin-left:4px;"><mark>' . $wordString . '</mark></div>'
      : $wordString;

	  $xpath = "//w[@id='{$id}']/following::*[not(name()='s') and not(name()='p') and not(name()='note')]";
	  $words = $subXML->xpath($xpath);
    /* postContext processing */
    $context["post"] = array("output"=>"");
    if ($postScope) {
      $post = array_slice($words,0, $postScope);
      //check if we're one token away from the end of the document
      $nextIndex = $postScope + 1;
      $limitCheck = array_slice($words, 0, $nextIndex);
      if (count($limitCheck) != count($post)+1) {
        $context["postlimit"] = count($post);
      }
      if ($normalisePunc) {
        $context["post"] = $this->_normalisePunctuation($post, $tagCollocates, $tagContext, $section = "post");
      } else {
        $context["post"]["output"] = implode(' ', $post);
      }
      //check if the scope has reached the end of the document
      if (count($post) < $postScope) {
        $context["post"]["limit"] = count($post);
      }
    }
    return $context;
  }

  /**
   * Parses an array of SimpleXML objects and formats the punctuation
   * @param array $chunk : array of SimpleXML objects
   * @return array : an array containing output string and flags for start and end joins
   */
  private function _normalisePunctuation (array $chunk, $tagCollocates, $tagContext, $section = null) {
    $output = $startJoin = $endJoin = "";
    $rightJoin = true;
		$this->_collocateIds = lemmas::getCollocateIds($this->getFilename());
		//used to track the position of each token in the pre/post context
		$position = $section == "pre" ? $this->_preScope : 1;
    foreach ($chunk as $i => $element) {
    	// !! $isWord is only used when we need to tag collocates
	    $isWord = false;
	    if ($tagCollocates) {
		    $isWord = ($wordId = $element->attributes()["id"]) ? true : false;
	    }
      $followingWord = ($i < (count($chunk)-1)) ? $chunk[$i+1] : null;
      $followingJoin = $followingWord ? $followingWord->attributes()["join"] : "";
      $attributes = $element->attributes();
      if ($i == 0) {
        $startJoin = (string)$attributes["join"];
      } else if ($i == (count($chunk) -1)) {
        $endJoin = (string)$attributes["join"];
      }

      $spacer = ' ';
			if ($tagCollocates) {
				$token = $isWord ? $this->_getCollocateDropdown($element, $wordId) : $element[0];
				$spacer = '<div style="margin-right:-4px;display:inline;">&thinsp;</div>';
			} else if ($tagContext) {
				$verb = $section == "pre" ? "start" : "end";
				$token = '<a data-toggle="tooltip" data-html="true" class="contextLink ' . $section . '" data-position="' . $position . '"';
				$token .= ' title="' . $verb . ' context with <em><strong>' . $element[0] . '</strong></em>">' . $element[0] . '</a>';
			} else {
				$token = $element[0];
			}
			//decrement/increment the position in the context
			$position = $section == "pre" ? $position - 1 : $position + 1;

      switch ($attributes["join"]) {
        case "left":
          $output .= $followingJoin == "right" || $followingJoin == "both" ? $token : $token . $spacer;
          $rightJoin = false;
          break;
        case "right":
          $output .= $spacer . $token;
          $rightJoin = true;
          break;
        case "both":
          $output .= $token;
          $rightJoin = true;
          break;
        default:
          $output .= $rightJoin ? $token : $spacer . $token;
          $rightJoin = false;
      }
    }
    return array("output" => $output, "startJoin" => $startJoin, "endJoin" => $endJoin);
  }

	/**
	 * @param $token:
	 * @param $wordId
	 * @return string : the HTML required for dropdown options for the given word (collocate)
	 */
  private function _getCollocateDropdown($word, $wordId) {
  	$existingCollocate = in_array($wordId, $this->_collocateIds) ? "existingCollocate" : "";
	  return <<<HTML
			<div class="dropdown show d-inline collocate" data-wordid="{$wordId}">
		    <a class="dropdown-toggle collocateLink {$existingCollocate}" href="#" id="dropdown_{$wordId}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{$word[0]}</a>
			  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown_{$wordId}">
			    <div class="dropdown-header">
			      <h5><span class="collocateHeadword"></span></h5>
					</div>
					<div class="dropdown-divider"></div>
				    <a id="subject_of_{$wordId}" class="dropdown-item collocateGrammar" href="#">subject of</a>
				    <a id="complement_of_{$wordId}" class="dropdown-item collocateGrammar" href="#">complement of</a>
				    <a id="modifier_of_{$wordId}" class="dropdown-item collocateGrammar" href="#">modifier of</a>
				    <a id="specifier_of_{$wordId}" class="dropdown-item collocateGrammar" href="#">specifier of</a>
				    <a id="has_subject_{$wordId}" class="dropdown-item collocateGrammar" href="#">has subject</a>
				    <a id="has_modifier_{$wordId}" class="dropdown-item collocateGrammar" href="#">has modifier</a>
				    <a id="has_specifier_{$wordId}" class="dropdown-item collocateGrammar" href="#">has specifier</a>
			  </div>
			</div>
HTML;
  }
}
