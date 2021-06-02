<?php


namespace views;

use models;

class district
{
	private $_model;   // an instance of models\district

	public function __construct($model) {
		$this->_model = $model;
	}

	public function show() {
		$html = <<<HTML
			<table class="table">
				<tbody>
					<tr>
						<td>name</td>
						<td>{$this->_model->getName()}</td>
					</tr>
					<tr>
						<td>notes</td>
						<td>{$this->_model->getNotes()}</td>
					</tr>
					<tr>
						<td>writers</td>
						<td>{$this->_getWritersHtml()}</td>
					</tr>
				</tbody>
			</table>
HTML;
		echo $html;
	}

	private function _getWritersHtml() {
		$writersInfo = models\districts::getWritersInfoForDistrict($this->_model->getID());
		if (empty($writersInfo)) {
			return "";
		}
		$html = '<ul class="list-group">';
		foreach ($writersInfo as $writerInfo) {
			$html .= <<<HTML
				<li class="list-group-item">
					<a href="?m=writers&a=browse&id={$writerInfo["id"]}">
						{$writerInfo["forenames_en"]} {$writerInfo["surname_en"]}
					</a>
				</li>
HTML;
		}
		$html .= "</ul>";
		return $html;
	}
}