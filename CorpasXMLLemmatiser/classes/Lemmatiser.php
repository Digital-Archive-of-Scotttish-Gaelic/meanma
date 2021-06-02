<?php


class Lemmatiser
{
	private $_lexicon = [];
	private $_iterator;

	public function __construct() {
		$this->_iterator = new RecursiveDirectoryIterator(INPUT_FILEPATH);
	}

	public function createLexicon() {
		$words = [];
		foreach (new RecursiveIteratorIterator($this->_iterator) as $nextFile) {
			if ($nextFile->getExtension()=='xml') {
				$xml = simplexml_load_file($nextFile);
				$xml->registerXPathNamespace('dasg','https://dasg.ac.uk/corpus/');
				$status = $xml->xpath("/dasg:text/@status")[0];
				if ($status == 'tagged') {
					foreach ($xml->xpath("//dasg:w") as $nextWord) {
						$form = $nextWord;
						$lemma = (string)$nextWord['lemma'];
						if ($lemma=='') { $lemma = $form; }
						if (strtolower($lemma[0]) == $lemma[0]) { $form = strtolower($form); }
						$pos = (string)$nextWord['pos'];
						$words[] = $form . '|' . $lemma . '|' . $pos;
					}
				}
			}
		}
		usort($words,'gdSort');
		$lexicon = [];
		foreach ($words as $nextWord) {
			if ($lexicon[$nextWord]) {
				$lexicon[$nextWord]++;
			}
			else {
				$lexicon[$nextWord] = 1;
			}
		}

		foreach ($lexicon as $nextWord => $nextCount) {
			$bits = explode('|',$nextWord);
			if ($this->_lexicon[$bits[0]]) {
				$bits2 = explode('|',$this->_lexicon[$bits[0]]);
				if ($nextCount > $bits2[2]) {
					$this->_lexicon[$bits[0]] = $bits[1] . '|' . $bits[2] . '|' . $nextCount;
				}
			}
			else {
				$this->_lexicon[$bits[0]] = $bits[1] . '|' . $bits[2] . '|' . $nextCount;
			}
		}
		return $this->_lexicon;
	}

	public function tagFiles() {
		foreach (new RecursiveIteratorIterator($this->_iterator) as $nextFile) {
			if ($nextFile->getExtension()=='xml') {
				$xml = simplexml_load_file($nextFile);
				$xml->registerXPathNamespace('dasg','https://dasg.ac.uk/corpus/');
				$status = $xml->xpath("/dasg:text/@status")[0];
				if ($status == 'raw') {
					foreach ($xml->xpath("//dasg:w") as $nextWord) {
						if ($this->_lexicon[(string)$nextWord]) {
							$bits = explode('|',$this->_lexicon[(string)$nextWord]);
							$nextWord['lemma'] = $bits[0];
							if ($bits[1]!='') {
								$nextWord['pos'] = $bits[1];
							}
							else {
								$nextWord['pos'] = False;
							}
						}
						else if ($this->_lexicon[strtolower((string)$nextWord)]) {
							$bits = explode('|',$this->_lexicon[strtolower((string)$nextWord)]);
							$nextWord['lemma'] = $bits[0];
							if ($bits[1]!='') {
								$nextWord['pos'] = $bits[1];
							}
							else {
								$nextWord['pos'] = False;
							}
						}
						else if (substr((string)$nextWord,1,1)==='h') {
							$delenited = substr((string)$nextWord,0,1) . substr((string)$nextWord,2);
							if ($this->_lexicon[$delenited]) {
								$bits = explode('|',$this->_lexicon[$delenited]);
								$nextWord['lemma'] = $bits[0];
								if ($bits[1]!='') {
									$nextWord['pos'] = $bits[1];
								}
								else {
									$nextWord['pos'] = False;
								}
							}
						}
					}
					$xml->asXML($nextFile);
				}
			}
		}
	}
}