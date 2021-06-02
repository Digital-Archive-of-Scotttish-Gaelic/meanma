<?php

namespace models;

class functions
{
	public static function getWordclassAbbrev($wordclass) {
		$abbs = array("verb"=>"v.", "noun"=>"n.", "preposition"=>"prep.", "adjective"=>"adj.", "adverb"=>"adv.");
		return $abbs[$wordclass];
	}

  public static function addMutations($word) {
    $mutations = array('h-', 'n-', 't-');
    foreach ($mutations as $mutation) {
      if (mb_substr($word, 0, 2) == $mutation) {
        $word = str_replace($mutation, "", $word);
      }
    }
    $regexp = "[h|n||t-]?" . $word;
    return $regexp;
  }

  public static function canBeLenited($word) {
	  if (mb_strlen($word) < 2 || mb_substr($word, 1, 1) == '-') {
		  return false;
	  }

    $excludeChars = array('h', 'l', 'n', 'r', '?', '*', '~', '[', ']');
    if (in_array(substr($word, 0, 1), $excludeChars)) {
      return false;
    }
    return true;
  }

  public static function getLenited($word) {
    if (self::canBeLenited($word) == false) {
      return $word;
    }
	  if (substr($word, 1, 1) == 'h') { //already lenited
      $word = substr_replace($word, "?", 2, 0);
    } else {                                       //add lenition test
      $word = substr_replace($word, "h?", 1, 0);
    }
    return $word;
  }

  public static function getAccentInsensitive($text, $caseSens = true) {
      $regExp = "";
      $accentMappedChars = null;
      if ($caseSens) {
          $accentMappedChars = array(
              "aàá", "eèé", "iìí", "oòó", "uùú"
          );
      } else {
          $accentMappedChars = array(
              "aàáAÀÁ", "eèéEÈÉ", "iìíIÌÍ", "oòóOÒÓ", "uùúUÙÚ"
          );
      }

      foreach (functions::str_split_unicode($text) as $char) {
          $replaced = false;
          foreach ($accentMappedChars as $accentMap) {
              if (stristr($accentMap, $char)) {
                  $regExp .= "[{$accentMap}]+";
                  $replaced = true;
              }
          }
          if ($replaced == false)
              $regExp .= $char;
      }
      return $regExp;
  }

  public static function str_split_unicode($str, $l = 0) {
    if ($l > 0) {
      $ret = array();
      $len = mb_strlen($str, "UTF-8");
      for ($i = 0; $i < $len; $i += $l) {
        $ret[] = mb_substr($str, $i, $l, "UTF-8");
      }
      return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
  }

  public static function gdSort($s, $t) {
    $accentedvowels = array('à', 'è', 'ì', 'ò', 'ù', 'À', 'È', 'Ì', 'Ò', 'Ù', 'ê', 'ŷ', 'ŵ', 'â', 'á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú');
    $unaccentedvowels = array('a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'e', 'y', 'w', 'a', 'a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U');
    $str3 = str_replace($accentedvowels, $unaccentedvowels, $s);
    $str4 = str_replace($accentedvowels, $unaccentedvowels, $t);
    return strcasecmp($str3, $str4);
  }
}