
<style>
    /* Search results: deliberately scoped so the rest of DASG is unaffected. */
    .xsearch-shell {
        --xs-border: #d9dee5;
        --xs-muted: #667085;
        --xs-soft: #f7f8fa;
        --xs-softer: #fbfcfd;
        --xs-text: #25313c;
        --xs-accent: #2f6388;
        --xs-accent-soft: #edf4f8;
        color: var(--xs-text);
    }

    .xsearch-back {
        margin: 0 0 1rem;
    }

    .xsearch-back a {
        color: var(--xs-accent);
        font-weight: 600;
        text-decoration: none;
    }

    .xsearch-back a:hover { text-decoration: underline; }

    .xsearch-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .75rem 1.25rem;
        padding: .85rem 1rem;
        margin: .5rem 0 1rem;
        background: var(--xs-soft);
        border: 1px solid var(--xs-border);
        border-radius: .45rem;
    }

    .xsearch-toolbar label {
        margin: 0 .35rem 0 0;
        font-size: .875rem;
        font-weight: 600;
        color: #475467;
    }

    .xsearch-toolbar .form-control {
        height: calc(1.5em + .65rem + 2px);
        padding: .25rem 1.75rem .25rem .55rem;
        font-size: .875rem;
    }

    .xsearch-summary {
        margin-left: auto;
        color: var(--xs-muted);
        font-size: .9rem;
        white-space: nowrap;
    }

    .xsearch-loading {
        min-width: 7rem;
        color: var(--xs-muted);
        font-size: .9rem;
    }

    .xsearch-auto {
        text-align: right;
        margin: -.25rem 0 .4rem;
    }

    .xsearch-auto a { color: var(--xs-accent); }

    #searchResults,
    .dict-results-table {
        width: 100%;
        background: #fff;
        border: 1px solid var(--xs-border);
        border-radius: .45rem;
        overflow: hidden;
    }

    #searchResults thead th,
    .dict-results-table thead th {
        border-top: 0;
        border-bottom: 1px solid var(--xs-border);
        background: var(--xs-soft);
        color: #475467;
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .025em;
        text-transform: uppercase;
        vertical-align: middle;
    }

    #searchResults tbody tr,
    .dict-results-table tbody tr {
        border: 0;
        border-top: 1px solid #edf0f2;
    }

    #searchResults tbody tr:hover,
    .dict-results-table tbody tr:hover {
        background: var(--xs-softer);
    }

    #searchResults td,
    .dict-results-table td {
        padding-top: .7rem;
        padding-bottom: .7rem;
        vertical-align: middle;
    }

    /* Give the bibliographic metadata a very light visual grouping. */
    #searchResults tbody td:nth-child(2),
    #searchResults tbody td:nth-child(3),
    #searchResults tbody td:nth-child(4),
    .dict-results-table tbody td:nth-child(1),
    .dict-results-table tbody td:nth-child(2),
    .dict-results-table tbody td:nth-child(3) {
        background: #f7f9fb;
    }

    #searchResults tbody tr:hover td:nth-child(2),
    #searchResults tbody tr:hover td:nth-child(3),
    #searchResults tbody tr:hover td:nth-child(4),
    .dict-results-table tbody tr:hover td:nth-child(1),
    .dict-results-table tbody tr:hover td:nth-child(2),
    .dict-results-table tbody tr:hover td:nth-child(3) {
        background: #f1f5f8;
    }

    #searchResults td:nth-child(5),
    #searchResults td:nth-child(7),
    .dict-results-table td:nth-child(4),
    .dict-results-table td:nth-child(6) {
        line-height: 1.45;
    }

    #searchResults td:nth-child(6) a,
    .dict-results-table td:nth-child(5) a {
        display: inline-block;
        padding: .18rem .45rem;
        border-radius: .25rem;
        background: var(--xs-accent-soft);
        color: #173f5a;
        font-weight: 700;
        text-decoration: none;
    }

    #searchResults td:nth-child(6) a:hover,
    .dict-results-table td:nth-child(5) a:hover {
        background: #dfeef5;
        text-decoration: underline;
    }

    .dictionary-heading {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: .45rem .8rem;
        margin: .25rem 0 1rem;
        padding-bottom: .75rem;
        border-bottom: 1px solid var(--xs-border);
    }

    .dictionary-heading h4,
    .dictionary-heading h5 { margin: 0; }

    .dictionary-heading h5 {
        color: var(--xs-muted);
        font-size: .95rem;
        font-weight: 400;
    }

    .dictionary-sort {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: .45rem;
    }

    .dictionary-sort label {
        margin: 0;
        color: var(--xs-muted);
        font-size: .82rem;
        font-weight: 600;
    }

    .dictionary-sort .form-control {
        width: auto;
        height: calc(1.5em + .55rem + 2px);
        padding: .2rem 1.7rem .2rem .5rem;
        font-size: .82rem;
    }

    .dictionary-forms {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid var(--xs-border);
        border-radius: .45rem;
        overflow: hidden;
    }

    .dictionary-forms > tbody > tr > td {
        padding: .85rem .9rem;
        border-top: 1px solid #edf0f2;
        vertical-align: top;
    }

    .dictionary-forms > tbody > tr:first-child > td { border-top: 0; }
    .dictionary-forms > tbody > tr:hover { background: var(--xs-softer); }

    .dictionary-forms > tbody > tr > td:first-child {
        width: 18%;
        font-weight: 700;
        font-size: 1.02rem;
    }

    .dictionary-forms > tbody > tr > td:nth-child(2) {
        width: 10%;
        color: var(--xs-muted);
        font-style: italic;
    }

    .loadDictResults {
        color: var(--xs-accent);
        font-weight: 600;
        text-decoration: none;
    }

    .loadDictResults:hover { text-decoration: underline; }

    .dictionary-results-panel {
        margin-top: .75rem;
        padding: .75rem;
        background: var(--xs-soft);
        border: 1px solid #e7ebef;
        border-radius: .4rem;
    }

    .dictionary-results-panel table {
        width: 100%;
        background: #fff;
    }

    .paginationjs .paginationjs-pages li > a {
        transition: background-color .12s ease, color .12s ease;
    }

    @media (max-width: 991.98px) {
        .xsearch-summary { margin-left: 0; width: 100%; }
        .xsearch-toolbar { align-items: flex-start; }
        #searchResults { font-size: .9rem; }
    }
</style>


<div class="xsearch-shell"><p class="xsearch-back"><a href="index.php?m=corpus&a=xsearch&id=<?= $_GET["id"] ?>" title="Back to search">&larr; Back to search</a></p>



    <?php

    if ($_GET["view"] != 'dictionary') {    //i.e. standard search view

        echo <<<HTML
        
        
        <table id="searchResults" class="table-borderless" style="display: none;">
        </table>
        
        <div class="float-right"><small><a id="autoCreateRecords" href="#">Automatically create all records</a></small></div>
        <div class="row">
            <div class="col-3">
                <label for="pageSizeSelect">Results per page:</label>
                <select id="pageSizeSelect" class="form-control" style="width:auto; display:inline-block;">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="col-3">
                <ul id="pagination" class="pagination-sm"></ul>
            </div>
            <div class="col-2">
                <div id="loadingMessage" class="text-center my-3">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...
                </div>
            </div>
            <div class="col-4">
                <span id="resultsSummary" style="margin-left: 15px;"></span>
            </div>
            
        </div>
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

        // Dictionary forms default to most frequent first.
        // Preserve the API order for equal counts so the sort is stable/predictable.
        $dictionaryForms = array_values($searchResults['form']);
        foreach ($dictionaryForms as $i => &$dictionaryForm) {
            $dictionaryForm['_original_order'] = $i;
        }
        unset($dictionaryForm);

        usort($dictionaryForms, static function ($a, $b) {
            $countA = (int)($a['count'] ?? 0);
            $countB = (int)($b['count'] ?? 0);

            if ($countA === $countB) {
                return ($a['_original_order'] ?? 0) <=> ($b['_original_order'] ?? 0);
            }

            return $countB <=> $countA;
        });

        echo '<div class="dictionary-heading"><h4>' . htmlspecialchars($headForm) . '</h4>';
        echo '<h5>' . number_format($totalResults) . ' results</h5>';
        echo '<div class="dictionary-sort">';
        echo '<label for="dictionaryFrequencySort">Order forms</label>';
        echo '<select id="dictionaryFrequencySort" class="form-control">';
        echo '<option value="desc" selected>Most frequent first</option>';
        echo '<option value="asc">Least frequent first</option>';
        echo '</select>';
        echo '</div></div>';

        echo <<<HTML
    <table class="dictionary-forms">
        <tbody>
HTML;

        $formNum = 0;

        foreach ($dictionaryForms as $nextForm) {

            $formNum++;

            $wordForm = $nextForm['word-form'] ?? '';
            $pos = $nextForm['pos'] ?? '';
            $count = (int)($nextForm['count'] ?? 0);

            /*
             * /xforms now returns summary data only. Individual citations
             * are fetched on demand from the paginated /word search.
             */

            $htmlWordForm = htmlspecialchars(
                    $wordForm,
                    ENT_QUOTES,
                    'UTF-8'
            );

            $htmlHeadForm = htmlspecialchars(
                    $headForm,
                    ENT_QUOTES,
                    'UTF-8'
            );

            $htmlPos = htmlspecialchars(
                    $pos,
                    ENT_QUOTES,
                    'UTF-8'
            );

            echo <<<HTML
        <tr class="dictionary-form-row" data-count="{$count}" data-original-order="{$formNum}">
            <td>{$htmlWordForm}</td>
            <td>{$htmlPos}</td>
            <td>
                <a href="#"
                   id="show-{$formNum}"
                   data-formnum="{$formNum}"
                   data-word-form="{$htmlWordForm}"
                   data-head-form="{$htmlHeadForm}"
                   data-pos="{$htmlPos}"
                   data-count="{$count}"
                   data-action="show"
                   class="loadDictResults">
                    <span class="actionToggle">show</span> {$count} result(s)
                </a>

                <div id="results-{$formNum}" class="dictionary-results-panel" style="display:none;">
                
                   
                    <!--img
                        id="loadingImage-{$formNum}"
                        src="https://dasg.ac.uk/images/loading.gif"
                        width="400"
                        style="display: none;"
                        alt="Loading"
                    -->

                    <table id="form-{$formNum}" class="table table-borderless dict-results-table"></table>
                    <div class="row">
                        <div class="col-6">
                            <div id="pag-{$formNum}"></div>
                        </div>
                        <div class="col-1">
                            <div id="loadingMessage" class="text-center my-3">
                                <span id="loadingImage-{$formNum}" class="spinner-border spinner-border-sm" style="display: none;" aria-hidden="true"></span> 
                            </div>
                        </div>
                        <div class="col-5" id="dict-controls-{$formNum}" style="display:none; margin:10px 0;">    
                            <span id="dict-summary-{$formNum}" style="margin-left:15px;"></span>
                        </div>
                    </div>
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

    <script>
        $(function () {
            $('#dictionaryFrequencySort').on('change', function () {
                var direction = $(this).val();
                var $tbody = $('.dictionary-forms > tbody');
                var rows = $tbody.children('tr.dictionary-form-row').get();

                rows.sort(function (a, b) {
                    var countA = parseInt(a.getAttribute('data-count'), 10) || 0;
                    var countB = parseInt(b.getAttribute('data-count'), 10) || 0;

                    if (countA === countB) {
                        return (parseInt(a.getAttribute('data-original-order'), 10) || 0) -
                            (parseInt(b.getAttribute('data-original-order'), 10) || 0);
                    }

                    return direction === 'asc' ? countA - countB : countB - countA;
                });

                $.each(rows, function (_, row) {
                    $tbody.append(row);
                });
            });
        });
    </script>

</div><!-- /.xsearch-shell -->

<style>
    .paginationjs{line-height:1.6;font-family:Marmelad,"Lucida Grande",Arial,"Hiragino Sans GB",Georgia,sans-serif;font-size:14px;box-sizing:initial}.paginationjs:after{display:table;content:" ";clear:both}.paginationjs .paginationjs-pages{float:left}.paginationjs .paginationjs-pages ul{float:left;margin:0;padding:0}.paginationjs .paginationjs-go-button,.paginationjs .paginationjs-go-input,.paginationjs .paginationjs-nav{float:left;margin-left:10px;font-size:14px}.paginationjs .paginationjs-pages li{float:left;border:1px solid #aaa;border-right:none;list-style:none}.paginationjs .paginationjs-pages li>a{min-width:30px;height:28px;line-height:28px;display:block;background:#fff;font-size:14px;color:#333;text-decoration:none;text-align:center}.paginationjs .paginationjs-pages li>a:hover{background:#eee}.paginationjs .paginationjs-pages li.active{border:none}.paginationjs .paginationjs-pages li.active>a{height:30px;line-height:30px;background:#aaa;color:#fff}.paginationjs .paginationjs-pages li.disabled>a{opacity:.3}.paginationjs .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs .paginationjs-pages li:first-child,.paginationjs .paginationjs-pages li:first-child>a{border-radius:3px 0 0 3px}.paginationjs .paginationjs-pages li:last-child{border-right:1px solid #aaa;border-radius:0 3px 3px 0}.paginationjs .paginationjs-pages li:last-child>a{border-radius:0 3px 3px 0}.paginationjs .paginationjs-go-input>input[type=text]{width:30px;height:28px;background:#fff;border-radius:3px;border:1px solid #aaa;padding:0;font-size:14px;text-align:center;vertical-align:baseline;outline:0;box-shadow:none;box-sizing:initial}.paginationjs .paginationjs-go-button>input[type=button]{min-width:40px;height:30px;line-height:28px;background:#fff;border-radius:3px;border:1px solid #aaa;text-align:center;padding:0 8px;font-size:14px;vertical-align:baseline;outline:0;box-shadow:none;color:#333;cursor:pointer;vertical-align:middle\9}.paginationjs.paginationjs-theme-blue .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-blue .paginationjs-pages li{border-color:#289de9}.paginationjs .paginationjs-go-button>input[type=button]:hover{background-color:#f8f8f8}.paginationjs .paginationjs-nav{height:30px;line-height:30px}.paginationjs .paginationjs-go-button,.paginationjs .paginationjs-go-input{margin-left:5px\9}.paginationjs.paginationjs-small{font-size:12px}.paginationjs.paginationjs-small .paginationjs-pages li>a{min-width:26px;height:24px;line-height:24px;font-size:12px}.paginationjs.paginationjs-small .paginationjs-pages li.active>a{height:26px;line-height:26px}.paginationjs.paginationjs-small .paginationjs-go-input{font-size:12px}.paginationjs.paginationjs-small .paginationjs-go-input>input[type=text]{width:26px;height:24px;font-size:12px}.paginationjs.paginationjs-small .paginationjs-go-button{font-size:12px}.paginationjs.paginationjs-small .paginationjs-go-button>input[type=button]{min-width:30px;height:26px;line-height:24px;padding:0 6px;font-size:12px}.paginationjs.paginationjs-small .paginationjs-nav{height:26px;line-height:26px;font-size:12px}.paginationjs.paginationjs-big{font-size:16px}.paginationjs.paginationjs-big .paginationjs-pages li>a{min-width:36px;height:34px;line-height:34px;font-size:16px}.paginationjs.paginationjs-big .paginationjs-pages li.active>a{height:36px;line-height:36px}.paginationjs.paginationjs-big .paginationjs-go-input{font-size:16px}.paginationjs.paginationjs-big .paginationjs-go-input>input[type=text]{width:36px;height:34px;font-size:16px}.paginationjs.paginationjs-big .paginationjs-go-button{font-size:16px}.paginationjs.paginationjs-big .paginationjs-go-button>input[type=button]{min-width:50px;height:36px;line-height:34px;padding:0 12px;font-size:16px}.paginationjs.paginationjs-big .paginationjs-nav{height:36px;line-height:36px;font-size:16px}.paginationjs.paginationjs-theme-blue .paginationjs-pages li>a{color:#289de9}.paginationjs.paginationjs-theme-blue .paginationjs-pages li>a:hover{background:#e9f4fc}.paginationjs.paginationjs-theme-blue .paginationjs-pages li.active>a{background:#289de9;color:#fff}.paginationjs.paginationjs-theme-blue .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-blue .paginationjs-go-button>input[type=button]{background:#289de9;border-color:#289de9;color:#fff}.paginationjs.paginationjs-theme-green .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-green .paginationjs-pages li{border-color:#449d44}.paginationjs.paginationjs-theme-blue .paginationjs-go-button>input[type=button]:hover{background-color:#3ca5ea}.paginationjs.paginationjs-theme-green .paginationjs-pages li>a{color:#449d44}.paginationjs.paginationjs-theme-green .paginationjs-pages li>a:hover{background:#ebf4eb}.paginationjs.paginationjs-theme-green .paginationjs-pages li.active>a{background:#449d44;color:#fff}.paginationjs.paginationjs-theme-green .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-green .paginationjs-go-button>input[type=button]{background:#449d44;border-color:#449d44;color:#fff}.paginationjs.paginationjs-theme-yellow .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-yellow .paginationjs-pages li{border-color:#ec971f}.paginationjs.paginationjs-theme-green .paginationjs-go-button>input[type=button]:hover{background-color:#55a555}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li>a{color:#ec971f}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li>a:hover{background:#fdf5e9}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li.active>a{background:#ec971f;color:#fff}.paginationjs.paginationjs-theme-yellow .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-yellow .paginationjs-go-button>input[type=button]{background:#ec971f;border-color:#ec971f;color:#fff}.paginationjs.paginationjs-theme-red .paginationjs-go-input>input[type=text],.paginationjs.paginationjs-theme-red .paginationjs-pages li{border-color:#c9302c}.paginationjs.paginationjs-theme-yellow .paginationjs-go-button>input[type=button]:hover{background-color:#eea135}.paginationjs.paginationjs-theme-red .paginationjs-pages li>a{color:#c9302c}.paginationjs.paginationjs-theme-red .paginationjs-pages li>a:hover{background:#faeaea}.paginationjs.paginationjs-theme-red .paginationjs-pages li.active>a{background:#c9302c;color:#fff}.paginationjs.paginationjs-theme-red .paginationjs-pages li.disabled>a:hover{background:0 0}.paginationjs.paginationjs-theme-red .paginationjs-go-button>input[type=button]{background:#c9302c;border-color:#c9302c;color:#fff}.paginationjs.paginationjs-theme-red .paginationjs-go-button>input[type=button]:hover{background-color:#ce4541}.paginationjs .paginationjs-pages li.paginationjs-next{border-right:1px solid #aaa\9}.paginationjs .paginationjs-go-input>input[type=text]{line-height:28px\9;vertical-align:middle\9}.paginationjs.paginationjs-big .paginationjs-pages li>a{line-height:36px\9}.paginationjs.paginationjs-big .paginationjs-go-input>input[type=text]{height:36px\9;line-height:36px\9}
</style>

<script src="js/pagination.min.js"></script>

<script>
    $(document).ready(function () {

        // load the results data into the paginated bootstrap table

        const q = "<?= addslashes($params['q']) ?>";
        const mode = "<?= addslashes($params['mode']) ?>";
        const text = "<?= addslashes($params['text']) ?>";

        let pageSize = 10;
        let totalResults = null;
        let paginationInitialised = false;

        function loadPage(pageNumber, includeTotal = false) {

            const start = ((pageNumber - 1) * pageSize) + 1;

            const xsearchUrl =
                'ajax.php?action=xsearch' +
                '&q=' + encodeURIComponent(q) +
                '&mode=' + encodeURIComponent(mode) +
                '&text=' + encodeURIComponent(text) +
                '&start=' + start +
                '&limit=' + pageSize +
                '&include-total=' + (includeTotal ? 'true' : 'false');

            $('#loadingMessage').show();

            $.getJSON(xsearchUrl, function (rawData) {

                if (!rawData || !rawData.rows || rawData.rows.length === 0) {

                    if (pageNumber === 1) {
                        $('#loadingMessage').html(
                            '<h3>There were no results for <em><?= htmlspecialchars($params['q'], ENT_QUOTES) ?></em></h3>'
                        );
                    }

                    return;
                }

                if (includeTotal && rawData.total !== undefined) {
                    totalResults = Number(rawData.total);
                }

                const tids = [
                    ...new Set(
                        rawData.rows
                            .map(row => row.tid || row.textid)
                            .filter(Boolean)
                    )
                ];

                const wids = [
                    ...new Set(
                        rawData.rows
                            .map(row => row.id)
                            .filter(Boolean)
                    )
                ];


                console.log('rawData:', rawData);
                console.log('tids:', tids);
                console.log('wids:', wids);

                $.ajax({
                    url: 'ajax.php?action=getCombinedMetadata',
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify({tids, wids}),

                    success: function ({textMeta, slipMeta}) {

                        const textMap = new Map(
                            (textMeta || []).map(meta => [String(meta.tid), meta])
                        );

                        const slipMap = new Map(
                            (slipMeta || []).map(meta => [String(meta.id), meta])
                        );

                        const enrichedData = rawData.rows.map((row, index) => {

                            const tid = String(row.tid || row.textid || '');

                            const textMetaRow =
                                textMap.get(tid) || {};

                            const slip =
                                slipMap.get(String(row.id)) || {};

                            const filename =
                                row.filename ||
                                (tid ? tid + '.xml' : '');

                            const title = textMetaRow.short_title
                                ? String(textMetaRow.short_title).replace(/"/g, '&quot;')
                                : '';

                            const matchLink =
                                `<a target="_blank" ` +
                                `href="?m=corpus&a=browse&id=${encodeURIComponent(tid)}&wid=${encodeURIComponent(row.id)}" ` +
                                `data-toggle="tooltip" data-html="true" title="${title}">` +
                                `${row.match}` +
                                `</a>`;

                            console.log('textMeta:', textMeta);
                            console.log('slipMeta:', slipMeta);

                            return {
                                ...row,
                                ...textMetaRow,

                                tid: tid,
                                textid: tid,
                                filename: filename,

                                pre: row.pre || '',
                                match: matchLink,
                                post: row.post || '',

                                row: start + index,

                                slipHtml: buildSlipHtml(
                                    slip,
                                    {
                                        ...row,
                                        ...textMetaRow,
                                        tid: tid,
                                        textid: tid,
                                        filename: filename
                                    },
                                    start + index - 1
                                )
                            };
                        });

                        renderTable(enrichedData);
                        updateResultsSummary(pageNumber, enrichedData.length);

                        if (includeTotal && !paginationInitialised && totalResults !== null) {
                            rebuildPagination();
                        }

                        $('#loadingMessage').hide();
                        $('#searchResults').show();
                    },

                    error: function (xhr, status, error) {
                        console.error("Metadata fetch error:", error);
                        $('#loadingMessage').hide();
                    }
                });
            });
        }

        function renderTable(rows) {

            $('#searchResults')
                .bootstrapTable('destroy')
                .bootstrapTable({
                    idField: 'id',
                    uniqueId: 'id',

                    data: rows,

                    // Pagination is handled externally
                    pagination: false,

                    // Search/sort here only affect the current page
                    search: false,

                    columns: [
                        {
                            field: 'row',
                            title: 'Row',
                            formatter: value => `<strong>${value}</strong>`,
                            sortable: false
                        },
                        {
                            field: 'tid',
                            title: 'Reference',
                            sortable: false
                        },
                        {
                            field: 'date_display',
                            title: 'Date',
                            sortable: false
                        },
                        {
                            field: 'short_title',
                            title: 'Short Title',
                            sortable: false
                        },
                        {
                            field: 'pre',
                            title: 'Pre Context',
                            align: 'right'
                        },
                        {
                            field: 'match',
                            title: 'Match',
                            align: 'center',
                            formatter: value => value
                        },
                        {
                            field: 'post',
                            title: 'Post Context'
                        },
                        {
                            field: 'slipHtml',
                            title: 'Slip',
                            escape: false,
                            sortable: false
                        }
                    ]
                });
        }

        loadPage(1, true);

        // number of results per page handler

        $('#pageSizeSelect').on('change', function () {

            pageSize = parseInt($(this).val(), 10);

            // Destroy the existing paginator so it can be rebuilt
            // using the new page size.
            $('#pagination').pagination('destroy');

            paginationInitialised = false;

            // We already know the total, so don't ask Elemental
            // to calculate it again.
            rebuildPagination();

            // Changing page size takes us back to page 1.
            loadPage(1, false);
        });

        function rebuildPagination() {

            if (totalResults === null) {
                return;
            }

            let initialPaginationCallback = true;

            $('#pagination').pagination({
                dataSource: new Array(totalResults),
                pageSize: pageSize,
                pageNumber: 1,

                callback: function (data, pagination) {

                    // Ignore the callback fired automatically when
                    // the paginator is created.
                    if (initialPaginationCallback) {
                        initialPaginationCallback = false;
                        return;
                    }

                    loadPage(pagination.pageNumber, false);
                }
            });

            paginationInitialised = true;
        }

        function updateResultsSummary(pageNumber, resultCount) {

            if (totalResults === null) {
                return;
            }

            if (totalResults === 0) {
                $('#resultsSummary').text('0 results');
                return;
            }

            const first = ((pageNumber - 1) * pageSize) + 1;
            const last = first + resultCount - 1;

            $('#resultsSummary').html(
                `Showing <strong>${first.toLocaleString()}–${last.toLocaleString()}</strong> of <strong>${totalResults.toLocaleString()}</strong> results`
            );
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
        const dictionaryStates = {};
        const dictionaryTextFilter = "<?= addslashes($params['text'] ?? '') ?>";

        function updateDictionarySummary(formNum, pageNumber, resultCount) {
            const state = dictionaryStates[formNum];
            if (!state) return;

            if (state.total === 0) {
                $('#dict-summary-' + formNum).text('0 results');
                return;
            }

            const first = ((pageNumber - 1) * state.pageSize) + 1;
            const last = first + resultCount - 1;

            $('#dict-summary-' + formNum).html(
                `Showing <strong>${first.toLocaleString()}–${last.toLocaleString()}</strong> ` +
                `of <strong>${state.total.toLocaleString()}</strong> results`
            );
        }

        function loadDictionaryPage(formNum, pageNumber) {
            const state = dictionaryStates[formNum];
            if (!state) return;

            const start = ((pageNumber - 1) * state.pageSize) + 1;
            const table = $('#form-' + formNum);

            $('#loadingImage-' + formNum).show();

            const url =
                'ajax.php?action=xsearch' +
                '&q=' + encodeURIComponent(state.wordForm) +
                '&mode=word-form' +
                '&head-form=' + encodeURIComponent(state.headForm) +
                '&pos=' + encodeURIComponent(state.pos) +
                '&text=' + encodeURIComponent(dictionaryTextFilter) +
                '&start=' + start +
                '&limit=' + state.pageSize +
                '&include-total=false';

            $.getJSON(url, function (rawData) {
                const rows = (rawData && rawData.rows) ? rawData.rows : [];

                if (rows.length === 0) {
                    $('#loadingImage-' + formNum).hide();
                    table.html('<tbody><tr><td>No results</td></tr></tbody>');
                    updateDictionarySummary(formNum, pageNumber, 0);
                    return;
                }

                const normalisedRows = rows.map(row => {
                    const tid = row.tid || row.textid ||
                        String(row.filename || '').replace(/\.xml$/i, '');

                    return {
                        ...row,
                        tid: String(tid || ''),
                        filename: row.filename || (tid ? tid + '.xml' : '')
                    };
                });

                const tids = [...new Set(
                    normalisedRows.map(row => row.tid).filter(Boolean)
                )];

                const wids = [...new Set(
                    normalisedRows.map(row => row.id).filter(Boolean)
                )];

                $.ajax({
                    url: 'ajax.php?action=getCombinedMetadata',
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify({tids, wids}),

                    success: function ({textMeta, slipMeta}) {
                        const textMap = new Map(
                            (textMeta || []).map(meta => [String(meta.tid), meta])
                        );

                        const slipMap = new Map(
                            (slipMeta || []).map(meta => [String(meta.id), meta])
                        );

                        const enrichedData = normalisedRows.map(row => ({
                            ...row,
                            ...(textMap.get(String(row.tid)) || {}),
                            ...(slipMap.get(String(row.id)) || {})
                        }));

                        table.html(template(enrichedData, {
                            headword: state.headForm,
                            pos: state.pos
                        }));

                        updateDictionarySummary(
                            formNum,
                            pageNumber,
                            enrichedData.length
                        );

                        $('#dict-controls-' + formNum).show();
                        $('#loadingImage-' + formNum).hide();
                    },

                    error: function (xhr, status, error) {
                        console.error('Dictionary metadata lookup failed:', status, error);
                        $('#loadingImage-' + formNum).hide();
                    }
                });
            }).fail(function (xhr, status, error) {
                console.error('Dictionary search failed:', status, error);
                $('#loadingImage-' + formNum).hide();
            });
        }

        function rebuildDictionaryPagination(formNum) {
            const state = dictionaryStates[formNum];
            if (!state) return;

            const paginator = $('#pag-' + formNum);
            paginator.empty();

            let initialPaginationCallback = true;

            paginator.pagination({
                dataSource: new Array(state.total),
                pageSize: state.pageSize,
                pageNumber: 1,

                callback: function (data, pagination) {
                    if (initialPaginationCallback) {
                        initialPaginationCallback = false;
                        return;
                    }

                    loadDictionaryPage(formNum, pagination.pageNumber);
                }
            });
        }

        $('.loadDictResults').on('click', function (event) {
            event.preventDefault();

            const link = $(this);
            const formNum = link.attr('data-formnum');
            const action = link.attr('data-action');

            if (action === 'hide') {
                $('#results-' + formNum).hide();
                link.attr('data-action', 'show');
                link.find('.actionToggle').text('show');
                return;
            }

            $('#results-' + formNum).show();
            link.attr('data-action', 'hide');
            link.find('.actionToggle').text('hide');

            // If this form has already been loaded, simply reveal it again.
            if (dictionaryStates[formNum]) {
                return;
            }

            dictionaryStates[formNum] = {
                wordForm: link.attr('data-word-form') || '',
                headForm: link.attr('data-head-form') || '',
                pos: link.attr('data-pos') || '',
                total: Number(link.attr('data-count') || 0),
                pageSize: 10
            };

            loadDictionaryPage(formNum, 1);
            rebuildDictionaryPagination(formNum);
        });

        $('.dict-page-size').on('change', function () {
            const formNum = $(this).attr('data-formnum');
            const state = dictionaryStates[formNum];
            if (!state) return;

            state.pageSize = parseInt($(this).val(), 10) || 10;

            rebuildDictionaryPagination(formNum);
            loadDictionaryPage(formNum, 1);
        });
    });

</script>




