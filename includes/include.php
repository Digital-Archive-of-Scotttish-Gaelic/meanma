<?php

session_start();

//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

//TODO: consider relocating this SB
if (!$_SESSION["printSlips"]) {
  $_SESSION["printSlips"] = array();
}

//constants
define("INPUT_FILEPATH", "../gadelica/xml/");
define("SCANS_FILEPATH", "../../scans/");

//define("DB", "corpas");       //the production database
define("DB", "corpas_dev");       //the test database
define("DB_HOST", "130.209.99.241");
define("DB_USER", "corpas");
define("DB_PASSWORD", "XmlCraobh2020");

//autoload classes
spl_autoload_extensions(".php"); // comma-separated list
spl_autoload_register();

