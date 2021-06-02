<?php

//constants

//test dirs
define('INPUT_FILEPATH', "./inputFiles/");
define('OUTPUT_FILEPATH', "./outputFiles/");

//define('INPUT_FILEPATH', "../../editableTXT/");
//define('OUTPUT_FILEPATH', "../../outXML/");

/* autoload classes anonymus function */
spl_autoload_register(function ($class) {
  include 'classes/' . $class . '.php';
});
