
<style>
    .table tr {
        border: none; /* Remove all row borders */
        border-top: 1px solid #ddd; /* Add a top border to each row */
    }
</style>


<p><a href="index.php?m=corpus&a=xsearch&id=<?= $_GET["id"] ?>" title="Back to search">&lt; Back to search</a></p>



<?php

if ($_GET["view"] != 'dictionary') {    //i.e. standard search view

    echo <<<HTML
        <div id="loadingMessage" class="text-center my-3">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...
        </div>
        
        <table id="searchResults" class="table-borderless" style="display: none;">
        </table>
        
        <div class="float-right"><small><a id="autoCreateRecords" href="#">Automatically create all records</a></small></div>
        <ul id="pagination" class="pagination-sm"></ul>
HTML;

} else {    // dictionary view

        $_GET["pp"] = null; // don't limit the results - fetch them all

        $model = new models\xsearch($_GET, true, $this->_db);

        $params = $_GET;
        $searchResults = $model->getResults($params, 'xforms');

        // No results
        if (empty($searchResults) || empty($searchResults['form'])) {
            echo '<h5>No results</h5>';
            $this->_writeViewSwitch();
            return;
        }

        $headForm = $searchResults['head-form'] ?? '';
        $totalResults = (int)($searchResults['count'] ?? 0);

        echo '<h4>' . htmlspecialchars($headForm) . '</h4>';
        echo '<h5>' . $totalResults . ' results</h5>';

        echo <<<HTML
    <table class="table">
        <tbody>
HTML;

        $formNum = 0;

        foreach ($searchResults['form'] as $nextForm) {

            $formNum++;

            $wordForm = $nextForm['word-form'] ?? '';
            $pos = $nextForm['pos'] ?? '';
            $count = (int)($nextForm['count'] ?? 0);

            /*
             * xforms has already grouped the individual results belonging
             * to this word-form/POS combination.
             */
            $locations = [];

            foreach ($nextForm['result'] ?? [] as $nextResult) {

                $textId = $nextResult['text-id'] ?? '';
                $wid = $nextResult['w']['wid'] ?? '';

                /*
                 * The old result contained:
                 *
                 * filename
                 * id
                 * date_of_lang
                 * auto_id
                 * title
                 * page
                 * tid
                 *
                 * xforms currently gives us text-id and wid instead.
                 *
                 * Keep both identifiers for the AJAX dictionary-results
                 * request.
                 */
                if ($textId !== '' && $wid !== '') {
                    $locations[] = $textId . ' ' . $wid;
                }
            }

            $locs = implode('|', $locations);

            /*
             * Escape values going into HTML attributes.
             */
            $htmlWordForm = htmlspecialchars(
                    $wordForm,
                    ENT_QUOTES,
                    'UTF-8'
            );

            $htmlPos = htmlspecialchars(
                    $pos,
                    ENT_QUOTES,
                    'UTF-8'
            );

            $htmlLocs = htmlspecialchars(
                    $locs,
                    ENT_QUOTES,
                    'UTF-8'
            );

            echo <<<HTML
        <tr>
            <td>{$htmlWordForm}</td>
            <td>{$htmlPos}</td>
            <td>
                <a href="#"
                   id="show-{$formNum}"
                   data-formNum="{$formNum}"
                   data-locs="{$htmlLocs}"
                   data-pos="{$htmlPos}"
                   data-lemma="{$htmlWordForm}"
                   data-action="show"
                   class="loadDictResults">
                    <span class="actionToggle">show</span> {$count} result(s)
                </a>

                <div id="results-{$formNum}">
                    <img
                        id="loadingImage-{$formNum}"
                        src="https://dasg.ac.uk/images/loading.gif"
                        width="400"
                        style="display: none;"
                        alt="Loading"
                    >
                    <table id="form-{$formNum}"></table>
                    <div id="pag-{$formNum}"></div>
                </div>
            </td>
        </tr>
HTML;
        }

        echo <<<HTML
        </tbody>
    </table>
HTML;

        models\collection::writeSlipDiv();
   //     $this->_writeViewSwitch();
   //     $this->_writeDictionaryResultsJavascript();




}

?>


<style>
    .paginationjs{line-height:1.6;font-family:Marmelad,"Lucida Grande",Arial,"Hiragino Sans GB",Georgia,sans-serif;font-size:14px;box-sizing:initial}.paginationjs:after{display:table;content:" ";clear:both}.paginationjs .paginationjs-pages{float:left}.paginationjs .paginationjs-pages ul{float:left;margin:0;padding:0}.paginationjs .paginationjs-go-button,.paginationjs .paginationjs-go-input,.paginationjs .paginationjs-nav{float:left;margin-left:10px;font-size:14px}.paginationjs .paginationjs-pages li{float:left;border:1px solid #aaa;border-right:none;list-style:none}.paginationjs .paginationjs-pages li>a{min-width:30px;height:28px;line-height:28px;display:block;background:#fff;font-size:14px;color:#333;text-decoration:none;text-align:center}.paginationjs .paginationjs-pages li>a:hover{background:#eee}.paginationjs .paginationjs-pages li.active{border:none}.paginationjs .paginationjs-pages li.active>a{height:30px;line-height:30px;background:#aaa;color:#fff}.paginationjs .paginationjs-pages li.disabled>a{opacity:.3}.paginationjs .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs .paginationjs-pages li:first-child,.paginationjs .paginationjs-pages li:first-child>a{border-radius:3px 0 0 3px}.paginationjs .paginationjs-pages li:last-child{border-right:1px solid #aaa;border-radius:0 3px 3px 0}.paginationjs .paginationjs-pages li:last-child>a{border-radius:0 3px 3px 0}.paginationjs .paginationjs-go-input>input[type=text]{width:30px;height:28px;background:#fff;border-radius:3px;border:1px solid #aaa;padding:0;font-size:14px;text-align:center;vertical-align:baseline;outline:0;box-shadow:none;box-sizing:initial}.paginationjs .paginationjs-go-button>input[type=button]{min-width:40px;height:30px;line-height:28px;background:#fff;border-radius:3px;border:1px solid #aaa;text-align:center;padding:0 8px;font-size:14px;vertical-align:baseline;outline:0;box-shadow:none;color:#333;cursor:pointer;vertical-align:middle\9}.paginationjs.paginationjs-theme-blue .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-blue .paginationjs-pages li{border-color:#289de9}.paginationjs .paginationjs-go-button>input[type=button]:hover{background-color:#f8f8f8}.paginationjs .paginationjs-nav{height:30px;line-height:30px}.paginationjs .paginationjs-go-button,.paginationjs .paginationjs-go-input{margin-left:5px\9}.paginationjs.paginationjs-small{font-size:12px}.paginationjs.paginationjs-small .paginationjs-pages li>a{min-width:26px;height:24px;line-height:24px;font-size:12px}.paginationjs.paginationjs-small .paginationjs-pages li.active>a{height:26px;line-height:26px}.paginationjs.paginationjs-small .paginationjs-go-input{font-size:12px}.paginationjs.paginationjs-small .paginationjs-go-input>input[type=text]{width:26px;height:24px;font-size:12px}.paginationjs.paginationjs-small .paginationjs-go-button{font-size:12px}.paginationjs.paginationjs-small .paginationjs-go-button>input[type=button]{min-width:30px;height:26px;line-height:24px;padding:0 6px;font-size:12px}.paginationjs.paginationjs-small .paginationjs-nav{height:26px;line-height:26px;font-size:12px}.paginationjs.paginationjs-big{font-size:16px}.paginationjs.paginationjs-big .paginationjs-pages li>a{min-width:36px;height:34px;line-height:34px;font-size:16px}.paginationjs.paginationjs-big .paginationjs-pages li.active>a{height:36px;line-height:36px}.paginationjs.paginationjs-big .paginationjs-go-input{font-size:16px}.paginationjs.paginationjs-big .paginationjs-go-input>input[type=text]{width:36px;height:34px;font-size:16px}.paginationjs.paginationjs-big .paginationjs-go-button{font-size:16px}.paginationjs.paginationjs-big .paginationjs-go-button>input[type=button]{min-width:50px;height:36px;line-height:34px;padding:0 12px;font-size:16px}.paginationjs.paginationjs-big .paginationjs-nav{height:36px;line-height:36px;font-size:16px}.paginationjs.paginationjs-theme-blue .paginationjs-pages li>a{color:#289de9}.paginationjs.paginationjs-theme-blue .paginationjs-pages li>a:hover{background:#e9f4fc}.paginationjs.paginationjs-theme-blue .paginationjs-pages li.active>a{background:#289de9;color:#fff}.paginationjs.paginationjs-theme-blue .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-blue .paginationjs-go-button>input[type=button]{background:#289de9;border-color:#289de9;color:#fff}.paginationjs.paginationjs-theme-green .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-green .paginationjs-pages li{border-color:#449d44}.paginationjs.paginationjs-theme-blue .paginationjs-go-button>input[type=button]:hover{background-color:#3ca5ea}.paginationjs.paginationjs-theme-green .paginationjs-pages li>a{color:#449d44}.paginationjs.paginationjs-theme-green .paginationjs-pages li>a:hover{background:#ebf4eb}.paginationjs.paginationjs-theme-green .paginationjs-pages li.active>a{background:#449d44;color:#fff}.paginationjs.paginationjs-theme-green .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-green .paginationjs-go-button>input[type=button]{background:#449d44;border-color:#449d44;color:#fff}.paginationjs.paginationjs-theme-yellow .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-yellow .paginationjs-pages li{border-color:#ec971f}.paginationjs.paginationjs-theme-green .paginationjs-go-button>input[type=button]:hover{background-color:#55a555}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li>a{color:#ec971f}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li>a:hover{background:#fdf5e9}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li.active>a{background:#ec971f;color:#fff}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-yellow .paginationjs-go-button>input[type=button]{background:#ec971f;border-color:#ec971f;color:#fff}.paginationjs.paginationjs-theme-red .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-red .paginationjs-pages li{border-color:#c9302c}.paginationjs.paginationjs-theme-yellow .paginationjs-go-button>input[type=button]:hover{background-color:#eea135}.paginationjs.paginationjs-theme-red .paginationjs-pages li>a{color:#c9302c}.paginationjs.paginationjs-theme-red .paginationjs-pages li>a:hover{background:#faeaea}.paginationjs.paginationjs-theme-red .paginationjs-pages li.active>a{background:#c9302c;color:#fff}.paginationjs.paginationjs-theme-red .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-red .paginationjs-go-button>input[type=button]{background:#c9302c;border-color:#c9302c;color:#fff}.paginationjs.paginationjs-theme-red .paginationjs-go-button>input[type=button]:hover{background-color:#ce4541}.paginationjs .paginationjs-pages li.paginationjs-next{border-right:1px solid #aaa\9}.paginationjs .paginationjs-go-input>input[type=text]{line-height:28px\9;vertical-align:middle\9}.paginationjs.paginationjs-big .paginationjs-pages li>a{line-height:36px\9}.paginationjs.paginationjs-big .paginationjs-go-input>input[type=text]{height:36px\9;line-height:36px\9}
</style>

<script src="../js/pagination.min.js"></script>

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

                    // Sort by date
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
            let url = `index.php?m=collection&a=add&filename=${row.filename}&wid=${row.id}&headword=${encodeURIComponent(row.lemma)}&pos=${row.pos}&wordform=${encodeURIComponent(row.wordform)}`;
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
                   data-filename="${row.filename}"
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

    function template(data, params) {
        const headword = params.headword;
        const pos = params.pos;

        let html = '<tbody>';

        $.each(data, function (key, val) {

            const tid = val.tid ?? val['text-id'] ?? '';
            const dateDisplay = val.date_display ?? val.date ?? '';
            const shortTitle = val.short_title ?? val.title ?? '';

            const pre = val.pre?.output ?? val.pre ?? '';
            const match = val.word ?? val.match ?? '';
            const post = val.post?.output ?? val.post ?? '';

            const filename = val.filename ?? (tid ? `${tid}.xml` : '');

            let title = 'Headword: ' + headword + '<br>';
            title += 'POS: ' + pos + '<br>';
            title += 'Date: ' + dateDisplay + '<br>';
            title += 'Title: ' + shortTitle + '<br>';
            title += filename + '<br>' + val.id;

            let slipClass = 'editSlipLink';
            let slipLinkText = 'add';
            let createSlipStyle = 'createSlipLink';

            let slipUrl =
                '?m=collection&a=add' +
                '&filename=' + encodeURIComponent(filename) +
                '&wid=' + encodeURIComponent(val.id) +
                '&headword=' + encodeURIComponent(headword) +
                '&pos=' + encodeURIComponent(pos);

            if (val.auto_id) {
                slipLinkText = 'view';
                slipClass = 'slipLink2';
                createSlipStyle = '';
                slipUrl = '#';
            }

            html += '<tr>';

            html += '<td>' + tid + '</td>';

            html += '<td>' + dateDisplay + '</td>';

            html += '<td>' + shortTitle + '</td>';

            html += '<td style="text-align: right;">' + pre + '</td>';

            html +=
                '<td>' +
                '<a target="_blank" ' +
                'href="?m=corpus&a=browse&id=' + encodeURIComponent(tid) +
                '&wid=' + encodeURIComponent(val.id) + '"' +
                ' data-toggle="tooltip"' +
                ' data-html="true"' +
                ' title="' + title + '">' +
                match +
                '</a>' +
                '</td>';

            html += '<td>' + post + '</td>';

            html +=
                '<td><small>' +
                '<a href="' + slipUrl + '"' +
                ' target="_blank"' +
                ' class="' + slipClass + ' ' + createSlipStyle + '"' +
                ' data-uri="' + (val.uri ?? '') + '"';

            if (slipClass === 'slipLink2') {
                html += ' data-toggle="modal" data-target="#slipModal"';
            }

            html +=
                ' data-headword="' + headword + '"' +
                ' data-pos="' + pos + '"' +
                ' data-id="' + val.id + '"' +
                ' data-xml="' + filename + '"' +
                ' data-date="' + dateDisplay + '"' +
                ' data-title="' + shortTitle + '"' +
                ' data-page="' + (val.page ?? '') + '"' +
                ' data-auto_id="' + (val.auto_id ?? '') + '"' +
                '>' + slipLinkText + '</a></small></td>';

            html += '</tr>';
        });

        html += '</tbody>';

        return html;
    }

    $(function () {
        $('.loadDictResults').on('click', function () {
            var formNum = $(this).attr('data-formnum');
            var action = $(this).attr('data-action');
            if (action == 'hide') {
                $('#results-'+formNum).hide();
                $(this).attr('data-action', 'show');
                $(this).find('.actionToggle').text('show'); //switch the toggle text to "show"
                return;
            }
            $('#results-'+formNum).show();
            $(this).find('.actionToggle').text('hide'); //switch the toggle text to "hide"
            $('#loadingImage-'+formNum).show();
            var locations = $(this).attr('data-locs');
            var headword = $(this).attr('data-lemma');
            var pos = $(this).attr('data-pos');
            var table = $('#form-'+formNum);
            var params = {headword: headword, pos: pos}
            $(this).attr('data-action', 'hide');  //link to hide the results
            $('#pag-'+formNum).pagination({

                dataSource: 'ajax.php',
                locator: 'results',
                totalNumberLocator: function(response) {
                    return response.hits;
                },
                pageSize: 10,
                ajax: {
                    type: "POST",
                    data: {action: "getDictionaryResults", locs: locations},
                    //do something else here
                    /* beforeSend: function() {
                         table.html('Loading data from DASG ...');
                     }*/
                },
                callback: function(data, pagination) {

                    // getDictionaryResults gives us filename/id/context,
                    // but not the current text metadata.
                    const rows = data.map(row => {

                        // e.g. "54.xml" -> "54"
                        const tid = row.tid ??
                            String(row.filename || '').replace(/\.xml$/i, '');

                        return {
                            ...row,
                            tid: tid
                        };
                    });

                    const tids = [...new Set(
                        rows
                            .map(row => row.tid)
                            .filter(Boolean)
                    )];

                    const wids = [...new Set(
                        rows
                            .map(row => row.id)
                            .filter(Boolean)
                    )];

                    $.ajax({
                        url: 'ajax.php?action=getCombinedMetadata',
                        method: 'POST',
                        contentType: 'application/json',
                        dataType: 'json',
                        data: JSON.stringify({tids, wids}),

                        success: function ({textMeta, slipMeta}) {

                            const textMap = new Map(
                                (textMeta || []).map(meta => [
                                    String(meta.tid),
                                    meta
                                ])
                            );

                            const slipMap = new Map(
                                (slipMeta || []).map(meta => [
                                    String(meta.id),
                                    meta
                                ])
                            );

                            const enrichedData = rows.map(row => {

                                const text =
                                    textMap.get(String(row.tid)) || {};

                                const slip =
                                    slipMap.get(String(row.id)) || {};

                                return {
                                    ...row,
                                    ...text,
                                    ...slip
                                };
                            });

                            const html = template(enrichedData, params);

                            $('#loadingImage-' + formNum).hide();
                            table.html(html);
                        },

                        error: function(xhr, status, error) {
                            console.error(
                                'Dictionary metadata lookup failed:',
                                status,
                                error
                            );

                            $('#loadingImage-' + formNum).hide();
                        }
                    });
                }
            })
        })
    });

</script>




