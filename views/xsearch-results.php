
<style>
    .table tr {
        border: none; /* Remove all row borders */
        border-top: 1px solid #ddd; /* Add a top border to each row */
    }
</style>


<p><a href="index.php?m=corpus&a=xsearch&id=<?= $_GET["id"] ?>" title="Back to search">&lt; Back to search</a></p>

<div id="loadingMessage" class="text-center my-3">
    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...
</div>

<table id="searchResults" class="table-borderless" data-toggle="table" style="display: none;">
</table>

<div class="float-right"><small><a id="autoCreateRecords" href="#">Automatically create all records</a></small></div>
<ul id="pagination" class="pagination-sm"></ul>




<!-- Search results Javascript -->
<!--script>
    $(document).ready(function () {
        $.getJSON('ajax.php?action=xsearch&q=<?= $params['q'] ?>&mode=<?= $params['mode'] ?>&text= <?= $params['text'] ?>', function (rawData) {
            if (!rawData || !rawData.rows || rawData.rows.length === 0) {
                console.warn("No rows returned");
                $('#loadingMessage').html('<h3>There were no results for <em><?= $params['q'] ?></em></h3>');
                return;
            }

            //  EB version !!
            //const tids = [...new Set(rawData.rows.map(row => row.textid))]; // unique textids
            //  SB version !!!
            const tids = [...new Set(rawData.rows.map(row => row.tid))]; // unique textids

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
                        const text = textMap.get(String(row.tid)) || {};    //!! changed to `tid` from `textid` for SB vs EB search !!
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
                            { field: 'row', title: 'Row', formatter: (value, row, index) => `<strong>${index + 1}</strong>`, sortable: false },
                            { field: 'tid', title: "Reference", sortable: true, searchable: true },          //!! changed to `tid` from `textid` for SB vs EB search !!
                            { field: 'date_display', title: "Date", sortable: false, searchable: true },
                            { field: 'short_title', title: "Short Title", sortable: true, searchable: true },
                            { field: 'pre', title: 'Pre Context', align: 'right' },
                            { field: 'match', title: 'Match', align: 'center', sortable: true, searchable: true },
                            { field: 'post', title: 'Post Context' },
                            { field: 'slipHtml', title: 'Slip', escape: false, sortable: false },
                            { field: 'context', title: 'Context', formatter: formatContext, escape: false }
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
        let url = `index.php?m=collection&a=add&filename=${row.textid}.xml&wid=${row.id}&headword=${encodeURIComponent(row.lemma)}&pos=${row.pos}&wordform=${encodeURIComponent(row.wordform)}`;
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
                       ${modalCode}
                       data-url="${url}"
                       class="${slipStyle} ${slipClass}"
                       data-auto_id="${auto_id}"
                       data-headword="${row.lemma}"
                       data-wordform="${row.wordform}"
                       data-pos="${row.pos}"
                       data-id="${row.id}"
                       data-filename="${row.textid}.xml"
                       data-uri=""
                       data-date=""
                       data-page=""
                       data-resultindex="${index}">
                       ${action}
                    </a>
                `;
        return html;
    }

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

        const cacheKey = `slip-${row.id}`;
        if (window.slipCache && window.slipCache[cacheKey]) {
            return window.slipCache[cacheKey];
        }

        // Lazy-init the cache
        window.slipCache = window.slipCache || {};
        window.slipCache[cacheKey] = 'Loading...'; // temp placeholder

        const qs = `&action=getSlipLinkHtml&result&id=${row.id}&filename=${row.textid}.xml&pos=${row.pos}&lemma=${row.lemma}&wordform=${row.wordform}`;

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

</script-->

<script>
    $(document).ready(function () {

        // Build the xsearch URL safely (encode params)
        const q = "<?= addslashes($params['q']) ?>";
        const mode = "<?= addslashes($params['mode']) ?>";
        const text = "<?= addslashes($params['text']) ?>";

        const xsearchUrl =
            'ajax.php?action=xsearch' +
            '&q=' + encodeURIComponent(q) +
            '&mode=' + encodeURIComponent(mode) +
            '&text=' + encodeURIComponent(text);

        $.getJSON(xsearchUrl, function (rawData) {

            if (!rawData || !rawData.rows || rawData.rows.length === 0) {
                console.warn("No rows returned");
                $('#loadingMessage').html('<h3>There were no results for <em><?= htmlspecialchars($params['q'], ENT_QUOTES) ?></em></h3>');
                return;
            }

            // SB version: unique tids + unique word ids
            const tids = [...new Set(rawData.rows.map(row => row.tid))];
            const wids = [...new Set(rawData.rows.map(row => row.id))];

            $.ajax({
                url: 'ajax.php?action=getCombinedMetadata',
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify({tids, wids}),
                success: function ({textMeta, slipMeta}) {

                    const textMap = new Map((textMeta || []).map(meta => [String(meta.tid), meta]));
                    const slipMap = new Map((slipMeta || []).map(meta => [String(meta.id), meta]));

                    // Build enriched dataset (ensure filename exists for context lookup)
                    const enrichedData = rawData.rows.map((row, index) => {
                        const text = textMap.get(String(row.tid)) || {};
                        const slip = slipMap.get(String(row.id)) || {};

                        const filename = row.filename
                            ? row.filename
                            : (row.textid ? (row.textid + '.xml') : '');

                        return {
                            ...row,
                            ...text,

                            filename,                 // used by getResultContext
                            _contextLoaded: false,     // cache flag

                            // placeholders; will be replaced once context loads
                            pre: '…',
                            match: '…',
                            post: '…',

                            slipHtml: buildSlipHtml(slip, row, index)
                        };
                    });

                    // Sort by date (as you had)
                    enrichedData.sort((a, b) => {
                        const dateA = a.date || 0;
                        const dateB = b.date || 0;
                        return dateA - dateB;
                    });

                    // Render table
                    $('#searchResults').bootstrapTable('destroy').bootstrapTable({
                        idField: 'id',
                        uniqueId: 'id',
                        data: enrichedData,
                        pagination: true,
                        pageSize: 10,
                        search: true,
                        sidePagination: 'client',

                        columns: [
                            {
                                field: 'row',
                                title: 'Row',
                                formatter: (value, row, index) => `<strong>${index + 1}</strong>`,
                                sortable: false
                            },
                            {field: 'tid', title: "Reference", sortable: true, searchable: true},
                            {field: 'date_display', title: "Date", sortable: false, searchable: true},
                            {field: 'short_title', title: "Short Title", sortable: true, searchable: true},

                            {field: 'pre', title: 'Pre Context', align: 'right'},

                            // ensure HTML is not escaped for the link we inject
                            {
                                field: 'match',
                                title: 'Match',
                                align: 'center',
                                sortable: true,
                                searchable: true,
                                formatter: (v) => v
                            },

                            {field: 'post', title: 'Post Context'},
                            {field: 'slipHtml', title: 'Slip', escape: false, sortable: false}
                        ]
                    });

                    // Only load context for currently displayed rows (page/search/sort changes)
                    $('#searchResults')
                        .off('post-body.bs.table.lilctx')
                        .on('post-body.bs.table.lilctx', function () {
                            loadContextForVisibleRows();
                        });

                    $('#loadingMessage').hide();
                    $('#searchResults').show();

                    // kick off initial visible-page load
                    loadContextForVisibleRows();
                },
                error: function (xhr, status, error) {
                    console.error("Metadata fetch error:", error);
                }
            });
        });

        // Loads context ONLY for the rows on the current visible page
        function loadContextForVisibleRows() {
            const $table = $('#searchResults');

            const opts = $table.bootstrapTable('getOptions') || {};
            const pageSize = Number(opts.pageSize) || 10;
            const pageNumber = Number(opts.pageNumber) || 1;

            // getData() here is the *current* client-side dataset (filtered/sorted)
            const all = $table.bootstrapTable('getData') || [];

            const start = (pageNumber - 1) * pageSize;
            const end = start + pageSize;

            const visibleRows = all.slice(start, end); // <= this guarantees "10 at a time"

            visibleRows.forEach(row => {
                if (!row || row._contextLoaded || row._contextLoading) return;
                if (!row.filename || !row.id) return;

                row._contextLoading = true;

                const url =
                    'ajax.php?action=getResultContext' +
                    '&wid=' + encodeURIComponent(row.id) +
                    '&filename=' + encodeURIComponent(row.filename);

                $.getJSON(url, function (data) {
                    row.pre = (data && data.pre && data.pre.output) ? data.pre.output : '';
                    row.post = (data && data.post && data.post.output) ? data.post.output : '';

                    const word = (data && data.word) ? data.word : '';
                    const title = row.title ? String(row.title).replace(/"/g, '&quot;') : '';

                    row.match =
                        `<a target="_blank" ` +
                        `href="?m=corpus&a=browse&id=${encodeURIComponent(row.tid)}&wid=${encodeURIComponent(row.id)}" ` +
                        `data-toggle="tooltip" data-html="true" title="${title}">${word}</a>`;

                    row._contextLoaded = true;
                    row._contextLoading = false;

                    // Update by unique ID (works if your build supports it)
                    if (typeof $table.bootstrapTable === 'function' && opts.uniqueId) {
                        $table.bootstrapTable('updateByUniqueId', {id: row.id, row});
                    } else {
                        // fallback: find index in current data and updateRow
                        const idx = $table.bootstrapTable('getData').findIndex(r => r.id === row.id);
                        if (idx !== -1) $table.bootstrapTable('updateRow', {index: idx, row});
                    }
                }).fail(function () {
                    row._contextLoading = false;
                });
            });
        }

        function buildSlipHtml(slip, row, index) {
            let url = `index.php?m=collection&a=add&filename=${row.textid}.xml&wid=${row.id}&headword=${encodeURIComponent(row.lemma)}&pos=${row.pos}&wordform=${encodeURIComponent(row.wordform)}`;
            let modalCode = "";
            let action = "add";
            let auto_id = "";
            let slipStyle = "createSlipLink";
            let slipClass = "editSlipLink";

            if (slip && slip.auto_id) {
                action = "view";
                url = "";
                auto_id = slip.auto_id;
                slipClass = 'slipLink2';
                slipStyle = "editSlipLink";
                modalCode = 'data-toggle="modal" data-target="#slipModal"';
            }

            let html = `
                <a href="#"
                   ${modalCode}
                   data-url="${url}"
                   class="${slipStyle} ${slipClass}"
                   data-auto_id="${auto_id}"
                   data-headword="${row.lemma}"
                   data-wordform="${row.wordform}"
                   data-pos="${row.pos}"
                   data-id="${row.id}"
                   data-filename="${row.textid}.xml"
                   data-uri=""
                   data-date=""
                   data-page=""
                   data-resultindex="${index}">
                   ${action}
                </a>
            `;
            return html;
        }
    });
</script>




