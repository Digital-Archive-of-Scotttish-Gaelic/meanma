<?php
namespace controllers;

require_once "includes/include.php";
require_once "includes/securityBootstrap.php";

$controller = new slipprint();
$controller->run("writePDF");


