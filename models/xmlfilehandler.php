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
  		$this->_xml = simplexml_load_file(INPUT_FILEPATH . $this->_filename, null, LIBXML_NOBLANKS);
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
	 * @param false $tagCollocates : flag to set whether to output with HTML markup for handling collocates
	 * @param false $tagContext : flag to set whether the output should be HTML markup with tokens clickable by user to trim context
	 * @return associative array of strings:
	 *  id : wordId in XML doc
	 *  filename : path of XML document
	 *  [pre] context (array),
	 *    output : literal string of pre context
	 *    startJoin (deprecate?) : possible values : left, right, both, none
	 *    endJoin : (make boolean?)
	 *  [prelimit] : int : if start of context is start of "document" will return the number of tokens in pre context
	 *        used for +/- buttons in slip edit form ALSO used for [reset context]
	 *  word : wordform
	 *  [post] context (array)
	 *    output : literal string of post context
	 *    startJoin
	 *    endJoin (deprecate?)
	 *  [postlimit] : int : if end of context is end of "document" will return the number of tokens in post context
	 *        used for +/- buttons in slip edit form ALSO used for [reset context]
	 */
	public function getContext($id, $preScope = 20, $postScope = 20, $tagCollocates = false, $tagContext = false) {
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
		$subXMLString = $subXML->asXML();
		//following line to replace verse lines with slashes
		$subXMLString = str_replace("</l><l>", '<pc join="none">/</pc>', $subXMLString);
		$subXML = new \SimpleXMLElement($subXMLString);
		$xpath = <<<XPATH
			//w[@id='{$id}']/preceding::*[(name()='w' and not(descendant::w)) or name()='pc' or name()='o']
XPATH;
		$words = $subXML->xpath($xpath);
		/* preContext processing */
		$context["pre"] = array("output"=>"");
		if ($preScope) {
			$pre = array_slice($words, -$this->_preScope);
			//check if preScope value is less than the number of available tokens
			if (count($pre) < $preScope) {
				$this->_preScope = count($pre);   // ... if it is, set to number of available tokens
			}
			//check if we're one token away from the start of the document
			$nextIndex = $this->_preScope + 1;
			$limitCheck = array_slice($words, -$nextIndex);
			if (count($limitCheck) != count($pre)+1) {
				$context["prelimit"] = count($pre);
			}
			$context["pre"] = $this->_normalisePunctuation($pre, $tagCollocates, $tagContext, $section = "pre");
		}
		/* - end pre context processing - */
		$xpath = "//dasg:w[@id='{$id}']";
		$word = $this->_xml->xpath($xpath);
		$wordString = functions::cleanForm($word[0]);   //strips tags
		$context["word"] = ($tagCollocates || $tagContext)
			? '<div style="display:inline; margin-left:4px;"><mark class="hi">' . $wordString . '</mark></div>'
			: $wordString;

		$xpath = <<<XPATH
			//w[@id='{$id}']/following::*[(name()='w' and not(descendant::w)) or name()='pc' or name()='o']			
XPATH;
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
			$context["post"] = $this->_normalisePunctuation($post, $tagCollocates, $tagContext, $section = "post");
		}
		return $context;
	}

  /**
   * Parses an array of SimpleXML objects and formats the punctuation
   * @param array $chunk : array of SimpleXML objects (w, pc, or o)
   * @param bool $tagCollocates : flag to set whether to output with HTML markup for handling collocates
   * @param bool $tagContext :  flag to set whether the output should be HTML markup with tokens clickable by user to trim context
   * @param string $section : either pre or post
   * @return associative array : an array containing output string and flags for start and end joins
   *   output => the context string, possible with HTML markup
   *   startJoin => one of possible values : left, right, both, none
   *   endJoin => one of possible values : left, right, both, none
   */
  private function _normalisePunctuation (array $chunk, $tagCollocates, $tagContext, $section) {
    $output = $startJoin = $endJoin = "";
    $rightJoin = true;  // should this token join to the next
		$this->_collocateIds = lemmas::getCollocateIds($this->getFilename());
		//used to track the position of each token in the pre/post context
		$position = $section == "pre" ? $this->_preScope : 1; // the position of this token in the context
    foreach ($chunk as $i => $element) {
    	// !! $isWord is only used when we need to tag collocates
	//    $isWord = false;
	//    if ($tagCollocates) {
	//	    $isWord = ($wordId = $element->attributes()["id"]) ? true : false;  //maybe change to name() of element
	//    }
      $followingToken = ($i < (count($chunk)-1)) ? $chunk[$i+1] : null;
      $followingJoin = $followingToken ? $followingToken->attributes()["join"] : "";
      $attributes = $element->attributes();
      if ($i == 0) {    //first element in the array
        $startJoin = (string)$attributes["join"];
      } else if ($i == (count($chunk) -1)) {    //last element in the array
        $endJoin = (string)$attributes["join"];
      }

      $spacer = ' ';    //default to using simple single space character.
			if ($tagCollocates) {
				//$token = $isWord ? $this->_getCollocateDropdown($element, $wordId) : $element[0];
				$token = $this->_getCollocateDropdown($element, $i);
				$spacer = '<div style="margin-right:-4px;display:inline;">&thinsp;</div>';
			} else if ($tagContext) {
				$startOrEnd = $section == "pre" ? "start" : "end";
				$token = '<a data-toggle="tooltip" data-html="true" class="contextLink ' . $section . '" data-position="' . $position . '"';
				$token .= ' title="' . $startOrEnd . ' context with <em><strong>' . $element[0] . '</strong></em>">' . $element[0] . '</a>';
			} else {
				$token = functions::cleanForm($element[0]); // ensure display of tags within the element (e.g. <abbr>)
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
  	$options = array("sic", "sc.", ":", "pr.", "MS", "erron. for ...", "Reference", "other 🚩");
  	$optionHtml = "";
  	foreach ($options as $option) {
  		$optionHtml .= <<<HTML
				<li><a class="dropdown-item" tabindex="-1" href="#">{$option}</a>
HTML;

	  }
	  return <<<HTML

			<style>
				.dropdown-submenu{
			    position: relative;
			}
			.dropdown-submenu a::after{
			    transform: rotate(-90deg);
			    position: absolute;
			    right: 3px;
			    top: 40%;
			}
			.dropdown-submenu:hover .dropdown-menu, .dropdown-submenu:focus .dropdown-menu{
			    display: flex;
			    flex-direction: column;
			    position: absolute !important;
			    margin-top: -30px;
			    left: 100%;
			}
			@media (max-width: 992px) {
			    .dropdown-menu{
			        width: 50%;
			    }
			    .dropdown-menu .dropdown-submenu{
			        width: auto;
			    }
			}
			</style>
			
			
			<div class="dropdown show d-inline collocate" data-wordid="{$wordId}">
		    <a class="dropdown-toggle collocateLink {$existingCollocate}" href="#" id="dropdown_{$wordId}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{$word[0]}</a>
			  <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown_{$wordId}">
			      <li class="dropdown-submenu">
							<a class="dropdown-item" tabindex="-1" href="#">insert before</a>
							<ul class="dropdown-menu">
								{$optionHtml}
							</ul>
						</li>
						<li class="dropdown-submenu">
							<a class="dropdown-item" tabindex="-1" href="#">insert after</a>
							<ul class="dropdown-menu">
								{$optionHtml}
							</ul>
						</li>
			  </li>
			</div>
HTML;
  }
}
