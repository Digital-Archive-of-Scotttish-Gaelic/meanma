<?php

namespace models;

class xmlfilehandler
{
  private $_filename, $_xml, $_collocateIds, $_preScope, $_postScope;

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

  public function getContext($id, $preScope = 12, $postScope = 12, $normalisePunc = true, $tagCollocates = false, $tagContext = false) {
  	$this->_preScope = $preScope;
  	$this->_postScope = $postScope;
    $context = array();
    $context["id"] = $id;
    $context["filename"] = $this->getFilename();
    $xpath = '/dasg:text/@ref';
    $out = $this->_xml->xpath($xpath);
    $context["uri"] = (string)$out[0];
    $xpath = "//dasg:w[@id='{$id}']/preceding::*[not(name()='s') and not(name()='p') and not(name()='note')]";
    $words = $this->_xml->xpath($xpath);
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
    $context["word"] = ($tagCollocates || $tagContext)
	    ? '<div style="display:inline; margin-left:4px;"><mark>' . (string)$word[0] . '</mark></div>'
      : (string)$word[0];
    $context["headwordId"] = $word[0]->attributes()["id"];
    $xpath = "//dasg:w[@id='{$id}']/following::*[not(name()='s') and not(name()='p') and not(name()='note')]";
    $words = $this->_xml->xpath($xpath);
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