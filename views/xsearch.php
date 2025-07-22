<?php

namespace views;

use models;


class xsearch extends search
{
    public function showSearchForm() {
        parent::writeSubHeading();
        echo <<<HTML
            <div>
            <form action="?m=corpus&a=xsearch&id=0" method="get">
               
               <div class="form-group">
		          <div class="input-group">
		            <input type="text" id="q" name="q"/>
		            <div class="input-group-append">
		              <input type="hidden" name="m" value="corpus">
		              <input type="hidden" name="a" value="xsearch"/>
		            
		              <button name="submit" class="btn btn-primary" type="submit">search</button>
		            </div>
		          </div>
		        </div>
		        
		        <div class="form-group">
		          <div class="form-check form-check-inline">
		            <input class="form-check-input" type="radio" name="mode" id="headwordRadio" value="head-form">
		            <label class="form-check-label" for="headwordRadio">headword</label>
		          </div>
		          <div class="form-check form-check-inline">
		            <input class="form-check-input" type="radio" name="mode" id="wordformRadio" value="word-form" checked>
		            <label class="form-check-label" for="wordformRadio">wordform</label>
		          </div>
		        </div>
                
            </form>
            </div>
HTML;

    }
    public function showSearchResults($params) {

        models\collection::writeSlipDiv();

        echo <<<HTML
            <style>
              .table tr {
                border: none; /* Remove all row borders */
                border-top: 1px solid #ddd; /* Add a top border to each row */
              }
              
            </style>

            <p><a href="index.php?m=corpus&a=xsearch&id=0 title="Back to search">&lt; Back to search</a></p>
            
            <table id="searchResults" data-show-header="false" class="hide-headings table-borderless" data-toggle="table">
                  <thead>
                    <tr>
                      <th data-field="id">reference</th>
                      <th data-field="pre" data-align="right">pre</th>
                      <th data-field="match" data-align="center">match</th>
                      <th data-field="post">post</th>
                      <th data-field="slip">slip</th>
                    </tr>
                  </thead>
  
                <tbody>
            

                </tbody>
            </table>
				<div class="float-right"><small><a id="autoCreateRecords" href="#">Automatically create all records</a></small></div>
        <ul id="pagination" class="pagination-sm"></ul>

HTML;



        echo <<<HTML
            <script>
                $('#searchResults').bootstrapTable({
                    url: 'ajax.php?action=xsearch&q={$params['q']}&mode={$params['mode']}',
                    pagination: true,
                    columns: [            
                        { field:   'textid' }, 
                        { field: 'pre' },
                        { field: 'match' },
                        { field: 'post' },
                        { field: 'slip', title: 'Slip', formatter: addSlipLink }
                    ]
                });
                
                function addSlipLink(value, row, index) {          
                    let qs = '&action=getSlipLinkHtml&result&id=' + row['id'] +'&filename=' + row['textid'] + '.xml' + '&pos=' + row['pos'] + '&lemma=' + row['lemma'] + '&wordform=' + row['wordform'] ;
                    if (row.slipHtml) {
                        // If the HTML is already loaded, return it immediately
                        return row.slipHtml;
                    } else {
                        
                        var request = $.ajax({
                            url: 'ajax.php?' + qs, 
                            dataType: "html"}
                          );
                          request.done(function(html) {
                            row.slipHtml = html;
                            
                            // Refresh the specific row to show the new data
                            $('#searchResults').bootstrapTable('updateRow', {
                                index: index,
                                row: row
                            });       
                        });
                       
                        // Return a placeholder while the AJAX call is pending
                        return 'Loading...';        
                    }
                }
            </script>
HTML;

    }
}