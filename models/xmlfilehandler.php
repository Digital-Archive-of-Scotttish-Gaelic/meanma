<?php

namespace models;

class xmlfilehandler
{

  private $_filename; //the filepath of the XML document
	private $_xml;  //SimpleXMLElement: content of the XML document

	private $_collocateIds; //
	private $_preScope; //int : the number of tokens in the pre context
	private $_postScope;  //int: the number of tokens in the post context

  public function __construct($filename, $inputFilepath = INPUT_FILEPATH) {
  	if ($filename != $this->_filename) {  //check if the file has already been loaded
  		$this->_filename = $filename;
  		$this->_xml = simplexml_load_file($inputFilepath . $this->_filename, null, LIBXML_NOBLANKS);
        $this->_xml->registerXPathNamespace('dasg','https://dasg.ac.uk/corpus/');
	  }
  }

  public function getXml() {
      return $this->_xml;
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
	 * @param array $emendations : an (optional) array of emendation objects
	 * @param bool $tagContext : flag to set whether the output should be HTML markup with tokens clickable by user to trim context
	 * @param int $edit : flag for edit interfaces : 0 = none; 1 = emendations; 2 = deletions
	 * @param array $deletions : an (optional) array of deletion objects
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
	public function getContext($id, $preScope = 20, $postScope = 20, $emendations = null, $tagContext = false, $edit = 0, $deletions = null) {

		$this->_preScope = $preScope;
		$this->_postScope = $postScope;
		$context = array();
		$context["id"] = $id;   //now takes place of ["headwordId"] as well as ["id"]
		$context["filename"] = $this->getFilename();
		// echo "<br>" . $this->_filename . " : {$id}";    // handy for debugging XML issues SB
		// run xpath on p or lg or h or list element - possibly revert after MSS project
		$xpath = <<<XPATH
			//dasg:w[@wid='{$id}']/ancestor::*[name()='p' or name()='lg' or name()='h' or name()='list']
XPATH;
		$subXML = $this->_xml->xpath($xpath)[0];
		$subXMLString = $subXML->asXML();
		//following line to replace verse lines with slashes
		$subXMLString = str_replace("</l><l>", '<pc join="none">/</pc>', $subXMLString);
		$subXML = new \SimpleXMLElement($subXMLString);
		$xpath = <<<XPATH
			//w[@wid='{$id}']/preceding::*[(name()='w' and not(descendant::w)) or name()='pc' or name()='o']
XPATH;
		$words = $subXML->xpath($xpath);
		/* preContext processing */
		$context["pre"] = array("output" => "");
		if ($preScope) {
			$pre = array_slice($words, -$this->_preScope);
			//check if preScope value is less than the number of available tokens
			if (count($pre) < $preScope) {
				$this->_preScope = count($pre);   // ... if it is, set to number of available tokens
			}
			//check if we're one token away from the start of the document
			$nextIndex = $this->_preScope + 1;
			$limitCheck = array_slice($words, -$nextIndex);
			if (count($limitCheck) != count($pre) + 1) {
				$context["prelimit"] = count($pre);
			}
			//		$context["pre"] = $simple ? $pre
			//			: $this->_normalisePunctuation($pre, false, $tagContext, $section = "pre");
			$context["pre"] = $this->_normalisePunctuation($pre, $emendations, $tagContext, "pre", $edit, $deletions);
		}
		/* - end pre context processing - */
		$xpath = "//dasg:w[@wid='{$id}']";
		$word = $this->_xml->xpath($xpath);
		$wordString = functions::cleanForm($word[0]);   //strips tags
		if ($emendations) {
			$context["word"] = $this->_normalisePunctuation($word, $emendations, $tagContext, "word", $edit, $deletions);
		} else if ($tagContext) {
			$context["word"] = '<div style="display:inline; margin-left:4px;"><mark class="hi">' . $wordString . '</mark></div>';
		} else {
			$context["word"] = $wordString;
		}
		$xpath = <<<XPATH
			//w[@wid='{$id}']/following::*[(name()='w' and not(descendant::w)) or name()='pc' or name()='o']			
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
			$context["post"] = $this->_normalisePunctuation($post, $emendations, $tagContext, $section = "post", $edit, $deletions);
		}
		return $context;
	}


  /**
   * Parses an array of SimpleXML objects and formats the punctuation
   * @param array $chunk : array of SimpleXML objects (w, pc, or o)
   * @param $emendations : an (optional) array of emendation objects
   * @param bool $tagContext :  flag to set whether the output should be HTML markup with tokens clickable by user to trim context
   * @param string $section : either pre or post
   * @param int $edit : flag for edit interfaces : 0 = none; 1 = emendations; 2 = deletions
   * @param array $deletions : an (optional) array of deletion objects
   * @return associative array : an array containing output string and flags for start and end joins
   *   output => the context string, possible with HTML markup
   *   startJoin => one of possible values : left, right, both, none
   *   endJoin => one of possible values : left, right, both, none
   */
  private function _normalisePunctuation (array $chunk, $emendations, $tagContext, $section, $edit = 0, $deletions = null) {
  	$numTokens = count($chunk);
    $output = $startJoin = $endJoin = "";
    $rightJoin = true;  // should this token join to the next
	//	$this->_collocateIds = lemmas::getCollocateIds($this->getFilename());
		//used to track the position of each token in the pre/post context
		$position = $section == "pre" ? $this->_preScope : 1; // the position of this token in the context
	  $deleted = false; //flag to mark whether current token should be 'deleted'
    foreach ($chunk as $i => $element) {
      $followingToken = ($i < (count($chunk)-1)) ? $chunk[$i+1] : null;
      $followingJoin = $followingToken ? $followingToken->attributes()["join"] : "";
      $attributes = $element->attributes();
      if ($i == 0) {    //first element in the array
        $startJoin = (string)$attributes["join"];
      } else if ($i == (count($chunk) -1)) {    //last element in the array
        $endJoin = (string)$attributes["join"];
      }
      $spacer = ' ';    //default to using simple single space character.

	    //count DOWN for pre context and UP for post
	    switch ($section) {
		    case "pre":
			    $tokenNum = $numTokens - $i;
			    break;
		    case "word":
			    $tokenNum = 0;
			    break;
		    case "post":
			    $tokenNum = $i + 1;
	    }
	    $tokenId = $section . "_" . $tokenNum;
			$token = "";
	    /**
	     * Deletions
	     */
	    foreach ($deletions as $deletion) {
		    if ($tokenId == $deletion->getTokenIdStart()) {   //start of deletion
			    $deletionId = $deletion->getId();
			    $deleted = true;
			    $token = ($edit == 2)
				    ? '<div id="deletion_' . $deletionId . '" class="dropdown show d-inline emendation-action">
		            <a class="dropdown-toggle deletion collocateLink" href="#" id="dropdown_' . $deletionId . '"
		              data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . $spacer . '[...]' . $spacer . '</a>
			          <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown_' . $deletionId . '">
			            <li><a class="dropdown-item delete-deletion" data-id="' . $deletionId . '" tabindex="-1" href="#">delete</a></li>
			           </ul>
			         </div>'
				    : ' <span class="deletion">[...]</span> ';
		    }
	      if ($tokenId == $deletion->getTokenIdEnd()) {  //end of deletion
		      $lastDeletedToken = true;
	      }
	    }
			// --- end deletions
			if (!$deleted) {
				if ($edit == 1) {    //show the edit emendation dropdown
					$spacer = '<div style="margin-right:-2px;display:inline;">&thinsp;</div>';
					$token = $this->_getEmendationsDropdown($element, $tokenId, $emendations);
				} else if ($edit == 2) {  //show the edit deletion dropdown
					$spacer = '<div style="margin-right:-2px;display:inline;">&thinsp;</div>';
					$token = $this->_getDeletionsDropdown($element, $tokenId, $i);
				} else if ($emendations) {
					$preEmendHtml = $postEmendHtml = "";
					foreach ($emendations as $emendation) {
						if ($tokenId == $emendation->getTokenId()) {
							$emType = $emendation->getType();
							$emContent = $emendation->getContent();
							$displayType = ($emType == "other") ? "" : $emType . " ";
							$content = $emContent ? $displayType . $emContent : $emType;
							if ($emendation->getPosition() == "before") {
								$preEmendHtml .= '<span class="emendation">[' . $content . ']</span>';
							}
							if ($emendation->getPosition() == "after") {
								$postEmendHtml .= $spacer . '<span class="emendation">[' . $content . ']</span>';;
							}
						}
					}
					$token = $preEmendHtml;
					$token .= functions::cleanForm($element[0]); // ensure display of tags within the element (e.g. <abbr>)
					$token .= $postEmendHtml;
				} else if ($tagContext) {
					$startOrEnd = $section == "pre" ? "start" : "end";
					$token = '<a data-toggle="tooltip" data-html="true" class="contextLink ' . $section . '" data-position="' . $position . '"';
					$token .= ' title="' . $startOrEnd . ' context with <em><strong>' . $element[0] . '</strong></em>">' . $element[0] . '</a>';
				} else {
					$token = functions::cleanForm($element[0]); // ensure display of tags within the element (e.g. <abbr>)
				}
			}

			if ($deleted && $lastDeletedToken) {  //reset the deletion detect flags
				$lastDeletedToken = false;
				$deleted = false;
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

  private function _getDeletionsDropdown($token, $tokenId, $index) {
  	return <<<HTML
			<div id="{$tokenId}" class="dropdown show d-inline deletion-select">
		    <a class="dropdown-toggle collocateLink" href="#" id="dropdown_{$tokenId}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{$token[0]}</a>
			  <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown_{$tokenId}">
			      <li>
							<a class="dropdown-item new-deletion" data-index="{$index}" tabindex="-1" href="#">start ellipsis here</a>
							<a class="dropdown-item end-deletion disabled" data-index="{$index}" tabindex="-1" href="#">end ellipsis here</a>
						</li>
			  </ul>
			</div>
HTML;
	}

	/**
	 * @param $token:
	 * @param $tokenId
	 * @param $emendations : an array of emendation objects
	 * @return html : the HTML required for dropdown options for the given emendation
	 */
  private function _getEmendationsDropdown($token, $tokenId, $emendations) {
	  $preEmendHtml = $postEmendHtml = "";
	  foreach ($emendations as $emendation) {
		  if ($tokenId == $emendation->getTokenId()) {
		  	$emId = $emendation->getId();
		  	$emType = $emendation->getType();
		  	$emContent = $emendation->getContent();
		  	$displayType = ($emType == "other") ? "" : $emType . " xx";
		  	$content = $emContent ? $displayType . $emContent : $emType;     //perhaps do this in _getEmendationHtml ??
		  	if ($emendation->getPosition() == "before") {
		  		$preEmendHtml .= $this->_getEmendationHtml($emType, $content, $emId);
			  }
		  	if ($emendation->getPosition() == "after") {
		  		$postEmendHtml .= $this->_getEmendationHtml($emType, $emContent, $emId);
			  }
		  }
	  }
  	$options = array("sic", "sc.", ":", "pr.", "MS", "erron. for ...", "Reference", "other");
  	$optionHtml = "";
  	foreach ($options as $option) {
  		$optionHtml .= <<<HTML
				<li><a class="dropdown-item new-emendation" tabindex="-1" href="#">{$option}</a></li>
HTML;
	  }
	  return <<<HTML
			{$preEmendHtml}
			<div id="{$tokenId}" class="dropdown show d-inline emendation-select">
		    <a class="dropdown-toggle collocateLink" href="#" id="dropdown_{$tokenId}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{$token[0]}</a>
			  <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown_{$tokenId}">
			      <li class="dropdown-submenu">
							<a class="dropdown-item" tabindex="-1" href="#">insert before</a>
							<ul class="dropdown-menu" data-placement="before">
								{$optionHtml}
							</ul>
						</li>
						<li class="dropdown-submenu">
							<a class="dropdown-item" tabindex="-1" href="#">insert after</a>
							<ul class="dropdown-menu" data-placement="after">
								{$optionHtml}
							</ul>
						</li>
			  </ul>
			</div>
			{$postEmendHtml}
HTML;
  }

  private function _getEmendationHtml($type, $content, $emendationId) {
  	$displayType = ($type == "other") ? "" : "{$type} ";
  	$html = <<<HTML
			<div id="emendation_{$emendationId}" class="dropdown show d-inline emendation-action">
		    <a class="dropdown-toggle emendation collocateLink" href="#" id="dropdown_{$emendationId}" 
          data-type="{$type}" data-content="{$content}"
          data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> [{$displayType}{$content}] 
        </a>
	      <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown_{$emendationId}">
	        <li><a class="dropdown-item edit-emendation" data-id="{$emendationId}" tabindex="-1" href="#">edit</a></li>
	        <li><a class="dropdown-item delete-emendation" data-id="{$emendationId}"  tabindex="-1" href="#">delete</a></li>
	      </ul>
			</div>
			<div id="edit_emendation_{$emendationId}" class="hide">
				<input type="text" class="emendation_input" id="edit_{$emendationId}" value="{$content}">
			</div>
HTML;
  	return $html;
  }
}
