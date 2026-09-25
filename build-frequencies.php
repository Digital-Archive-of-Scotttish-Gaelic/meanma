<?php

declare(strict_types=1);

$apiBase   = 'http://localhost:8080/exist/restxq';
$outputFile = __DIR__ . '/frequencies.xml';

$started = microtime(true);

echo "MEANMA frequency builder\n";
echo "========================\n\n";

echo "Fetching text list...\n";

$listXml = @file_get_contents($apiBase . '/text');

if ($listXml === false) {
    throw new RuntimeException('Unable to retrieve /text from Meanma API.');
}

$list = simplexml_load_string($listXml);

if ($list === false) {
    throw new RuntimeException('Unable to parse /text response.');
}

$textIds = [];

foreach ($list->text as $text) {
    $id = (string)$text['id'];

    if ($id !== '') {
        $textIds[] = $id;
    }
}

$totalTexts = count($textIds);

echo number_format($totalTexts) . " texts found.\n\n";

$frequencies     = [];
$wordFrequencies = [];

$totalWords       = 0;
$wordsWithLemma   = 0;
$wordsWithForm    = 0;
$processedTexts   = 0;
$failedTexts      = [];

foreach ($textIds as $index => $textId) {

    $number = $index + 1;

    echo sprintf(
        "[%d/%d] %-15s ",
        $number,
        $totalTexts,
        $textId
    );

    $url = $apiBase . '/text/' . rawurlencode($textId);

    $xml = @file_get_contents($url);

    if ($xml === false) {
        echo "FAILED\n";
        $failedTexts[] = $textId;
        continue;
    }

    $reader = new XMLReader();

    if (!$reader->XML($xml)) {
        echo "INVALID XML\n";
        $failedTexts[] = $textId;
        continue;
    }

    $textWords = 0;
    $textLemmaWords = 0;

    while ($reader->read()) {

        if (
            $reader->nodeType !== XMLReader::ELEMENT ||
            $reader->localName !== 'w'
        ) {
            continue;
        }

        $totalWords++;
        $textWords++;

        // Capture attributes before reading the element text.
        $lemma = $reader->getAttribute('lemma');
        $pos   = $reader->getAttribute('pos') ?? '';

        // Surface word form, normalised case-insensitively using UTF-8.
        $wordForm = trim($reader->readString());

        if ($wordForm !== '') {
            $wordForm = mb_strtolower($wordForm, 'UTF-8');
            $wordsWithForm++;

            if (!isset($wordFrequencies[$wordForm])) {
                $wordFrequencies[$wordForm] = [
                    'total' => 0,
                    'pos'   => [],
                ];
            }

            $wordFrequencies[$wordForm]['total']++;

            if (!isset($wordFrequencies[$wordForm]['pos'][$pos])) {
                $wordFrequencies[$wordForm]['pos'][$pos] = 0;
            }

            $wordFrequencies[$wordForm]['pos'][$pos]++;
        }

        if ($lemma === null || $lemma === '') {
            continue;
        }

        $wordsWithLemma++;
        $textLemmaWords++;

        if (!isset($frequencies[$lemma])) {
            $frequencies[$lemma] = [
                'total' => 0,
                'pos'   => [],
            ];
        }

        $frequencies[$lemma]['total']++;

        if (!isset($frequencies[$lemma]['pos'][$pos])) {
            $frequencies[$lemma]['pos'][$pos] = 0;
        }

        $frequencies[$lemma]['pos'][$pos]++;
    }

    $reader->close();

    $processedTexts++;

    echo number_format($textWords) . " words";

    if ($textLemmaWords !== $textWords) {
        echo " (" . number_format($textLemmaWords) . " with lemma)";
    }

    echo "\n";
}

echo "\nSorting frequencies...\n";

uksort(
    $frequencies,
    static fn($a, $b): int =>
        strnatcasecmp((string)$a, (string)$b)
);

uksort(
    $wordFrequencies,
    static fn($a, $b): int =>
        strnatcasecmp((string)$a, (string)$b)
);

echo "Writing frequencies.xml...\n";

$writer = new XMLWriter();
$writer->openMemory();
$writer->setIndent(true);
$writer->setIndentString('  ');

$writer->startDocument('1.0', 'UTF-8');

$writer->startElement('frequencies');

$writer->writeAttribute(
    'generated',
    (new DateTimeImmutable())->format(DateTimeInterface::ATOM)
);

$writer->writeAttribute(
    'texts',
    (string)$processedTexts
);

$writer->writeAttribute(
    'words',
    (string)$totalWords
);

$writer->writeAttribute(
    'words-with-lemma',
    (string)$wordsWithLemma
);

$writer->writeAttribute(
    'lemmas',
    (string)count($frequencies)
);

$writer->writeAttribute(
    'words-with-form',
    (string)$wordsWithForm
);

$writer->writeAttribute(
    'word-forms',
    (string)count($wordFrequencies)
);

foreach ($frequencies as $lemma => $data) {

    $writer->startElement('lemma');

    $writer->writeAttribute('value', (string)$lemma);
    $writer->writeAttribute('total', (string)$data['total']);

    $posCounts = $data['pos'];

    uksort(
        $posCounts,
        static fn(string $a, string $b): int =>
        strnatcasecmp($a, $b)
    );

    foreach ($posCounts as $pos => $count) {

        $writer->startElement('pos');

        $writer->writeAttribute('value', (string)$pos);
        $writer->writeAttribute('count', (string)$count);

        $writer->endElement();
    }

    $writer->endElement();
}

$writer->startElement('word-forms');

foreach ($wordFrequencies as $wordForm => $data) {

    $writer->startElement('word-form');

    $writer->writeAttribute('value', (string)$wordForm);
    $writer->writeAttribute('total', (string)$data['total']);

    $posCounts = $data['pos'];

    uksort(
        $posCounts,
        static fn($a, $b): int =>
            strnatcasecmp((string)$a, (string)$b)
    );

    foreach ($posCounts as $pos => $count) {

        $writer->startElement('pos');

        $writer->writeAttribute('value', (string)$pos);
        $writer->writeAttribute('count', (string)$count);

        $writer->endElement();
    }

    $writer->endElement();
}

$writer->endElement(); // word-forms

$writer->endElement(); // frequencies
$writer->endDocument();

$outputXml = $writer->outputMemory();

$tmpFile = $outputFile . '.tmp';

if (file_put_contents($tmpFile, $outputXml) === false) {
    throw new RuntimeException(
        "Unable to write temporary output: {$tmpFile}"
    );
}

if (!rename($tmpFile, $outputFile)) {
    throw new RuntimeException(
        "Unable to move completed frequency file to {$outputFile}"
    );
}

$elapsed = microtime(true) - $started;

echo "\nComplete\n";
echo "========\n";
echo "Texts processed:    " . number_format($processedTexts) . "\n";
echo "Texts failed:       " . number_format(count($failedTexts)) . "\n";
echo "Words:              " . number_format($totalWords) . "\n";
echo "Words with lemma:   " . number_format($wordsWithLemma) . "\n";
echo "Distinct lemmas:    " . number_format(count($frequencies)) . "\n";
echo "Words with form:    " . number_format($wordsWithForm) . "\n";
echo "Distinct word forms:" . number_format(count($wordFrequencies)) . "\n";
echo "Output:             {$outputFile}\n";
echo "Elapsed:            " . number_format($elapsed, 1) . " seconds\n";

if ($failedTexts !== []) {
    echo "\nFailed text IDs:\n";

    foreach ($failedTexts as $textId) {
        echo "  - {$textId}\n";
    }
}