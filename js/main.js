$(function () {

  $('[data-toggle="tooltip"]').tooltip();
  //bind the tooltips to the body for AJAX content
  $('body').tooltip({
    selector: '[data-toggle=tooltip]'
  });

  /**
   * Login handlers
   */
  $('#email').change(function () {
    var email = $(this).children("option:selected").val();
    $.getJSON('ajax.php?action=getUsername&email='+email, function (data) {
      $('#selectedUser').text(data.firstname + ' ' + data.lastname);
    })
      .done(function () {
        $('#passwordLabel').show();
        $('#emailSelectContainer').hide();
        $('#passwordContainer').show();
        $('#login').removeClass('loginButton');
        $('#loginCancel').removeClass('loginButton');
      });
  });

  $('#loginCancel').on('click', function () {
    $('#email option:first').prop('selected',true);
    $('#emailSelectContainer').show();
    $('#passwordContainer').hide();
    $('#login').addClass('loginButton');
    $('#loginCancel').addClass('loginButton');
    $('.loginMessage').text('');
  });
  /** -- **/

  /**
   * Send email to request slip unlock
   */
  $('#lockedBtn').on('click', function () {
    var slipId = $(this).attr('data-slipid');
    $.ajax({url: 'ajax.php?action=requestUnlock&slipId='+slipId})
      .done(function () {
        alert('email sent');
      });
  });

  /**
   * Sense category editing
   */
  $(document).on('click', '.senseBadge', function() {
    var senseId = $(this).attr('data-sense');
    var senseDescription = $(this).attr('data-sense-description');
    var senseName = $(this).attr('data-sense-name');
    var slipId = $(this).attr('data-slip-id');
    $('#senseId').val(senseId);
    if (slipId) {
      $('#modalSlipId').val(slipId);
      $('#modalSlipIdDisplay').text(slipId);
      $('#modalSlipRemoveSection').show();
    }
    $('#modalSenseName').val(senseName);
    $('#modalSenseDescription').val(senseDescription)
  });

  $('#editSense').on('click', function () {
    var name = $('#modalSenseName').val();
    var description = $('#modalSenseDescription').val();
    var id = $('#senseId').val();
    var url = 'ajax.php?action=editSense&id=' + id;
    url += '&name=' + name + '&description=' + description;
    //check if a slip association is to be removed
    if ($('#modalSenseSlipRemove').prop('checked')) {
      url += '&slipId=' + $('#modalSlipId').val();
    }
    $('#senseModal').modal('hide');
    $.ajax({url: url}, function () {
    })
      .done(function(response) {
        location.reload();  //refresh the page
      });
  });

  /**
   * Load and display slip data in a modal
   */
  $('#slipModal').on('show.bs.modal', function (event) { // added by MM
    var modal = $(this);
    var slipLink = $(event.relatedTarget);
    //reset lock buttons
    $('.lockBtn').addClass('d-none');
    $('#lockedBtn').attr('title', 'Slip is locked - click to request unlock');
    $('#lockedBtn').removeClass('disabled');
    var locked = "";
    var owner = "";
    var slipId = slipLink.data('auto_id');
    var headword = slipLink.data('headword');
    var pos = slipLink.data('pos');
    var id = slipLink.data('id');
    var xml = slipLink.data('xml');
    //var filenameElems = xml.split('_');
    var textId = xml.split('_')[0];
    var uri = slipLink.data('uri');
    var date = slipLink.data('date');
    var title = slipLink.data('title');
    var page = slipLink.data('page');
    var resultindex = slipLink.data('resultindex');
    var auto_id = slipLink.data('auto_id');
    var body = '';
    var header = headword;
    //write the hidden info needed for slip edit
    $('#slipFilename').val(xml);
    $('#slipId').val(id);
    $('#slipPOS').val(pos);
    $('#auto_id').val(auto_id);
    $('#slipHeadword').html(headword);
    var canEdit;
    var isOwner;
    //get the slip info from the DB
    $.getJSON('ajax.php?action=loadSlip&filename='+xml+'&id='+id+'&index='+resultindex
      +'&preContextScope='+$('#slipContext').attr('data-precontextscope')+'&auto_id='+auto_id
      +'&postContextScope='+$('#slipContext').attr('data-postcontextscope') + '&pos=' + pos, function (data) {
      if (data.wordClass) {
        var wc = data.wordClass;
        if (wc=='noun') {
          header += ' <em>n.</em>';
        }
        else if (wc=='verb') {
          header += ' <em>v.</em>';
        }
      }
      //check if user can edit slip
      canEdit = data.canEdit ? true : false;
      var context = data.context.pre["output"] + ' <mark>' + data.context.word + '</mark> ' + data.context.post["output"];
      body += '<p>' + context + '</p>';
      if (data.translation) {
        body += '<div><small><a href="#translation" data-toggle="collapse" aria-expanded="false" aria-controls="translation">';
        body += 'show/hide translation</a></small></div>';
        body += '<div id="translation" class="collapse"><small class="text-muted">' + data.translation + '</small></div>';
      }
      //body += '<p class="small">[#' + textId + ': <em>' + title + '</em> p.' + page + ']</p>';
      body += '<p class="text-muted"><span data-toggle="tooltip" data-html="true" title="' + '<em>' + title + '</em> p.' + page + '">#' + textId + ': ' + date + '</span></p>';
      body += '<hr/>';
      body += '<ul class="list-inline">';
      $.each(data.senses, function (id, sense) {
        body += '<li class="list-inline-item badge badge-success senseBadge"';
        body += ' data-sense="' + id + '" data-title="' + sense.description + '"';
        body += '>' + sense.name + '</li>';
      });
      body += '</ul><ul class="list-inline">';
      $.each(data.slipMorph, function(k, v) {
        body += '<li class="list-inline-item badge badge-secondary">' + v + '</li>';
      });
      body += '</ul>';
      if (data.notes) {
        body += '<p><small class="text-muted">' + data.notes + '</small></p>';
      }
      slipId = data.auto_id;
      //check the slip lock status
      locked = data.locked;
      owner = data.owner;
      isOwner = data.isOwner;
    })
      .done(function () {
        modal.find('.modal-title').html(header);
        modal.find('#slipNo').text('§'+slipId);
        $('#auto_id').val(slipId);
        modal.find('.modal-body').html(body);
        if (canEdit) {
          $('#lockedBtn').attr('title', 'Slip is locked');
          $('#lockedBtn').addClass('disabled');
          $('.modal').find('button#editSlip').prop('disabled', false);
        } else {
          $('.modal').find('button#editSlip').prop('disabled', 'disabled');
        }
        //show the correct lock icon

        if (locked == 1) {
          $('.locked').removeClass('d-none');
          $('.locked').attr('data-owner', owner);
          $('.locked').attr('data-slipid', slipId);
          /*if (isOwner) {
            $('#lockedBtn').attr('title', 'Slip is locked');
            $('#lockedBtn').addClass('disabled');
          }*/
        } /* else {
          $('.unlocked').removeClass('d-none');
        }
        */
      });
  });

  $(document).on('click', '#editSlip', function () {
    $('#slipModal').modal('hide');
    var filename = $('#slipFilename').val();
    var id = $('#slipId').val();
    var headword = $('#slipHeadword').text();
    var pos = $('#slipPOS').val();
    var auto_id = $('#auto_id').val();
    var url = '?m=collection&a=edit&id=' + auto_id + '&filename=' + filename + '&&headword=' + headword;
    url += '&pos=' + pos + '&wid=' + id;
    var win = window.open(url, '_blank');
    if (win) {
      //Browser has allowed it to be opened
      win.focus();
    } else {
      //Browser has blocked it
      alert('Please allow popups for this website');
    }
  });

  $('.hideDictResults').on('click', function () {
    var formNum = $(this).attr('data-formNum');
    $('#show-' + formNum).show();
    $('#hide-' + formNum).hide();
    $('#form-' + formNum).hide();
  });

  $(document).on('click', '#closeSlipLink', function() {
    $('#slip').hide();
  });

  $('#wordformRadio').on('click', function () {
    $('#wordformOptions').show();
  });

  $('#headwordRadio').on('click', function () {
    $('#wordformOptions').hide();
  });

  $('.windowClose').on('click', function () {
    window.close();
  });

  $('#savedClose').on('click', function () {
    saveSlip();
    $('#slipSavedModal').modal();
    setTimeout(function() {
      window.close();
    }, 2000);
  });

  function resetSlip() {
    $('#slipNumber').html('');
    $('#slipContext').attr('data-precontextscope', 20);
    $('#slipContext').attr('data-postcontextscope', 20);
    $('#slipStarred').prop('checked', false);
    $('#slipTranslation').html('');
    $('#slipNotes').html('');
  }

  function saveSlip() {
    var wordclass = $('#wordClass').val();
    var starred = $('#slipStarred').prop('checked') ? 1 : 0;
    var locked = $('#locked').val();
    var translation = CKEDITOR.instances['slipTranslation'].getData();
    var notes = CKEDITOR.instances['slipNotes'].getData();
    var status = $('#status').val();
    var data = {action: "saveSlip", filename: $('#slipFilename').text(), id: $('#wordId').text(),
      auto_id: $('#auto_id').text(), pos: $('#pos').val(), starred: starred, translation: translation,
      notes: notes, status: status, preContextScope: $('#slipContext').attr('data-precontextscope'),
      postContextScope: $('#slipContext').attr('data-postcontextscope'), wordClass: wordclass,
      locked: locked};
    console.log(data);
    switch (wordclass) {
      case "noun":
        data['numgen'] = $('#posNumberGender').val();
        data['case'] = $('#posCase').val();
        break;
      case "verb":
        var mode = $('#posMode').val();
        data['mode'] = mode;
        if (mode == "imperative") {
          data['imp_person'] = $('#posImpPerson').val();
          data['imp_number'] = $('#posImpNumber').val();
        } else if (mode == "finite") {
          data['fin_person'] = $('#posFinPerson').val();
          data['fin_number'] = $('#posFinNumber').val();
          data['status'] = $('#posStatus').val();
          data['tense'] = $('#posTense').val();
          data['mood'] = $('#posMood').val();
        }
        break;
      case "preposition":
          data["prep_mode"] = $('#posPrepMode').val();
          if (data["prep_mode"] == 'conjugated' || data["prep_mode"] == 'possessive') {
            data["prep_person"] = $('#posPrepPerson').val();
            data["prep_number"] = $('#posPrepNumber').val();
            if (data["prep_person"] == 'third person' && data["prep_number"] == 'singular') {
              data["prep_gender"] = $('#posPrepGender').val();
            }
          }
        break;
    }
    $.post("ajax.php", data, function (response) {
      //refresh the parent page to show updated slip info
      window.opener.document.location.reload(true);
    });
  }
});
