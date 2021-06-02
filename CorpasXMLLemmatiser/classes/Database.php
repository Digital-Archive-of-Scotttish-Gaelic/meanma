<?php


class Database
{
  private static $databaseHandle;
  const ERROR_REPORTING = true;

  private static function connect($dbName)
  {
    try {
      self::$databaseHandle = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . $dbName . ";charset=utf8;", DB_USER, DB_PASSWORD
      );
    } catch (PDOException $e){
      echo $e->getMessage();
    }
  }

  public static function getDatabaseHandle($dbName = DB_NAME)
  {
    self::connect($dbName);

    if (self::ERROR_REPORTING)
      self::$databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    self::$databaseHandle->query("SET NAMES utf8");

    return self::$databaseHandle;
  }
}