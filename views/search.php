<?php


namespace views;

use models;

class search
{
	protected function writeSubHeading() {
		$user = models\users::getUser($_SESSION["user"]);
		if ($_GET["id"]) {    //if this is a subtext don't write the date range block
			$dateRangeBlock = $districtBlock = "";
		}
		echo <<<HTML
		<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">
HTML;
		if ($_GET["id"]=="0") {
			echo <<<HTML
			  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=browse&id=0">view corpus</a></li>
			  <li class="nav-item"><div class="nav-link active">searching corpus</div></li>
HTML;
			if ($user->getSuperuser()) {
				echo <<<HTML
				  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=edit&id=0">add text</a></li>
HTML;
			}
			echo <<<HTML
				<li class="nav-item"><a class="nav-link" href="?m=corpus&a=generate&id=0">corpus wordlist</a></li>
HTML;
		}
		else {
			echo <<<HTML
			  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=browse&id={$_GET["id"]}">view text #{$_GET["id"]}</a></li>
			  <li class="nav-item"><div class="nav-link active">searching text #{$_GET["id"]}</div></li>
HTML;
			if ($user->getSuperuser()) {
				echo <<<HTML
			      <li class="nav-item"><a class="nav-link" href="?m=corpus&a=edit&id={$_GET["id"]}">edit text #{$_GET["id"]}</a></li>
HTML;
			}
			echo <<<HTML
				  <li class="nav-item"><a class="nav-link" href="?m=corpus&a=generate&id={$_GET["id"]}">text #{$_GET["id"]} wordlist</a></li>
HTML;
		}
		echo <<<HTML
		  </ul>
			<hr/>
HTML;
	}
}