<?php

namespace models;

class database {

  private $_dbh, $_sth;

  /**
   * Creates and initialises a new Database object
   */
  public function __construct($dbName = DB) {
    try {
      $this->_dbh = new \PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . $dbName . ";charset=utf8;", DB_USER, DB_PASSWORD, array(
        \PDO::MYSQL_ATTR_LOCAL_INFILE => true,
      ));
    } catch (PDOException $e){
      echo $e->getMessage();
    }
  }

  public function getDatabaseHandle() {
    return $this->_dbh;
  }

  public function __destruct() {
    $this->_dbh = null;
    $this->_sth = null;
  }

  /**
   * A simple fetch function to run a prepared query
   *
   * @param string $sql      : The SQL for the query
   * @param array $values    : The params for the query (defaults to empty)
   * @return array $results  : The results array
   */
  public function fetch($sql, array $values = array()) {
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
   * @param string $sql      : The SQL for the query
   * @param array $values    : The params for the query (defaults to empty)
   */
  public function exec($sql, array $values = array()) {
    try {
      $this->_sth = $this->_dbh->prepare($sql);
      $this->_sth->execute($values);
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }

  public function getLastInsertId() {
    return $this->_dbh->lastInsertId();
  }
}