<?php


namespace views;
use models;


class issue
{
	private $_issue;  //an (optional) instance of \models\issue

	public function __construct($issue = null) {
		if (isset($issue)) {
			$this->_issue = $issue;
			$this->_issue->load();
		}
	}

	public function show($action="browse") {
		switch ($action) {
			case "edit":
				$this->_writeEditForm();
				break;
			default:
				$this->_writeBrowseTable();
		}
	}

	private function _writeBrowseTable() {
		$issues = models\issues::getAllIssues();
		$html = <<<HTML
			<table class="table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Description</th>
						<th>Submitted by</th>
						<th>Status</th>
						<th>Last updated</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody
HTML;
		foreach ($issues as $issue) {
			$user = \models\users::getUser($issue->getUserEmail());
			$userHtml = <<<HTML
				<a href="mailto:{$user->getEmail()}">{$user->getFirstName()} {$user->getLastName()}</a>
HTML;
			$statusBadge = $issue->getStatus() == "new" ? "danger" : "success";
			$html .= <<<HTML
					<tr>
						<td>{$issue->getId()}</td>
						<td>{$issue->getDescription()}</td>
						<td>{$userHtml}</td>
						<td><span class="badge badge-{$statusBadge}">{$issue->getStatus()}</span></td>
						<td>{$issue->getUpdated()}</td>
						<td><small><a onclick="window.open('index.php?m=issues&a=edit&id={$issue->getId()}');" href="#" title="edit issue #{$issue->getId()}">edit</a></small></td>
					</tr>
HTML;
		}
		$html .= <<<HTML
				</tbody>
			</table>
HTML;
		echo $html;
	}

	private function _writeEditForm() {
		$issue = $this->_issue;
		$user = models\users::getUser($issue->getUserEmail());
		$resolvedSelected = $issue->getStatus() == "resolved" ? "selected" : "";
		$html = <<<HTML
			<form name="issue">
				<div class="form-group row">
					<label class="col-sm-2" for="id">ID</label>
					<input class="col-sm-1" id="id" name="id" id="id" type="text" disabled value="{$issue->getId()}">
				</div>
				<div class="form-group row">
					<label class="col-sm-2" for="description">Description</label>
					<textarea id="description" class="col-sm-6" rows="4" cols="40">{$issue->getDescription()}</textarea>
				</div>
				<div class="form-group row">
					<label class="col-sm-2" for="userName">Reported by</label>
					<label class="col-sm-4" id="userName"><a href="mailto:{$user->getEmail()}" title="email {$user->getEmail()}">{$user->getFirstName()} {$user->getLastName()}</a>
					<input type="hidden" name="userEmail" id="userEmail" value="{$user->getEmail()}">
				</div>
				<div class="form-group row">
					<label class="col-sm-2" for="status">Status</label>
					<select id="status" name="status" class="col-sm-2">
						<option value="new">new</option>
						<option value="resolved" {$resolvedSelected}>resolved</option>
					</select>
				</div>
				<div class="form-group row">
					<label class="col-sm-2" for="updated">Updated</label>
					<input class="col-sm-2" id="updated" name="updated" type="text" value="{$issue->getUpdated()}" disabled>
				</div>
				<button type="button" name="update" id="update" class="btn btn-primary">save</button>
				<button type="cancel" id="cancel" class="btn btn-secondary">close</button>
			</form>
HTML;
		echo $html;
		$this->_writeJavascript();
	}

	private function _writeJavascript() {
		echo <<<HTML
			<script>
				$(function() {
				  $('#cancel').on('click', function () {
				    window.close();
				  });
				  
				  //save the form data for an issue
				  $('#update').on('click', function() {
				    var params = {id: $('#id').val(), description: $('#description').val(), userEmail: $('#userEmail').val(),
				      status: $('#status').val(), updated: $('#updated').val()};
				    $.getJSON('ajax.php?action=updateIssue', params, function(response) {
				      console.log(response.message);
				    })
				    .done(function() {
				      window.opener.document.location.reload(true);
				      window.close();
				    });
				  });
				});	
			</script>
HTML;
	}
}
