<?php

namespace views;
use models;

class home
{

	public function show() {
		$issueHtml = models\users::checkSuperuserAuth()
			? '<a class="list-group-item list-group-item-action" href="?m=issues&a=browse">issues</a>'
			: '';
		$html = <<<HTML
			<div class="list-group list-group-flush">
				<a class="list-group-item list-group-item-action" href="?m=corpus&a=browse">corpus</a>
				<a class="list-group-item list-group-item-action" href="?m=writers&a=browse">writers</a>
				<a class="list-group-item list-group-item-action" href="?m=districts&a=browse">districts</a>
				<a class="list-group-item list-group-item-action" href="?m=collection&a=browse">collection</a>
				{$issueHtml}
				<!--
				<a class="list-group-item list-group-item-action" href="?m=entries&a=browse">browse entries</a>
				<a class="list-group-item list-group-item-action" href="?m=docs&action=view">technical documentation</a>
			-->
			</div>
HTML;
		echo $html;
	}

}
