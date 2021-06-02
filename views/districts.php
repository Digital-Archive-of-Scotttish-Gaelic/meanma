<?php


namespace views;


class districts
{
	private $_model;   // an instance of models\districts

	public function __construct($model) {
		$this->_model = $model;
	}

	public function show() {
		$html = "";
		foreach ($this->_model->getAllDistrictsInfo() as $districtInfo) {
			$html .= <<<HTML
					<tr>
						<td><a href="?m=districts&a=browse&id={$districtInfo["id"]}">@{$districtInfo["id"]}</a></td>
						<td>{$districtInfo["name"]}</td>
						<td>{$districtInfo["notes"]}</td>
					</tr>
HTML;
		}
		echo <<<HTML
				<table class="table">
					<tbody>
						{$html}
					</tbody>
				</table>
HTML;
	}
}