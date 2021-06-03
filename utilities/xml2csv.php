<?php
/* converts the corpus into a csv file for import to lemma database */

namespace models;

require_once 'metadata.php';

//load the metadata file into $data array
$titles = $data["titles"];
$districts = $data["districts"];
$dates = $data["dates"];


//iterate through the XML files and get the lemmas, etc
$path = '/var/www/html/dasg.arts.gla.ac.uk/www/gadelica/xml';
if (getcwd()=='/Users/stephenbarrett/Sites/meanma/utilities') {
	$path = '../../gadelica/xml';
}
else if (getcwd()=='/Users/mark/Sites/meanma/utilities') {
	$path = '../../gadelica/xml';
	//$path = '../../gadelica/xml/804_mss';
}
$it = new \RecursiveDirectoryIterator($path);
foreach (new \RecursiveIteratorIterator($it) as $nextFile) {
	if ($nextFile->getExtension()=='xml') {
		$xml = simplexml_load_file($nextFile);
		$xml->registerXPathNamespace('dasg','https://dasg.ac.uk/corpus/');
		foreach ($xml->xpath("//dasg:w[not(descendant::dasg:w)]") as $nextWord) {
			$lemma = (string)$nextWord['lemma'];
			if ($lemma) { echo $lemma . ','; }
			else { echo trim(strip_tags($nextWord->asXML())) . ','; }
			if (getcwd()=='/Users/stephenbarrett/Sites/meanma/utilities') {
				$filename = substr($nextFile,19);
			} else if (getcwd()=='/Users/mark/Sites/meanma/utilities') {
				$filename = substr($nextFile,19);
			} else {
				$filename = substr($nextFile,67);
			}
			echo $filename . ',';
			echo $nextWord['id'] . ',';
      echo trim(strip_tags($nextWord->asXML())) . ',';
			echo trim(strip_tags($nextWord->asXML())) . ',';
			echo $nextWord['pos'] . ',';
			if ($dates[$filename]) { echo $dates[$filename] . ','; }
			else { echo '9999,'; }
			if ($titles[$filename]) { echo '"' . $titles[$filename] . '",'; }
			else { echo '6666,'; }
			$nextWord->registerXPathNamespace('dasg','https://dasg.ac.uk/corpus/');
			$pageNum = $nextWord->xpath("preceding::dasg:pb[1]/@n");
			echo $pageNum[0] . ",";
			$medium = "other";
			if ($nextWord->xpath("ancestor::dasg:lg")) {
				$medium = "verse";
			}
			else if ($nextWord->xpath("ancestor::dasg:p")) {
				$medium = "prose";
			}
			echo $medium . ',';
			if ($districts[$filename]) { echo $districts[$filename] . ',';}
			else { echo '3333,'; }
			$ps = end($nextWord->xpath("preceding::dasg:w"));
			if (!$ps) { echo 'ZZ,'; }
			else { echo trim(strip_tags($ps->asXML())) . ','; }
			$fs = $nextWord->xpath("following::dasg:w[not(descendant::dasg:w)]")[0];
			if (!$fs) { echo 'ZZ,'; }
			else { echo trim(strip_tags($fs->asXML())) . ','; }
			if ($ps) {
				echo $ps['lemma'] . ',';
			}
			else {echo 'ZZ,';}
			if ($fs) {
				echo $fs['lemma'];
			}
			else {echo 'ZZ';}
			echo PHP_EOL;
		}
	}
}

class database
{

	private $_dbh, $_sth;

	/**
	 * Creates and initialises a new Database object
	 */
	public function __construct($dbName = DB)
	{
		try {
			$this->_dbh = new \PDO(
				"mysql:host=" . DB_HOST . ";dbname=" . $dbName . ";charset=utf8;", DB_USER, DB_PASSWORD, array(
				\PDO::MYSQL_ATTR_LOCAL_INFILE => true,
			));
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	public function getDatabaseHandle()
	{
		return $this->_dbh;
	}

	public function __destruct()
	{
		$this->_dbh = null;
		$this->_sth = null;
	}

	public function fetchRow($sql, array $values = array())
	{
		try {
			$this->_sth = $this->_dbh->prepare($sql);
			$this->_sth->execute($values);
			$result = $this->_sth->fetch();
			return $result;
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * A simple fetch function to run a prepared query
	 *
	 * @param string $sql : The SQL for the query
	 * @param array $values : The params for the query (defaults to empty)
	 * @return array $results  : The results array
	 */
	public function fetch($sql, array $values = array())
	{
		try {
			$this->_sth = $this->_dbh->prepare($sql);
			$this->_sth->execute($values);
			$results = $this->_sth->fetchAll();
			return $results;
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * A simple execute function to run a prepared query
	 *
	 * @param string $sql : The SQL for the query
	 * @param array $values : The params for the query (defaults to empty)
	 */
	public function exec($sql, array $values = array())
	{
		$results = array();
		try {
			$this->_sth = $this->_dbh->prepare($sql);
			$this->_sth->execute($values);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	public function getLastInsertId()
	{
		return $this->_dbh->lastInsertId();
	}
}
