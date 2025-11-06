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
		            <input type="hidden" name="text" value="_110"/>
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
            
            <div id="loadingMessage" class="text-center my-3">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...
            </div>
            <table id="searchResults" class="table-borderless" data-toggle="table" style="display: none;">
            </table>
				<div class="float-right"><small><a id="autoCreateRecords" href="#">Automatically create all records</a></small></div>
        <ul id="pagination" class="pagination-sm"></ul>

HTML;



        echo <<<HTML
            <script>
            $(document).ready(function () {
                $.getJSON('ajax.php?action=xsearch&q={$params['q']}&mode={$params['mode']}&texts={$params['text']}', function (rawData) {
                    if (!rawData || !rawData.rows || rawData.rows.length === 0) {
                        console.warn("No rows returned");
                        return;
                    }
            
                    const tids = [...new Set(rawData.rows.map(row => row.textid))]; // unique textids
                    const wids = [...new Set(rawData.rows.map(row => row.id))];     // unique word IDs
            
                    $.ajax({
                        url: 'ajax.php?action=getCombinedMetadata',
                        method: 'POST',
                        contentType: 'application/json',
                        dataType: 'json',
                        data: JSON.stringify({ tids, wids }),
                        success: function ({ textMeta, slipMeta }) {
                            const textMap = new Map(textMeta.map(meta => [String(meta.tid), meta]));
                            const slipMap = new Map(slipMeta.map(meta => [String(meta.id), meta]));
            
                            const enrichedData = rawData.rows.map((row, index) => {
                                const text = textMap.get(String(row.textid)) || {};
                                const slip = slipMap.get(String(row.id)) || {};
            
                                return {
                                    ...row,
                                    ...text,
                                    slipHtml: buildSlipHtml(slip, row, index)
                                };
                            });
            
                            enrichedData.sort((a, b) => {
                                const dateA = a.date || 0;
                                const dateB = b.date || 0;
                                return dateA - dateB;
                            });
            
                            $('#searchResults').bootstrapTable('destroy').bootstrapTable({
                                idField: 'id',
                                data: enrichedData,
                                pagination: true,
                                search: true,
                                sidePagination: 'client',
                                columns: [
                                    { field: 'row', title: 'Row', formatter: (value, row, index) => `<strong>\${index + 1}</strong>`, sortable: false },
                                    { field: 'textid', title: "Reference", sortable: true, searchable: true },
                                    { field: 'date_display', title: "Date", sortable: false, searchable: true },
                                    { field: 'title', title: "Title", sortable: true, searchable: true },
                                    { field: 'pre', title: 'Pre Context', align: 'right' },
                                    { field: 'match', title: 'Match', align: 'center', sortable: true, searchable: true },
                                    { field: 'post', title: 'Post Context' },
                                    { field: 'slipHtml', title: 'Slip', escape: false, sortable: false }
                                ]
                            });
            
                            $('#loadingMessage').hide();
                            $('#searchResults').show();
                        },
                        error: function (xhr, status, error) {
                            console.error("Metadata fetch error:", error);
                        }
                    });
                });
            });

            function buildSlipHtml(slip, row, index) {
                let url = `index.php?m=collection&a=add&filename=\${row.textid}.xml&wid=\${row.id}&headword=\${encodeURIComponent(row.lemma)}&pos=\${row.pos}&wordform=\${encodeURIComponent(row.wordform)}`;
                let modalCode = "";
                let action = "add";
                let auto_id = "";
                let slipStyle = "createSlipLink";
                let slipClass = "editSlipLink";
           console.log(slip); 
                if (slip.auto_id) {
                    action = "view";
                    url = "";
                    auto_id = slip.auto_id;
                    slipClass = 'slipLink2';
                    slipStyle = "editSlipLink";
                    modalCode = 'data-toggle="modal" data-target="#slipModal"'
                }
            
                let html = `
                    <a href="#"
                       \${modalCode}
                       data-url="\${url}"
                       class="\${slipStyle} \${slipClass}"
                       data-auto_id="\${auto_id}"
                       data-headword="\${row.lemma}"
                       data-wordform="\${row.wordform}"
                       data-pos="\${row.pos}"
                       data-id="\${row.id}"
                       data-filename="\${row.textid}.xml"
                       data-uri=""
                       data-date=""
                       data-page=""
                       data-resultindex="\${index}">
                       \${action}
                    </a>
                `;
                return html;
            }
                /*$('#searchResults').bootstrapTable({
                    url: 'ajax.php?action=xsearch&q={$params['q']}&mode={$params['mode']}',
                    pagination: true,
                    columns: [       
                        { field: 'row', title: 'Row', formatter: function(value, row, index) {return '<strong>' + (index + 1) + '</strong>'; }},
                        { field: 'textid', title: "Text ID" },
                        { field: 'date_display', title: "Date" },
                        { field: 'pre', title: 'Pre Context' }, 
                        { field: 'match', title: 'Match' },
                        { field: 'post', title: 'Post Context' },
                        { field: 'slip', title: 'Slip', formatter: addSlipLink }
                    ]
                });
                
                
                $('#searchResults').on('load-success.bs.table', function (e, data) {
                    const tids = (data.rows || []).map(row => row.textid);
                
                    $.ajax({
                        url: 'ajax.php?action=getTextMetadata',
                        method: 'POST',
                        contentType: 'application/json',
                        dataType: 'json', // force parsing
                        data: JSON.stringify({ tids: tids }),
                        success: function (extraData) {
                            // Index all original rows by textid for fast lookup
                            const rowMap = new Map((data.rows || []).map(row => [row.textid, row]));
                        
                            // Now build enrichedData in the order of extraData
                            const enrichedData = extraData.map(extra => {
                                const originalRow = rowMap.get(extra.tid) || {};
                                return { ...originalRow, ...extra };
                            });
                        
                            // Reload the table with rows now ordered by `date` from text table
                            $('#searchResults').bootstrapTable('load', {
                                total: enrichedData.length,
                                rows: enrichedData
                            });
                        },
                        error: function (xhr, status, error) {
                            console.error('AJAX error:', error);
                        }
                    });
                });
                
                 */
                
                /*
                // Boootstrap table code for search results
                 */
                function formatContext(value, row, index) {
                    if (row.contextHtml) {
                        // If the HTML is already loaded, return it immediately
                        return row.contextHtml;
                    } else {
                        $.getJSON('ajax.php?action=getResultContext&wid=' + row['id'] + '&filename=' + row['filename'] , function(data) {
                            let html = '<td style="border:none;text-align: right;">'+data["pre"]["output"]+'</td><td style="border:none;text-align: center;">';
                            html += '<a target="_blank" href="?m=corpus&a=browse&id='+row["tid"]+'&wid='+row["id"]+'" data-toggle="tooltip" data-html="true" title="'+row["title"]+'">';
                            html += data["word"] + '</a></td><td style="border:none;">'+data["post"]["output"]+'</td>';
                            
                            // Update the row data with the formatted HTML for the next render
                            row.contextHtml = html;
                            
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

                
                function addSlipLink(value, row, index) {
                    // Use a key that uniquely identifies the slip (e.g. row.id)
                    if (row.slipHtml) {
                        return row.slipHtml;
                    }
                
                    const cacheKey = `slip-\${row.id}`;
                    if (window.slipCache && window.slipCache[cacheKey]) {
                        return window.slipCache[cacheKey];
                    }
                
                    // Lazy-init the cache
                    window.slipCache = window.slipCache || {};
                    window.slipCache[cacheKey] = 'Loading...'; // temp placeholder
                
                    const qs = `&action=getSlipLinkHtml&result&id=\${row.id}&filename=\${row.textid}.xml&pos=\${row.pos}&lemma=\${row.lemma}&wordform=\${row.wordform}`;
                
                    $.ajax({
                        url: 'ajax.php?' + qs,
                        dataType: 'html'
                    }).done(function (html) {
                        window.slipCache[cacheKey] = html;
                        row.slipHtml = html;
                        $('#searchResults').bootstrapTable('updateRow', {
                            index: index,
                            row: row
                        });
                    }).fail(function () {
                        window.slipCache[cacheKey] = '[Error]';
                    });
                
                    return 'Loading...';
                }
                
                /*
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
                */
                
            </script>
HTML;

    }
}