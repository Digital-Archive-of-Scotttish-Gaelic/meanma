<?php

require_once "includes/htmlHeader.php";
$filename = $_GET["tid"].'.xml';

writeEditModal();


$xmlHandler = new models\xmlfilehandler($filename, XML_FILEPATH);
$xml = $xmlHandler->getXml();


$xsl = new DOMDocument;
$xsl->load("xsl/corpus.xsl");
$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl);
$text = $proc->transformToXML($xml);

echo <<<HTML

    <h2>Lemma Editor</h2>
    
    {$text}
    
<script>
$(function () {
    
    $('.word').on('click', function () {
        
       //reset the form 
       $('#form')[0].reset();
       $('.new-row').remove();
       
       let id = $(this).attr('id');
       let lemma = $(this).attr('data-lemma');
       let pos = $(this).attr('data-pos');
       let formstring = $(this).attr('data-forms');
       //check if word has multiple forms
       if (formstring) {
           let forms = formstring.split("|");
           let lemmas = lemma.split('|');
           forms.forEach(function (form, i) {
              addFormRow(form, lemmas[i]); 
           });
       }
       $('#wordId').val(id);
       $('#lemma').val(lemma)
       $('#pos').val(pos);
       $('#emendationModal').modal('show');
    });
    
    $(document).on('click', '#saveEdit', function () {
        let wordId = $('#wordId').val();
        let textId = '{$_GET['tid']}';
        let lemma = $('#lemma').val();
        let pos = $('#pos').val();
        let split = $('#split').val();
        let data = {
            action: 'editLemma',
            id: wordId,
            textId: textId,
            lemma: lemma,
            pos: pos, 
            split: split,
            lemmas: [],
            forms: []
        }
        
        $('input[name="lemma[]"]').each(function() {
            data.lemmas.push($(this).val());
        });
        $('input[name="wordform[]"]').each(function() {
            data.forms.push($(this).val());
        });
        
        //update the text on screen
        if (split == 1) {                   //if split then use multiple lemmas
            lemma = data.lemmas.join('|');
        }
        let attrs = {
            'data-lemma': lemma,
            'data-pos': pos,
            'data-original-title': 'lemma: '+lemma+' pos: '+pos
        }
        $('#'+wordId).attr(attrs);
        
        //save the changes to the file
        $.getJSON('ajax.php', data, function (response) {
           console.log(response);
        });
    });
    
    //handle word split form
    $(document).on('click', '#splitBtn', function () {
        $('#addForm').removeClass('hide');
        $('#splitBtn').hide();
        $('#lemma').attr('disabled', true);
        $('#split').val('1');
        addFormRow();
    });
    
    $(document).on('click', '#addForm', function () {
        addFormRow();
    });
    
});
 
function addFormRow(wordform = '', lemma = '') {
    let html = '<label for="wordform" class="label">wordform:</label>';
    html += '<input type="text" name="wordform[]" class="form-control new-row" value="'+wordform+'" />';
    html += '<label for="lemma" class="label">lemma:</label>';
    html += '<input type="text" name="lemma[]" class="form-control new-row" value="'+lemma+'" />';
    
    $('#form').append(html);
}
</script>
HTML;


require_once "includes/htmlFooter.php";


function writeEditModal() {
    echo <<<HTML
        <div id="emendationModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="emendationModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body" style="font-size:1.2rem;">
                  <div id="emendationContext">
                    <form id="form">
                        <label for="lemma" class="label">Lemma:</label>
                        <input type="text" class="form-control" id="lemma" value="">
                        
                        <label for="pos" class="label">POS:</label>
                        <input type="text" class="form-control" id="pos" value="">
                        
                        <button type="button" id="splitBtn" class="btn btn-danger mt-3">split</button>
                      
                        <button type="button" id="addForm" class="btn btn-success mt-3 hide">add form</button>
                        <br>
                        
                        <input type="hidden" id="wordId" value="">
                        <input type="hidden" id="split" value="">
                    </form> 
                  </div>
                </div>
                <span data-precontextscope="" data-postcontextscope="" id="emendationContext" class="emendationContext">
                <div class="modal-footer">
                    <button type="button" id="saveEdit" class="btn btn-danger" data-dismiss="modal">save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
                </div>
           
            </div>
          </div>
        </div>
HTML;

}