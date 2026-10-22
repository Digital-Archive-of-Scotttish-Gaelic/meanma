<?php

declare(strict_types=1);

$type = str_ends_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '', '/word-form')
    ? 'word-form'
    : 'lemma';
$start = max(1, (int) ($_GET['start'] ?? 1));
$limit = max(1, min(500, (int) ($_GET['limit'] ?? 50)));
$order = ($_GET['order'] ?? '') === 'asc' ? 'asc' : 'desc';
$total = ((string) ($_GET['min'] ?? '') === '999999') ? 0 : ($type === 'lemma' ? 1003 : 3);

$log = getenv('MEANMA_FREQUENCY_FIXTURE_LOG');
if ($log) {
    file_put_contents($log, json_encode([
        'type' => $type,
        'pos' => (string) ($_GET['pos'] ?? ''),
        'min' => (string) ($_GET['min'] ?? ''),
        'max' => (string) ($_GET['max'] ?? ''),
        'order' => $order,
        'start' => $start,
        'limit' => $limit,
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<frequency total="', $total, '" start="', $start, '" limit="', $limit,
    '" order="', $order, '" min="', htmlspecialchars((string) ($_GET['min'] ?? '1'), ENT_XML1),
    '" max="', htmlspecialchars((string) ($_GET['max'] ?? ''), ENT_XML1),
    '" pos="', htmlspecialchars((string) ($_GET['pos'] ?? ''), ENT_XML1),
    '" generated="2026-10-22T10:00:00+00:00">';

$last = min($total, $start + $limit - 1);
for ($rank = $start; $rank <= $last; $rank++) {
    $term = $rank === 1 ? '=2+2' : ($rank === 2 ? 'Gàidhlig' : $type . '-' . $rank);
    $frequency = $order === 'asc' ? $rank : ($total - $rank + 1);
    $attribute = $type === 'word-form' ? 'word-form' : 'lemma';
    echo '<entry ', $attribute, '="', htmlspecialchars($term, ENT_QUOTES | ENT_XML1, 'UTF-8'),
        '" frequency="', $frequency, '"><pos value="n" count="', $frequency, '"/></entry>';
}
echo '</frequency>';
