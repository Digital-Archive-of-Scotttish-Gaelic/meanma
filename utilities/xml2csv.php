<?php
/* converts the corpus into a csv file for import to lemma database */

namespace models;

define("DB", "corpas");
define("DB_HOST", "130.209.99.241");
define("DB_USER", "corpas");
define("DB_PASSWORD", "XmlCraobh2020");

$titles = array();
//$media = array();
$dates = array();
$districts = array();

//get the texts

$db = new database();
$sql = <<<SQL
		SELECT id, filepath, title, date, partOf FROM text
SQL;
$results = $db->fetch($sql);

//iterate through each text
foreach ($results as $result) {
	//query for district
	$sql = <<<SQL
		SELECT district_1_id as district FROM writer w
		JOIN text_writer tw ON w.id = tw.writer_id
		WHERE tw.text_id = '{$result["id"]}'
SQL;
	$districtResult = $db->fetchRow($sql);
	$district = $districtResult["district"];

	$filepath = $result["filepath"];
	$title = $result["title"];
	$date = $result["date"];
	$partOf = $result["partOf"];
	if (!$filepath && !$date && !$partOf) { //skip
		continue;
	}
	//populate the dates array
	if ($date && $filepath) {
		$dates[$filepath] = $date;
	} else if ($partOf) {
		$dates[$filepath] = getParentDate($partOf);
	}
	//populate the titles array
	if ($partOf) {
		$titles[$filepath] = getParentTitle($title, $partOf);
	} else {
		$titles[$filepath] = $title;
	}
	//populate the districts array
	if ($district && $filepath) {
		$districts[$filepath] = $district;
	} else if ($partOf) {
		$districts[$filepath] = getParentDistrict($partOf);
	}
}


/**
 * Recursive function to assemble a title string based on a text title's ancestor(s)
 * @param string $title the title of this subtext
 * @param int $parentId the ID of its parent text
 * @return string the formatted title
 */
function getParentTitle($title, $parentId) {
	$title = $title;
	global $db;
	$sql = <<<SQL
		SELECT partOf, title FROM text WHERE id = :id
SQL;
	$results = $db->fetch($sql, array(":id"=>$parentId));
	$result = $results[0];
	$parentTitle = $result["title"];
	$partOf = $result["partOf"];
	$title = $parentTitle . " – " . $title;
	if ($partOf) {
		$title = getParentTitle($title, $partOf);
	}
	return $title;
}

/**
 * Recursive function to get the date for a text from its ancestor(s)
 * @param int $parentId the parent ID
 * @return string the date
 */
function getParentDate($parentId)
{
	global $db;
	$sql = <<<SQL
		SELECT filepath, partOf, date FROM text WHERE id = :id
SQL;
	$results = $db->fetch($sql, array(":id" => $parentId));
	$result = $results[0];
	$filepath = $result["filepath"];
	$date = $result["date"];
	$partOf = $result["partOf"];

	if (!$partOf && !$filepath && !$date) {
		return "";
	}

	if ($date) {
		return $date;
	} else {
		if ($date = getParentDate($partOf)) {
			return $date;
		}
	}
}

/**
 * Recursive function to get the district for a writer from its text's ancestor(s)
 * @param int $parentId the parent text ID
 * @return string the date
 */
function getParentDistrict($parentId) {
	global $db;
	$sql = <<<SQL
		SELECT filepath, partOf, district_1_id as district FROM text t
			LEFT JOIN text_writer tw ON t.id = tw.text_id
			LEFT JOIN writer w ON w.id = tw.writer_id
			WHERE t.id = :id
SQL;
	$results = $db->fetch($sql, array(":id"=>$parentId));
	$result = $results[0];
	$filepath = $result["filepath"];
	$district = $result["district"];
	$partOf = $result["partOf"];

	if (!$partOf && !$filepath && !$district) {
		return "";
	}

	if ($district) {
		return $district;
	} else {
		if ($district = getParentDistrict($partOf)) {
			return $district;
		}
	}
}

//iterate through the XML files and get the lemmas, etc
$path = '/var/www/html/dasg.arts.gla.ac.uk/www/gadelica/corpas/xml';
if (getcwd()=='/Users/stephenbarrett/Sites/gadelica/corpas/code/mm_utilities') {
	$path = '../../xml';
}
else if (getcwd()=='/Users/mark/Sites/gadelica/corpas/code/mm_utilities') {
	$path = '../../xml';
}
$it = new \RecursiveDirectoryIterator($path);
foreach (new \RecursiveIteratorIterator($it) as $nextFile) {
	if ($nextFile->getExtension()=='xml') {
		$xml = simplexml_load_file($nextFile);
		$xml->registerXPathNamespace('dasg','https://dasg.ac.uk/corpus/');
		foreach ($xml->xpath("//dasg:w") as $nextWord) {

			$lemma = (string)$nextWord['lemma'];
			if ($lemma) { echo $lemma . ','; }
			else { echo $nextWord . ','; }
			if (getcwd()=='/Users/stephenbarrett/Sites/gadelica/corpas/code/mm_utilities') {
				$filename = substr($nextFile,10);
			} else if (getcwd()=='/Users/mark/Sites/gadelica/corpas/code/mm_utilities') {
				$filename = substr($nextFile,10);
			} else {
				$filename = substr($nextFile,58);
			}
			echo $filename . ',';
			echo $nextWord['id'] . ',';
			echo $nextWord . ',';
			echo $nextWord . ',';
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
			if ($districts[$filename]) { echo $districts[$filename];}
			else { echo '3333'; }
			$ps = end($nextWord->xpath("preceding-sibling::dasg:w"));
			if ($ps) {
				echo ',' . $ps;
			}
			else {echo ',ZZ';}
			$fs = $nextWord->xpath("following-sibling::dasg:w")[0];
			if ($fs) {
				echo ',' . $fs;
			}
			else {echo ',ZZ';}
			if ($ps) {
				echo ',' . $ps['lemma'];
			}
			else {echo ',ZZ';}
			if ($fs) {
				echo ',' . $fs['lemma'];
			}
			else {echo ',ZZ';}

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

	public function fetchRow($sql, array $values = array()) {
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
?>
