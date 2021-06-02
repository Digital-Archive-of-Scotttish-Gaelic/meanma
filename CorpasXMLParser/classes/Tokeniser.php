<?php

class Tokeniser
{
  private $_elems = array(
    "/&(?!amp;)/u"                      => "&amp;",
    "/([.,;:?!’‘”“'\"\)\(\-–—\/])/u"    => "<pc>$1</pc>",
    "/\s<pc>(.)<\/pc>\s/u"              => " <pc join=\"no\">$1</pc> ",
    "/(\S)<pc>(.)<\/pc>(\S)/u"          => "$1 <pc join=\"both\">$2</pc> $3",
    "/\s<pc>(.)<\/pc>(\S)/u"            => " <pc join=\"right\">$1</pc> $2",
    "/<pc>(.)<\/pc>/u"                  => " <pc join=\"left\">$1</pc> ",
    "/(\[TD )(\d+)(\])/u"               => "<pb n=\"$2\"/>",
    "/\R\R/u"                           => "</p><p>",
    "/\R/u"                             => "<lb/>",
    "/<\/p><lb\/>/u"                    => "</p>",
    "/(<p>)(<pb n=\"\d+\"\/>)(<\/p>)/u" => "$2",
    "/<p>/u"                            => "\n<p>\n",
    "/<\/p>/u"                          => "\n</p>",
    "/<pb /u"                           => "\n<pb ",
    "/<lb\/>/u"                         => "\n<lb/>\n",
    "/(\S) <pc /u"                      => "$1\n<pc ",
    "/<\/pc> (\S)/u"                    => "</pc>\n$1",
    "/\s(\w+)\s/u"                      => " <w>$1</w> ",
    " /\s(\w+)\s/u"                      => " <w>$1</w> ",
    "/<w>/u"                            => "\n<w>",
    "/> <pc /u"                         => ">\n<pc ",
    "/(\S) </u"                         => "$1\n<",
    "/ </u"                             => "<",
    "/<w>(\w+)/u"                       => "<w pos=\"n\" lemma=\"$1\">$1",
    "/<([a-z]{3})>/u"                   => "<$1/>"
  );

  public function run($text)
  {
    $text = "<p>" . $text;
    foreach ($this->_elems as $pattern => $tag) {
      $text = preg_replace($pattern, $tag, $text);
    }
    return $text;
  }

  /*
   * Legacy code - retained for reference only
   */
  /*
  public function processMark($input)
  {
  // punctuation
    $input = preg_replace("/([.,;:?!’‘”“'\"\)\(-\/–])/u", "<pc>$1</pc>", $input);
    $input = preg_replace("/\s<pc>(.)<\/pc>\s/u", " <pc join=\"no\">$1</pc> ", $input);
    $input = preg_replace("/(\S)<pc>(.)<\/pc>(\S)/u", "$1 <pc join=\"both\">$2</pc> $3", $input);
    $input = preg_replace("/\s<pc>(.)<\/pc>(\S)/u", " <pc join=\"right\">$1</pc> $2", $input);
    $input = preg_replace("/<pc>(.)<\/pc>/u", " <pc join=\"left\">$1</pc> ", $input);

  // whitespace
    $input = "<p>" . $input;
    $input = preg_replace("/(\[TD )(\d+)(\])/u", "<pb n=\"$2\"/>", $input);
    $input = preg_replace("/\R\R/u", "</p><p>", $input);
    $input = preg_replace("/\R/u", "<lb/>", $input);
    $input = preg_replace("/<\/p><lb\/>/u", "</p>", $input);
    $input = preg_replace("/(<p>)(<pb n=\"\d+\"\/>)(<\/p>)/u", "$2", $input);
    $input = preg_replace("/<p>/u", "\n<p>\n", $input);
    $input = preg_replace("/<\/p>/u", "\n</p>", $input);
    $input = preg_replace("/<pb /u", "\n<pb ", $input);
    $input = preg_replace("/<lb\/>/u", "\n<lb/>\n", $input);
    $input = preg_replace("/(\S) <pc /u", "$1\n<pc ", $input);
    $input = preg_replace("/<\/pc> (\S)/u", "</pc>\n$1", $input);
    $input = preg_replace("/\s(\w+)\s/u", " <w>$1</w> ", $input);
    $input = preg_replace("/\s(\w+)\s/u", " <w>$1</w> ", $input);
    $input = preg_replace("/<w>/u", "\n<w>", $input);
    $input = preg_replace("/> <pc /u", ">\n<pc ", $input);
    $input = preg_replace("/(\S) </u", "$1\n<", $input);
    $input = preg_replace("/ </u", "<", $input);
    $input = preg_replace("/<w>(\w+)/u", "<w pos=\"n\" lemma=\"$1\">$1", $input);
    $input = preg_replace("/<([a-z]{3})>/u", "<$1/>", $input);

    $input .= "\n";

    return $input;
  } */

}