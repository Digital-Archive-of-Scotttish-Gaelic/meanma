<?php
/**
 * MEANMA frequency list view.
 *
 * Standalone view for the loose MVC structure. The view talks to the
 * RESTXQ /frequency/lemma endpoint directly and renders a Sketch Engine-
 * inspired multi-column word list using the same basic visual language as
 * xsearch-results.php.
 *
 * Expected endpoint:
 *   http://localhost:8080/exist/restxq/frequency/lemma
 *
 * Supported API parameters:
 *   min, max, pos, start, limit, order
 */
?>

<style>
    .frequency-shell {
        --fq-border: #d9dee5;
        --fq-muted: #667085;
        --fq-soft: #f7f8fa;
        --fq-softer: #fbfcfd;
        --fq-text: #25313c;
        --fq-accent: #2f6388;
        --fq-accent-soft: #edf4f8;
        color: var(--fq-text);
    }

    .frequency-heading {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: .5rem .9rem;
        margin: .25rem 0 .9rem;
        padding-bottom: .7rem;
        border-bottom: 1px solid var(--fq-border);
    }

    .frequency-heading h3 {
        margin: 0;
        font-weight: 600;
    }

    .frequency-heading .frequency-heading-summary {
        color: var(--fq-muted);
        font-size: .95rem;
    }

    .frequency-intro {
        margin: -.25rem 0 1rem;
        color: var(--fq-muted);
        font-size: .92rem;
    }

    .frequency-toolbar {
        display: flex;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: .75rem 1rem;
        padding: .9rem 1rem;
        margin-bottom: 1rem;
        background: var(--fq-soft);
        border: 1px solid var(--fq-border);
        border-radius: .45rem;
    }

    .frequency-control {
        min-width: 115px;
    }

    .frequency-control.frequency-control-order {
        min-width: 175px;
    }

    .frequency-control label {
        display: block;
        margin: 0 0 .25rem;
        color: #475467;
        font-size: .78rem;
        font-weight: 700;
    }

    .frequency-control .form-control {
        height: calc(1.5em + .65rem + 2px);
        padding: .25rem .55rem;
        font-size: .875rem;
    }

    .frequency-toolbar-actions {
        display: flex;
        gap: .45rem;
        align-items: center;
    }

    .frequency-toolbar .btn {
        font-size: .875rem;
    }

    .frequency-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem 1rem;
        margin: 0 0 .65rem;
        color: var(--fq-muted);
        font-size: .86rem;
    }

    .frequency-generated {
        margin-left: auto;
    }

    .frequency-loading {
        padding: 2.5rem 1rem;
        text-align: center;
        color: var(--fq-muted);
    }

    /*
     * CSS columns
     */
    .frequency-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: .8rem;
        align-items: start;
        margin-bottom: 1rem;
    }

    .frequency-block {
        display: inline-block;
        width: 100%;
        margin: 0 0 .8rem;
        vertical-align: top;
        break-inside: avoid;
        border: 1px solid var(--fq-border);
        border-radius: .4rem;
        overflow: hidden;
        background: #fff;
    }

    .frequency-block-header,
    .frequency-row {
        display: grid;
        grid-template-columns: 2.25rem minmax(0, 1fr) auto;
        align-items: center;
    }

    .frequency-block-header {
        min-height: 2rem;
        background: var(--fq-accent-soft);
        border-bottom: 1px solid var(--fq-border);
        color: #475467;
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .frequency-block-header .frequency-header-lemma {
        padding: .45rem .45rem;
    }

    .frequency-block-header .frequency-header-value {
        padding: .45rem .6rem .45rem .3rem;
        text-align: right;
    }

    .frequency-row {
        min-height: 2.25rem;
        border-top: 1px solid #edf0f2;
        font-size: .9rem;
    }

    .frequency-block-header + .frequency-row {
        border-top: 0;
    }

    .frequency-row:hover {
        background: var(--fq-softer);
    }

    .frequency-rank {
        padding: .45rem .3rem .45rem .55rem;
        color: var(--fq-muted);
        font-size: .78rem;
        text-align: right;
    }

    .frequency-lemma {
        min-width: 0;
        padding: .45rem .4rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 600;
    }

    .frequency-lemma a {
        color: var(--fq-accent);
        text-decoration: none;
    }

    .frequency-lemma a:hover {
        text-decoration: underline;
    }

    .frequency-value {
        padding: .45rem .6rem .45rem .35rem;
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .frequency-footer {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .75rem 1rem;
        padding: .8rem 0;
        border-top: 1px solid var(--fq-border);
    }

    .frequency-footer .frequency-page-summary {
        color: var(--fq-muted);
        font-size: .88rem;
    }

    .frequency-pagination {
        display: flex;
        gap: .3rem;
        margin-left: auto;
        align-items: center;
    }

    .frequency-pagination .btn {
        min-width: 2.25rem;
    }

    .frequency-page-number {
        min-width: 5.5rem;
        color: var(--fq-muted);
        font-size: .86rem;
        text-align: center;
    }

    .frequency-error {
        padding: 1rem;
        border: 1px solid #e5b9b9;
        border-radius: .4rem;
        background: #fff6f6;
        color: #842029;
    }

    @media (max-width: 1399.98px) {
        .frequency-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 1199.98px) {
        .frequency-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .frequency-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .frequency-generated {
            margin-left: 0;
            width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .frequency-grid {
            grid-template-columns: 1fr;
        }
        .frequency-control,
        .frequency-control.frequency-control-order {
            width: 100%;
        }
        .frequency-toolbar-actions {
            width: 100%;
        }
        .frequency-toolbar-actions .btn {
            flex: 1;
        }
        .frequency-pagination {
            width: 100%;
            margin-left: 0;
            justify-content: center;
        }
    }
</style>

<div class="frequency-shell">

    <div class="frequency-heading">
        <h3><span id="frequencyTypeHeading">Lemma</span> frequency list</h3>
        <span id="frequencyHeadingSummary" class="frequency-heading-summary"></span>
    </div>

    <p class="frequency-intro">
        Browse lemmas by frequency.
    </p>

    <form id="frequencyFilters" class="frequency-toolbar" autocomplete="off">

        <div class="frequency-control frequency-control-order">
            <label for="frequencyType">Frequency type</label>
            <select id="frequencyType" class="form-control">
                <option value="lemma" selected>Lemma</option>
                <option value="word-form">Word form</option>
            </select>
        </div>

        <div class="frequency-control">
            <label for="frequencyPos">Part of speech</label>
            <select id="frequencyPos" class="form-control">
                <option value="">All</option>
                <!--
                    POS values can be added here once we expose them from the API.
                    For the current corpus the lemmatiser is known to tag words as n.
                -->
                <option value="n">n</option>
            </select>
        </div>

        <div class="frequency-control">
            <label for="frequencyMin">Minimum frequency</label>
            <input
                type="number"
                id="frequencyMin"
                class="form-control"
                min="1"
                step="1"
                value="1">
        </div>

        <div class="frequency-control">
            <label for="frequencyMax">Maximum frequency</label>
            <input
                type="number"
                id="frequencyMax"
                class="form-control"
                min="1"
                step="1"
                placeholder="No maximum">
        </div>

        <div class="frequency-control frequency-control-order">
            <label for="frequencyOrder">Order</label>
            <select id="frequencyOrder" class="form-control">
                <option value="desc" selected>Most frequent first</option>
                <option value="asc">Least frequent first</option>
            </select>
        </div>

        <div class="frequency-control">
            <label for="frequencyLimit">Results per page</label>
            <select id="frequencyLimit" class="form-control">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
                <option value="250">250</option>
            </select>
        </div>

        <div class="frequency-toolbar-actions">
            <button type="submit" class="btn btn-primary">Apply filters</button>
            <button type="button" id="frequencyReset" class="btn btn-outline-secondary">Reset</button>
        </div>
    </form>

    <div class="frequency-meta">
        <span id="frequencyResultSummary"></span>
        <span id="frequencyGenerated" class="frequency-generated"></span>
    </div>

    <div id="frequencyLoading" class="frequency-loading">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Loading frequency list...
    </div>

    <div id="frequencyError" class="frequency-error" style="display:none;"></div>

    <div id="frequencyGrid" class="frequency-grid" style="display:none;"></div>

    <div id="frequencyFooter" class="frequency-footer" style="display:none;">
        <span id="frequencyPageSummary" class="frequency-page-summary"></span>

        <div class="frequency-pagination">
            <button type="button" id="frequencyFirst" class="btn btn-sm btn-outline-secondary" title="First page">&laquo;</button>
            <button type="button" id="frequencyPrevious" class="btn btn-sm btn-outline-secondary" title="Previous page">&lsaquo;</button>

            <span id="frequencyPageNumber" class="frequency-page-number"></span>

            <button type="button" id="frequencyNext" class="btn btn-sm btn-outline-secondary" title="Next page">&rsaquo;</button>
            <button type="button" id="frequencyLast" class="btn btn-sm btn-outline-secondary" title="Last page">&raquo;</button>
        </div>
    </div>

</div>

<script>
$(function () {

    const frequencyApi = 'ajax.php';

    let currentPage = 1;
    let totalResults = 0;
    let currentLimit = 50;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function numberFormat(value) {
        return Number(value || 0).toLocaleString('en-GB');
    }

    function parseGeneratedDate(value) {
        if (!value) {
            return '';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString('en-GB', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /*

     * the lemma is rendered the lemma stored in
     * data-lemma and a link to the main search.
     */
    function termHref(term, type) {
        const params = new URLSearchParams();

        params.set('m', 'corpus');
        params.set('a', 'xsearch');
        params.set('q', term);
        params.set('mode', type === 'word-form' ? 'word-form' : 'head-form');

        return '?' + params.toString();
    }

    function getFilters() {
        return {
            type: $('#frequencyType').val() || 'lemma',
            pos: $('#frequencyPos').val() || '',
            min: $('#frequencyMin').val() || '1',
            max: $('#frequencyMax').val() || '',
            order: $('#frequencyOrder').val() || 'desc',
            limit: parseInt($('#frequencyLimit').val(), 10) || 50
        };
    }

    function buildApiUrl(page) {
        const filters = getFilters();
        const start = ((page - 1) * filters.limit) + 1;

        const params = new URLSearchParams();
        params.set('action', 'getFrequencies');
        params.set('type', filters.type);

        if (filters.pos !== '') {
            params.set('pos', filters.pos);
        }

        if (filters.min !== '') {
            params.set('min', filters.min);
        }

        if (filters.max !== '') {
            params.set('max', filters.max);
        }

        params.set('order', filters.order);
        params.set('start', start);
        params.set('limit', filters.limit);

        return frequencyApi + '?' + params.toString();
    }

    function normaliseEntries(data) {
        if (!data || !Array.isArray(data.rows)) {
            return [];
        }

        return data.rows.map(function (row) {
            return {
                term: row.term || '',
                frequency: parseInt(row.frequency, 10) || 0
            };
        });
    }

    function renderGrid(entries, start) {
        const $grid = $('#frequencyGrid');
        $grid.empty();

        if (!entries.length) {
            $grid.hide();
            return;
        }

        /*
         * Aim for five vertical blocks on a wide desktop, matching the
         * Sketch Engine scanning pattern. CSS then reduces the number of
         * columns responsively.
         */
        const filters = getFilters();
        const typeLabel = filters.type === 'word-form' ? 'Word form' : 'Lemma';
        const desiredBlocks = 5;
        const rowsPerBlock = Math.ceil(entries.length / desiredBlocks);

        for (let blockStart = 0; blockStart < entries.length; blockStart += rowsPerBlock) {

            const blockEntries = entries.slice(
                blockStart,
                blockStart + rowsPerBlock
            );

            const $block = $('<div>', {
                class: 'frequency-block'
            });

            $block.append(
                '<div class="frequency-block-header">' +
                    '<span></span>' +
                    '<span class="frequency-header-lemma">' + typeLabel + '</span>' +
                    '<span class="frequency-header-value">Frequency</span>' +
                '</div>'
            );

            blockEntries.forEach(function (entry, index) {

                const absoluteIndex = start + blockStart + index;
                const term = entry.term;

                const $row = $('<div>', {
                    class: 'frequency-row'
                });

                $row.append(
                    $('<span>', {
                        class: 'frequency-rank',
                        text: numberFormat(absoluteIndex)
                    })
                );

                const $lemmaCell = $('<span>', {
                    class: 'frequency-lemma'
                });

                const $lemmaLink = $('<a>', {
                    href: termHref(term, filters.type),
                    class: 'frequency-lemma-link',
                    'data-term': term,
                    text: term,
                    title: term
                });

                $lemmaCell.append($lemmaLink);

                $row.append($lemmaCell);

                $row.append(
                    $('<span>', {
                        class: 'frequency-value',
                        text: numberFormat(entry.frequency)
                    })
                );

                $block.append($row);
            });

            $grid.append($block);
        }

        $grid.show();
    }

    function updatePagination(start, returnedCount) {
        const totalPages = Math.max(1, Math.ceil(totalResults / currentLimit));
        const end = returnedCount > 0
            ? Math.min(start + returnedCount - 1, totalResults)
            : 0;

        $('#frequencyPageNumber').text(
            'Page ' + numberFormat(currentPage) +
            ' / ' + numberFormat(totalPages)
        );

        $('#frequencyPageSummary').text(
            returnedCount > 0
                ? numberFormat(start) + '–' + numberFormat(end) +
                  ' of ' + numberFormat(totalResults)
                : 'No matching lemmas'
        );

        $('#frequencyFirst, #frequencyPrevious')
            .prop('disabled', currentPage <= 1);

        $('#frequencyNext, #frequencyLast')
            .prop('disabled', currentPage >= totalPages);

        $('#frequencyFooter').show();
    }

    function loadFrequencyPage(page) {
        currentPage = Math.max(1, page);

        const filters = getFilters();
        currentLimit = filters.limit;

        const start = ((currentPage - 1) * currentLimit) + 1;

        $('#frequencyError').hide().empty();
        $('#frequencyGrid').hide().empty();
        $('#frequencyFooter').hide();
        $('#frequencyLoading').show();

        $.ajax({
            url: buildApiUrl(currentPage),
            method: 'GET',
            dataType: 'json',
            cache: false,

            success: function (data) {

                if (!data || data.error) {
                    showError(
                        data && data.error
                            ? data.error
                            : 'The frequency API returned an unexpected response.'
                    );
                    return;
                }

                totalResults = parseInt(data.total, 10) || 0;

                const apiStart =
                    parseInt(data.start, 10) || start;

                const entries = normaliseEntries(data);
                const typeLabel = data.type === 'word-form' ? 'Word form' : 'Lemma';
                const typePlural = data.type === 'word-form' ? 'word forms' : 'lemmas';

                $('#frequencyTypeHeading').text(typeLabel);

                $('#frequencyHeadingSummary').text(
                    '(' + numberFormat(totalResults) + ' matching ' + typePlural + ')'
                );

                $('#frequencyResultSummary').text(
                    totalResults
                        ? 'Showing ' +
                        numberFormat(apiStart) + '–' +
                        numberFormat(
                            Math.min(
                                apiStart + entries.length - 1,
                                totalResults
                            )
                        ) +
                        ' of ' + numberFormat(totalResults) + ' ' + typePlural
                        : 'No ' + typePlural + ' match the current filters'
                );

                const generated = parseGeneratedDate(
                    data.generated
                );

                $('#frequencyGenerated').text(
                    generated
                        ? 'Frequency data generated: ' + generated
                        : ''
                );

                renderGrid(entries, apiStart);
                updatePagination(apiStart, entries.length);

                $('#frequencyLoading').hide();
            },

            error: function (xhr, status, error) {
                showError(
                    'Unable to load the frequency list.' +
                    (error ? ' ' + error : '')
                );
            }
        });
    }

    function showError(message) {
        $('#frequencyLoading').hide();
        $('#frequencyGrid').hide();
        $('#frequencyFooter').hide();

        $('#frequencyError')
            .text(message)
            .show();
    }

    $('#frequencyFilters').on('submit', function (event) {
        event.preventDefault();

        const min = parseInt($('#frequencyMin').val(), 10);
        const maxRaw = $('#frequencyMax').val();
        const max = maxRaw === '' ? null : parseInt(maxRaw, 10);

        if (max !== null && !Number.isNaN(min) && max < min) {
            $('#frequencyError')
                .text('Maximum frequency must be greater than or equal to minimum frequency.')
                .show();
            return;
        }

        loadFrequencyPage(1);
    });

    $('#frequencyReset').on('click', function () {
        $('#frequencyType').val('lemma');
        $('#frequencyPos').val('');
        $('#frequencyMin').val('1');
        $('#frequencyMax').val('');
        $('#frequencyOrder').val('desc');
        $('#frequencyLimit').val('50');

        loadFrequencyPage(1);
    });

    $('#frequencyType').on('change', function () {
        loadFrequencyPage(1);
    });

    $('#frequencyLimit').on('change', function () {
        loadFrequencyPage(1);
    });

    $('#frequencyOrder').on('change', function () {
        loadFrequencyPage(1);
    });

    $('#frequencyFirst').on('click', function () {
        if (currentPage > 1) {
            loadFrequencyPage(1);
        }
    });

    $('#frequencyPrevious').on('click', function () {
        if (currentPage > 1) {
            loadFrequencyPage(currentPage - 1);
        }
    });

    $('#frequencyNext').on('click', function () {
        const totalPages = Math.max(
            1,
            Math.ceil(totalResults / currentLimit)
        );

        if (currentPage < totalPages) {
            loadFrequencyPage(currentPage + 1);
        }
    });

    $('#frequencyLast').on('click', function () {
        const totalPages = Math.max(
            1,
            Math.ceil(totalResults / currentLimit)
        );

        if (currentPage < totalPages) {
            loadFrequencyPage(totalPages);
        }
    });

    /*
     * Prevent the placeholder lemma links jumping to the top of the page.
     * This handler can be replaced when the existing Meanma search route
     * is wired in.
     */
    $(document).on('click', '.frequency-lemma-link', function (event) {
        if ($(this).attr('href') === '#') {
            event.preventDefault();
        }
    });

    loadFrequencyPage(1);
});
</script>
