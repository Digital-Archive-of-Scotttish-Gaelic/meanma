<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

[$script, $path, $type, $expectedRows] = $argv + [null, null, null, null];
if (!$path || !$type || $expectedRows === null) {
    fwrite(STDERR, "Usage: inspect_frequency_export.php FILE TYPE ROWS\n");
    exit(2);
}

$workbook = IOFactory::load($path);
$sheet = $workbook->getSheetByName('Frequency list');
$expectedLabel = $type === 'word-form' ? 'Word form' : 'Lemma';
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$check($sheet !== null, 'Frequency worksheet is missing');
if ($sheet !== null) {
    $check((string) $sheet->getCell('B1')->getValue() === $expectedLabel, 'Frequency type header is incorrect');
    $check($sheet->getHighestDataRow() === ((int) $expectedRows + 1), 'Export row count is incorrect');
    if ((int) $expectedRows > 0) {
        $check((string) $sheet->getCell('B2')->getValue() === '=2+2', 'Formula-like text changed');
        $check($sheet->getCell('B2')->getDataType() === DataType::TYPE_INLINE, 'Formula-like text is not an inline string');
        $check($sheet->getCell('C2')->getDataType() === DataType::TYPE_NUMERIC, 'Frequency is not numeric');
        $check((string) $sheet->getCell('D2')->getValue() === 'n: 1', 'Part-of-speech count is missing');
    }
    if ((int) $expectedRows > 1) {
        $check((string) $sheet->getCell('B3')->getValue() === 'Gàidhlig', 'Gaelic Unicode text changed');
    }
}
$workbook->disconnectWorksheets();

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
