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
        $('#passwordContainer').removeClass('hide');
        $('#password').focus();
        $('#login').removeClass('loginButton');
        $('#loginCancel').removeClass('loginButton');
      });
  });

  $('#loginCancel').on('click', function () {
    $('#email option:first').prop('selected',true);
    $('#emailSelectContainer').show();
    $('#passwordContainer').addClass('hide');
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
   * Pile category editing
   */
  $(document).on('click', '.pileBadge', function() {
    var thisBadge = $(this);
    //add a flag in case of deletion
    thisBadge.addClass('delete');
    var pileId = $(this).attr('data-pile');
    var pileDescription = $(this).attr('data-pile-description');
    var pileName = $(this).attr('data-pile-name');
    var slipId = $(this).attr('data-slip-id');
    $('#pileId').val(pileId);
    if (slipId) {
      $('#modalSlipId').val(slipId);
      $('#modalSlipIdDisplay').text(slipId);
      $('#modalSlipRemoveSection').show();
    }
    $('#modalPileName').val(pileName);
    $('#modalPileDescription').val(pileDescription)
  });

  $('#editPile').on('click', function () {
    var name = $('#modalPileName').val();
    var description = $('#modalPileDescription').val();
    var id = $('#pileId').val();
    var pileBadge = $('[data-pile='+id+']');
    pileBadge.attr('data-title', description);
    pileBadge.attr('data-description', description);
    pileBadge.attr('data-pile-name', name);
    pileBadge.text(name);
    var url = 'ajax.php?action=editPile&id=' + id;
    url += '&name=' + encodeURIComponent(name) + '&description=' + encodeURIComponent(description);
    //check if a slip association is to be removed
    if ($('#modalPileSlipRemove').prop('checked')) {
      $('.delete').remove();    //get rid of the badge
      url += '&slipId=' + $('#modalSlipId').val();
    }
    $('.delete').removeClass('delete');
    $('#pileModal').modal('hide');
    $.ajax({url: url}, function () {
    });
  });

  /**
   * Load and display slip data in a modal
   */
  $('#slipModal').on('show.bs.modal', function (event) {

    console.log('called');
    var modal = $(this);
    var slipLink = $(event.relatedTarget);
    //reset lock buttons
    $('.lockBtn').addClass('d-none');
    $('#lockedBtn').attr('title', 'Slip is locked - click to request unlock');
    $('#lockedBtn').removeClass('disabled');
    var locked = "";
    var owner = "";
    var slipId = slipLink.data('auto_id');
    var entryId = slipLink.data('entryid');
    var headword = slipLink.data('headword');
    var pos = slipLink.data('pos');
    var id = slipLink.data('id');
    var filename = slipLink.data('filename');
    var slipType = filename ? "corpus" : "paper";
    var textId = filename.split('_')[0];
    var uri = slipLink.data('uri');
    var date = slipLink.data('date_internal');
    var title = slipLink.data('title');
    var page = slipLink.data('page');
    var resultindex = slipLink.data('resultindex');
    var auto_id = slipLink.data('auto_id');
    var body = '';
    var header = headword;
    //write the hidden info needed for slip edit
    $('#slipFilename').val(filename);
    $('#slipId').val(id);
    $('#slipPOS').val(pos);
    $('#auto_id').val(auto_id);
    $('#entryId').val(entryId);
    $('#slipHeadword').html(headword);
    var canEdit;
    var isOwner;
    var starred;
    //get the slip info from the DB
    $.getJSON('ajax.php?action=loadSlip&filename='+filename+'&id='+id+'&index='+resultindex+'&auto_id='+auto_id
      +'&pos='+pos+'&entryId='+entryId, function (data) {
      if (data.wordClass) {
        var wc = data.wordClass;
        starred = data.starred;
        type = data.type;
        if (wc=='noun') {
          header += ' <em>n.</em>';
        }
        else if (wc=='verb') {
          header += ' <em>v.</em>';
        }
      }
      //check if user can edit slip
      canEdit = data.canEdit ? true : false;
      $.each(data.citation, function(cid, citation) {
        body += '<div>' + citation.context;
        body += ' <small><em>('+citation.type+')</em></small>';
        $.each(citation.translation, function (tid, translation) {
          if (translation.content != '') {
            body += '<p><small><a href="#" data-tid="trans_'+tid+'" class="toggleTranslation text-muted">show/hide translation</a></small></p>';
            body += '<div class="slipTranslation d-none" id="trans_'+tid+'">'+translation.content+' (<small><em>'+translation.type+')</em></small></div>';
          }
        });
        body += '</div>';
      });
      body += '<p class="text-muted">';
        //<span data-toggle="tooltip" data-html="true" title="' + '<em>' + title + '</em> p.';

      //temp work for page number placeholders - revisit
      let pageHtml = page ? 'p.'+page : '';
      let reference = data.referenceTemplate ? data.referenceTemplate.replace('%p', pageHtml) : '';
      body +=  date + ' ' + reference + '</p>';

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
        var slipNumHtml = '';
        if (slipType == 'paper') {  //paper slip
          slipNumHtml = '📝 ';
        }
        slipNumHtml += '§'+slipId;
        if (starred === 1) {
          slipNumHtml += ' ✅';  //checked slip
        }
        modal.find('#slipNo').text(slipNumHtml);
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

  //show/hide translations
  $(document).on('click', '.toggleTranslation', function () {
    let tid = '#' + $(this).attr('data-tid');
    if ($(tid).hasClass('d-none')) {
      $(tid).removeClass('d-none');
    } else {
      $(tid).addClass('d-none');
    }
  });

  /*
    Open the add new slip form in a new tab
  */
  $(document).on('click', '.createSlipLink', function() {
    var url = $(this).attr('data-url');
    var win = window.open(url, '_blank');
    if (win) {
      //Browser has allowed it to be opened
      win.focus();
    } else {
      //Browser has blocked it
      alert('Please allow popups for this website');
    }
  });

  $(document).on('click', '#editSlip', function () {
    $('#slipModal').modal('hide');
    var filename = $('#slipFilename').val();
    var id = $('#slipId').val();
    var headword = $('#slipHeadword').text();
    var pos = $('#slipPOS').val();
    var auto_id = $('#auto_id').val();
    var entryId = $('#entryId').val();
    var url = '?m=collection&a=edit&id=' + auto_id + '&filename=' + filename + '&headword=' + headword + '&entryId=' + entryId;
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
  });
});

function resetSlip() {
  $('#slipNumber').html('');
  $('#slipContext').attr('data-precontextscope', 80);
  $('#slipContext').attr('data-postcontextscope', 80);
  $('#slipStarred').prop('checked', false);
  $('#slipTranslation').html('');
  $('#slipNotes').html('');
}

function saveSlip() {
  var slipType = $('#citationContext').attr('data-sliptype');
  var entryId = $('#citationContext').attr('data-entryid');
  var senseId = $('#subsense_id').val();
  var textId = $('#textId').val();
  var reference = CKEDITOR.instances['reference'].getData();
  var wordform = $('#wordform').val();
  var wordclass = $('#wordClass').val();
  var starred = $('#slipStarred').prop('checked') ? 1 : 0;
  var locked = $('#locked').val();
  var notes = CKEDITOR.instances['slipNotes'].getData();
  var status = $('#status').val() ? $('#status').val() : 1; //set default status if not defined
  var data = {action: "saveSlip", filename: $('#slipFilename').text(), id: $('#wordId').text(),
    auto_id: $('#auto_id').text(), pos: $('#pos').val(), starred: starred, reference: reference,
    notes: notes, slipStatus: status, wordClass: wordclass, wordform: wordform, text_id: textId,
    locked: locked, text_id: $('#textId').val(), slipType: slipType, entryId: entryId, subsense_id: senseId};
  switch (wordclass) {
    case "noun":
      data['gender'] = $('#posGender').val();
      data['number'] = $('#posNumber').val();
      data['case'] = $('#posCase').val();
      break;
    case "noun phrase":
      data['number'] = $('#posNPNumber').val();
      data['gender'] = $('#posNPGender').val();
      data['case'] = $('#posNPCase').val();
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
    case "adjective":
      data["form"] = $('#posForm').val();
      if (data["form"] == "attributive") {
        data["noun_type"] = $('#posNounType').val();
        data["case"] = $('#posAdjCase').val();
      }
      break;
  }
  $.post("ajax.php", data, function (response) {
  })
    .done(function () {
      //refresh the parent page to show updated slip info once save is complete
      window.opener.document.location.reload(true);
      $('#slipSavedModal').modal();   //show the saved messsage
      setTimeout(function() {
        window.close();     //close the edit window
      }, 2000);
    });
}
