<?php

namespace models;

class partofspeech
{
  private static $_labels = array(
    "n" => "common noun (unmarked)",
    "ns" => "plural-marked noun",
    "nx" => "case-marked noun",
    "N" => "proper noun (unmarked)",
    "Nx" => "case-marked proper noun",
    "v" => "dependent verb form",
    "vn" => "verbal noun",
    "V" => "independent verb form",
    "a" => "adjective",
    "ax" => "pre-adjectival particle",
    "ar" => "comparative adjective",
    "A" => "adverb",
    "d" => "determiner/article",
    "c" => "cardinal numeral",
    "o" => "ordinal numeral",
    "p" => "preposition",
    "P" => "prepositional pronoun",
    "px" => "prefix",
    "z" => "verb particle/conjunction",
    "D" => "pronoun",
    "Dx" => "post-nominal pronoun",
    "q" => "question word"
  );
  private $_label;

  public function __construct($abbr = null) {
    if ($abbr) {
      $this->setLabel($abbr);
    }
  }

  public function setLabel($abbr) {
    $this->_label = self::$_labels[$abbr];
  }

  public function getLabel() {
    return $this->_label;
  }

  public static function getAllLabels() {
    return self::$_labels;
  }
}