<?php

namespace controllers;
use views;

class home
{

	public function run() {
		$view = new views\home();
		$view->show();
	}

}
