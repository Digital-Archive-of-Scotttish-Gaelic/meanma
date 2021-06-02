<?php

namespace models;

class lemmas
{
	public static function getLemma($id, $filename) {
		$db = new database();
		$dbh = $db->getDatabaseHandle();
		try {
			$sql = <<<SQL
        SELECT lemma FROM lemmas 
            WHERE filename = :filename AND id =:id
SQL;
			$sth = $dbh->prepare($sql);
			$sth->execute(array(":filename"=>$filename, ":id"=>$id));
			$lemmaInfo = $sth->fetch();
			return $lemmaInfo;
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	public static function getGrammarInfo($id, $filename) {
		$db = new database();
		$dbh = $db->getDatabaseHandle();
		try {
			$sql = <<<SQL
        SELECT lemma, grammar FROM lemmas l
        	LEFT JOIN lemmaGrammar g ON l.id = g.id AND l.filename = g.filename
            WHERE l.filename = :filename AND l.id = :id
SQL;
			$sth = $dbh->prepare($sql);
			$sth->execute(array(":filename"=>$filename, ":id"=>$id));
			$grammarInfo = $sth->fetch();
			return $grammarInfo;
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	public static function getCollocateIds($filename) {
		$ids = array();
		$db = new database();
		$dbh = $db->getDatabaseHandle();
		try {
			$sql = <<<SQL
        SELECT id FROM lemmaGrammar
            WHERE filename = :filename
SQL;
			$sth = $dbh->prepare($sql);
			$sth->execute(array(":filename"=>$filename));
			while ($row = $sth->fetch()) {
				$ids[] = $row["id"];
			}
			return $ids;
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	public static function saveLemmaGrammar($id, $filename, $headwordId, $slipId, $grammar) {
		$db = new database();
		$dbh = $db->getDatabaseHandle();
		try {
			$sql = <<<SQL
        REPLACE INTO lemmaGrammar (id, filename, headwordId, slipId, grammar) 
            VALUES(:id, :filename, :headwordId, :slipId, :grammar)
SQL;
			$sth = $dbh->prepare($sql);
			echo $sth->execute(array(":filename"=>$filename, ":id"=>$id, ":headwordId"=>$headwordId,
				":slipId"=>$slipId, ":grammar"=>$grammar));
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}
}