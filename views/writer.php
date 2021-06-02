<?php

namespace views;
use models;

class writer
{

	private $_model;   // an instance of models\writer

	public function __construct($model) {
		$this->_model = $model;
	}

  public function show($type = "browse") {
		switch ($type) {
			case "edit":
				$this->_showEdit();
				break;
			default:
				$this->_showBrowse();
		}
	}

  private function _showBrowse() {
	  $user = models\users::getUser($_SESSION["user"]);
		$writer = $this->_model;
		$html = <<<HTML
			<ul class="nav nav-pills nav-justified" style="padding-bottom: 20px;">
				<li class="nav-item"><div class="nav-link active">viewing writer @{$writer->getId()}</div></li>
HTML;

    if ($user->getSuperuser()) {
			$html .= <<<HTML
        <li class="nav-item"><a class="nav-link" href="?m=writers&a=edit&id={$this->_model->getId()}">edit writer @{$writer->getId()}</a></li>
HTML;
		}
		$html .= <<<HTML
			</ul>
			<table class="table">
				<tbody>
					{$this->_getSurnameHtml($writer)}
					{$this->_getForenamesHtml($writer)}
					{$this->_getTitleHtml($writer)}
					{$this->_getNicknameHtml($writer)}
					{$this->_getLifeSpanHtml($writer)}
					{$this->_getOriginHtml($writer)}
					{$this->_getNotesHtml($writer)}
					{$this->_getTextsHtml($writer)}
				</tbody>
			</table>
HTML;
		echo $html;
	}

	private function _showEdit() {
		$user = models\users::getUser($_SESSION["user"]);
		if (!$user->getSuperuser()) {
      if (!$this->_model->getId()) {
				$model = new models\writers();
				$view = new writers($model);
				$view->show();
			} else {
				$this->_showBrowse();
			}
			return;
		}
		$writer = $this->_model;
		$preferredNameOptions = array("gd"=>"Gaelic", "en"=>"English", "nk"=>"Nickname");
		$preferredNameHtml = "";
		foreach ($preferredNameOptions as $abbr => $option) {
			$selected = ($abbr == $writer->getPreferredName()) ? "selected" : "";
			$preferredNameHtml .= <<<HTML
				\n<option value="{$abbr}" {$selected}>{$option}</option>
HTML;
		}
		$html = <<<HTML
		  <ul class="nav nav-pills nav-justified">
HTML;
    if (!$writer->getId()) {
			$html .= <<<HTML
			<li class="nav-item"><a class="nav-link" href="?m=writers&a=browse">view writers</a></li>
			<li class="nav-item"><div class="nav-link active">adding writer</div></li>
HTML;
		}
		else {
			$html .= <<<HTML
			<li class="nav-item"><a class="nav-link" href="?m=writers&a=browse&id={$this->_model->getId()}">view writer @{$this->_model->getId()}</a></li>
			<li class="nav-item"><div class="nav-link active">editing writer @{$this->_model->getId()}</div></li>
HTML;
		}
    $html .= <<<HTML
		  </ul>
			<hr/>
			<form action="index.php?m=writers&a=save&id={$writer->getId()}" method="post">
				<div class="form-group row">
					<label for="surname_gd" class="col-sm-2 col-form-label">Gaelic surname</label>
					<input type="text" class="form-control col-sm-4" name="surname_gd" id="surname_gd" value="{$writer->getSurnameGD()}">
				</div>
				<div class="form-group row">
					<label for="forenames_gd" class="col-sm-2 col-form-label">Gaelic forenames</label>
					<input type="text" class="form-control col-sm-4" name="forenames_gd" id="forenames_gd" value="{$writer->getForenamesGD()}">
				</div>
				<div class="form-group row">
					<label for="surname_en" class="col-sm-2 col-form-label">English surname</label>
					<input type="text" class="form-control col-sm-4" name="surname_en" id="surname_en" value="{$writer->getSurnameEN()}">
				</div>
				<div class="form-group row">
					<label for="forenames_gd" class="col-sm-2 col-form-label">English forenames</label>
					<input type="text" class="form-control col-sm-4" name="forenames_en" id="forenames_en" value="{$writer->getForenamesEN()}">
				</div>
				<div class="form-group row">
					<label for="preferred_name" class="col-sm-2 col-form-label">Preferred name</label>
					<select id="preferred_name" class="form-control col-sm-4" name="preferred_name">
						{$preferredNameHtml}
					</select>
				</div>
				<div class="form-group row">
					<label for="title" class="col-sm-2 col-form-label">Title</label>
					<input type="text" class="form-control col-sm-4" name="title" id="title" value="{$writer->getTitle()}">
				</div>
				<div class="form-group row">
					<label for="nickname" class="col-sm-2 col-form-label">Nickname</label>
					<input type="text" class="form-control col-sm-4" name="nickname" id="nickname" value="{$writer->getNickname()}">
				</div>
				<div class="form-group row">
					<label for="yob" class="col-sm-2 col-form-label">Year of birth</label>
					<input type="text" class="form-control col-sm-4" name="yob" id="yob" value="{$writer->getYearOfBirth()}">
				</div>
				<div class="form-group row">
					<label for="yod" class="col-sm-2 col-form-label">Year of death</label>
					<input type="text" class="form-control col-sm-4" name="yod" id="yod" value="{$writer->getYearOfDeath()}">
				</div>
				{$this->_getDistrictsHtml($writer)}
				<div class="form-group row">
					<label for="notes" class="col-sm-2 col-form-label">Notes</label>
					<textarea id="notes" name="notes" class="form-control col-sm-4" rows="10">{$writer->getNotes()}</textarea>
				</div>
				<input type="hidden" name="id" value="{$writer->getId()}">
				<button type="submit" class="btn btn-primary">save</button>
				<a href="?m=writers&a=browse&id={$_GET["id"]}"><button type="button" class="btn btn-secondary">cancel</button></a>
			</form>
			<p>&nbsp;</p>
HTML;
		echo $html;
	}

	private function _getSurnameHtml($writer) {
		$snEN = $writer->getSurnameEN();
		$snGD = $writer->getSurnameGD();
		if ($snEN != "" && $snGD != "") {
			$sn = "<strong>" . $snEN . "</strong> / <strong>" . $snGD . "</strong>";
		}
    else if ($snEN != "") {
			$sn = "<strong>" . $snEN . "</strong>";
		}
		else {
			$sn = "<strong>" . $snGD . "</strong>";
		}
		$html = <<<HTML
			<tr>
				<td>surname</td>
				<td>{$sn}</td>
			</tr>
HTML;
		return $html;
	}

	private function _getForenamesHtml($writer) {
		$fnEN = $writer->getForenamesEN();
		$fnGD = $writer->getForenamesGD();
		if ($fnEN != "" && $fnGD != "") {
			$fn = "<strong>" . $fnEN . "</strong> / <strong>" . $fnGD . "</strong>";
		}
		else if ($fnEN != "") {
			$fn = "<strong>" . $fnEN . "</strong>";
		}
		else {
			$fn = "<strong>" . $fnGD . "</strong>";
		}
		$html = <<<HTML
			<tr>
				<td>forenames</td>
				<td>{$fn}</td>
			</tr>
HTML;
		return $html;
	}

	private function _getTitleHtml($writer) {
		$html = "";
		if (empty($writer->getTitle())) {
			return $html;
		} else {
			$html = <<<HTML
				<tr>
					<td>title</td>
					<td>{$writer->getTitle()}</td>
				</tr>
HTML;
		}
		return $html;
	}

	private function _getNicknameHtml($writer) {
		$html = "";
		if (empty($writer->getNickname())) {
			return $html;
		} else {
			$html = <<<HTML
				<tr>
					<td>nickname</td>
					<td>{$writer->getNickname()}</td>
				</tr>
HTML;
		}
		return $html;
	}

	private function _getLifeSpanHtml($writer) {
		$html = "";
		if (empty($writer->getLifeSpan())) {
			return $html;
		} else {
			$html = <<<HTML
				<tr>
					<td>life</td>
					<td>{$writer->getLifeSpan()}</td>
				</tr>
HTML;
		}
		return $html;
	}

	private function _getDistrictsHtml($writer) {
		$districts = models\districts::getAllDistrictsInfo();
		$html = <<<HTML
			<div class="form-group row">
				<label for="district_1_id" class="col-sm-2 col-form-label">Origin</label>
				<select name="district_1_id" class="form-control col-sm-4">
					<option value="">-----</option>
HTML;
		foreach ($districts as $district) {
			$selected = "";
			if ($district["id"] == $writer->getOrigin()) {
				$selected = "selected";
			}
			$html .= <<<HTML
				<option value="{$district["id"]}" {$selected}>{$district["name"]}</option>
HTML;
		}
		$html .= <<<HTML
				</select>
			</div>
			<div class="form-group row">
				<label for="district_2_id" class="col-sm-2 col-form-label">Secondary origin</label>
				<select name="district_2_id" class="form-control col-sm-4">
					<option value="">-----</option>
HTML;
		foreach ($districts as $district) {
			$selected = "";
			if ($district["id"] == $writer->getOrigin2()) {
				$selected = "selected";
			}
			$html .= <<<HTML
				<option value="{$district["id"]}" {$selected}>{$district["name"]}</option>
HTML;
		}
		$html .= <<<HTML
				</select>
			</div>
HTML;
		return $html;
	}

	private function _getOriginHtml($writer) {
		$html = "";
		$origin = $writer->getOrigin();
		if (empty($origin)) {
			return $html;
		} else {
			$district = new models\district($origin);
			$html = <<<HTML
				<tr>
					<td>origin</td>
					<td><a href="?m=districts&a=browse&id={$origin}">{$district->getName()}</a>
HTML;
    $origin2 = $writer->getOrigin2();
    if (!empty($origin2)) {
    	$district2 = new models\district($origin2);
			$html .= ' / <a href="?m=district&a=browse&id=' . $origin2 . '">' . $district2->getName();
		}
    $html .= <<<HTML
					</td>
				</tr>
HTML;
		}
		return $html;
	}

	private function _getNotesHtml($writer) {
		$html = "";
		if (empty($writer->getNotes())) {
			return $html;
		} else {
			$html = <<<HTML
				<tr>
					<td>notes</td>
					<td>{$writer->getNotes()}</td>
				</tr>
HTML;
		}
		return $html;
	}

	private function _getTextsHtml($writer) {
		$html = "";
		if ($texts = $writer->getTexts()) {
			$html = "<tr><td>works</td><td><div class='list-group list-group-flush'>";
			foreach ($texts as $text) {
				$html .= <<<HTML
					<div class="list-group-item list-group-item-action">
						<a href="?m=corpus&a=browse&id={$text->getId()}">
							{$text->getTitle()}
						</a>
					</div>
HTML;
			}
			$html .= "</div></td></tr>";
		}
		return $html;
	}
}
