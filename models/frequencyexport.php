<?php

declare(strict_types=1);

namespace models;

use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use ZipArchive;

final class frequencyexport
{
    public const BATCH_SIZE = 500;
    public const MAX_DATA_ROWS = 1048575;

    /** @var callable(array):array */
    private $fetchBatch;

    public function __construct(?callable $fetchBatch = null)
    {
        $this->fetchBatch = $fetchBatch ?? static fn(array $params): array =>
            (new frequency())->getResults($params);
    }

    public static function temporaryDirectory(): string
    {
        $directory = getenv('MEANMA_EXPORT_TMP_DIR') ?: sys_get_temp_dir();
        $resolved = realpath($directory);
        if ($resolved === false || !is_dir($resolved) || !is_writable($resolved)) {
            throw new RuntimeException('The configured export temporary directory is unavailable.');
        }
        return $resolved;
    }

    /**
     * @return array{type:string,pos:string,min:int,max:?int,order:string}
     */
    public static function normalizeParameters(array $params): array
    {
        foreach (['type', 'pos', 'min', 'max', 'order'] as $name) {
            if (isset($params[$name]) && !is_scalar($params[$name])) {
                throw new DomainException('Invalid frequency export parameters.');
            }
        }

        $type = (string) ($params['type'] ?? 'lemma');
        if (!in_array($type, ['lemma', 'word-form'], true)) {
            throw new DomainException('Invalid frequency type.');
        }
        $order = (string) ($params['order'] ?? 'desc');
        if (!in_array($order, ['asc', 'desc'], true)) {
            throw new DomainException('Invalid frequency order.');
        }
        $pos = trim((string) ($params['pos'] ?? ''));
        if ($pos !== '' && !preg_match('/^[\p{L}\p{N}_.-]{1,32}$/u', $pos)) {
            throw new DomainException('Invalid part-of-speech filter.');
        }
        $min = self::positiveInteger($params['min'] ?? 1, 'minimum frequency');
        $max = ($params['max'] ?? '') === ''
            ? null
            : self::positiveInteger($params['max'], 'maximum frequency');
        if ($max !== null && $max < $min) {
            throw new DomainException('Maximum frequency must be greater than or equal to minimum frequency.');
        }

        return compact('type', 'pos', 'min', 'max', 'order');
    }

    /**
     * @return array{filename:string,rows:int,type:string,batches:int}
     */
    public function write(array $params, string $outputPath): array
    {
        $filters = self::normalizeParameters($params);
        $first = $this->fetch($filters, 1);
        $total = $this->validateBatch($first, 1, null);
        if ($total > self::MAX_DATA_ROWS) {
            throw new DomainException('The filtered result exceeds the Excel worksheet row limit. Narrow the filters and try again.');
        }

        $worksheetPath = tempnam(self::temporaryDirectory(), 'meanma-frequency-sheet-');
        if ($worksheetPath === false) {
            throw new RuntimeException('Unable to create the temporary worksheet.');
        }

        $typeLabel = $filters['type'] === 'word-form' ? 'Word form' : 'Lemma';
        $spreadsheet = null;
        try {
            $spreadsheet = $this->createTemplate($filters, $first, $total, $typeLabel);
            (new Xlsx($spreadsheet))->save($outputPath);
            $spreadsheet->disconnectWorksheets();
            $spreadsheet = null;

            $handle = fopen($worksheetPath, 'wb');
            if ($handle === false) {
                throw new RuntimeException('Unable to open the temporary worksheet.');
            }
            try {
                $this->writeWorksheetStart($handle, $total + 1, $typeLabel);
                $written = 0;
                $batches = 0;
                $batch = $first;
                while (true) {
                    $this->validateBatch($batch, $written + 1, $total);
                    $rows = $batch['rows'];
                    if ($rows === []) {
                        break;
                    }
                    foreach ($rows as $row) {
                        if ($written >= $total) {
                            throw new RuntimeException('The frequency API returned more rows than its declared total.');
                        }
                        $written++;
                        $this->writeDataRow($handle, $written + 1, $written, $row, $filters['pos']);
                    }
                    $batches++;
                    if ($written === $total) {
                        break;
                    }
                    $batch = $this->fetch($filters, $written + 1);
                }
                if ($written !== $total) {
                    throw new RuntimeException('The frequency API ended before all matching results were exported.');
                }
                fwrite($handle, '</sheetData><autoFilter ref="A1:D' . ($total + 1) . '"/></worksheet>');
            } finally {
                fclose($handle);
            }

            $archive = new ZipArchive();
            if ($archive->open($outputPath) !== true) {
                throw new RuntimeException('Unable to assemble the Excel export.');
            }
            $archive->deleteName('xl/worksheets/sheet1.xml');
            if (!$archive->addFile($worksheetPath, 'xl/worksheets/sheet1.xml')) {
                $archive->close();
                throw new RuntimeException('Unable to add the frequency worksheet.');
            }
            if (!$archive->close()) {
                throw new RuntimeException('Unable to finish the Excel export.');
            }

            return [
                'filename' => 'meanma-' . $filters['type'] . '-frequency-' . date('Y-m-d') . '.xlsx',
                'rows' => $written,
                'type' => $filters['type'],
                'batches' => $batches,
            ];
        } finally {
            if ($spreadsheet instanceof Spreadsheet) {
                $spreadsheet->disconnectWorksheets();
            }
            if (is_file($worksheetPath)) {
                unlink($worksheetPath);
            }
        }
    }

    private static function positiveInteger(mixed $value, string $label): int
    {
        $string = (string) $value;
        if (!preg_match('/^[1-9][0-9]*$/', $string)) {
            throw new DomainException('Invalid ' . $label . '.');
        }
        return (int) $string;
    }

    private function fetch(array $filters, int $start): array
    {
        $request = $filters;
        $request['start'] = $start;
        $request['limit'] = self::BATCH_SIZE;
        $request['include_pos_counts'] = true;
        return ($this->fetchBatch)($request);
    }

    private function validateBatch(array $batch, int $expectedStart, ?int $expectedTotal): int
    {
        if (!empty($batch['error'])) {
            throw new RuntimeException('Frequency API error: ' . (string) $batch['error']);
        }
        if (!isset($batch['rows']) || !is_array($batch['rows']) || !isset($batch['total'])) {
            throw new RuntimeException('The frequency API returned an invalid response.');
        }
        $total = (int) $batch['total'];
        if ($total < 0 || ($expectedTotal !== null && $total !== $expectedTotal)) {
            throw new RuntimeException('The frequency API total changed during export.');
        }
        if (isset($batch['start']) && (int) $batch['start'] !== $expectedStart) {
            throw new RuntimeException('The frequency API returned an unexpected page.');
        }
        return $total;
    }

    private function createTemplate(array $filters, array $first, int $total, string $typeLabel): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('MEANMA')
            ->setTitle($typeLabel . ' frequency export')
            ->setSubject('Filtered frequency list');
        $data = $spreadsheet->getActiveSheet();
        $data->setTitle('Frequency list');
        $data->fromArray(['Rank', $typeLabel, 'Frequency', 'Part-of-speech counts'], null, 'A1');
        $data->getStyle('A1:D1')->getFont()->setBold(true);

        $metadata = $spreadsheet->createSheet();
        $metadata->setTitle('Export details');
        $metadata->fromArray(['Field', 'Value'], null, 'A1');
        $metadata->getStyle('A1:B1')->getFont()->setBold(true);
        $details = [
            ['Frequency type', $typeLabel],
            ['Part-of-speech filter', $filters['pos'] === '' ? 'All' : $filters['pos']],
            ['Minimum frequency', $filters['min']],
            ['Maximum frequency', $filters['max'] ?? 'None'],
            ['Sort order', $filters['order'] === 'asc' ? 'Least frequent first' : 'Most frequent first'],
            ['Matching rows', $total],
            ['Frequency data generated', (string) ($first['generated'] ?? '')],
            ['Exported', date(DATE_ATOM)],
        ];
        foreach ($details as $index => [$label, $value]) {
            $row = $index + 2;
            $metadata->setCellValueExplicit('A' . $row, (string) $label, DataType::TYPE_STRING);
            if (is_int($value)) {
                $metadata->setCellValue('B' . $row, $value);
            } else {
                $metadata->setCellValueExplicit('B' . $row, (string) $value, DataType::TYPE_STRING);
            }
        }
        $metadata->getColumnDimension('A')->setWidth(28);
        $metadata->getColumnDimension('B')->setWidth(38);
        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    /** @param resource $handle */
    private function writeWorksheetStart($handle, int $lastRow, string $typeLabel): void
    {
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($handle, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">');
        fwrite($handle, '<dimension ref="A1:D' . $lastRow . '"/>');
        fwrite($handle, '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');
        fwrite($handle, '<sheetFormatPr defaultRowHeight="15"/><cols><col min="1" max="1" width="12" customWidth="1"/><col min="2" max="2" width="36" customWidth="1"/><col min="3" max="3" width="14" customWidth="1"/><col min="4" max="4" width="32" customWidth="1"/></cols><sheetData>');
        fwrite($handle, '<row r="1">' . $this->stringCell('A1', 'Rank', 1) . $this->stringCell('B1', $typeLabel, 1) . $this->stringCell('C1', 'Frequency', 1) . $this->stringCell('D1', 'Part-of-speech counts', 1) . '</row>');
    }

    /** @param resource $handle */
    private function writeDataRow($handle, int $sheetRow, int $rank, array $row, string $posFilter): void
    {
        if (!array_key_exists('term', $row) || !is_scalar($row['term']) || !isset($row['frequency']) || !is_numeric($row['frequency'])) {
            throw new RuntimeException('The frequency API returned an invalid row.');
        }
        $frequency = (int) $row['frequency'];
        if ($frequency < 0) {
            throw new RuntimeException('The frequency API returned a negative frequency.');
        }
        $posCounts = is_array($row['pos_counts'] ?? null) ? $row['pos_counts'] : [];
        if ($posCounts === [] && $posFilter !== '') {
            $posCounts[$posFilter] = $frequency;
        }
        $parts = [];
        foreach ($posCounts as $pos => $count) {
            if (is_scalar($pos) && is_numeric($count)) {
                $parts[] = ((string) $pos === '' ? '(unmarked)' : (string) $pos) . ': ' . (int) $count;
            }
        }

        fwrite($handle, '<row r="' . $sheetRow . '"><c r="A' . $sheetRow . '"><v>' . $rank . '</v></c>'
            . $this->stringCell('B' . $sheetRow, (string) $row['term'])
            . '<c r="C' . $sheetRow . '"><v>' . $frequency . '</v></c>'
            . $this->stringCell('D' . $sheetRow, implode('; ', $parts)) . '</row>');
    }

    private function stringCell(string $reference, string $value, ?int $style = null): string
    {
        $space = trim($value) !== $value ? ' xml:space="preserve"' : '';
        $styleAttribute = $style === null ? '' : ' s="' . $style . '"';
        return '<c r="' . $reference . '" t="inlineStr"' . $styleAttribute . '><is><t' . $space . '>'
            . htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8')
            . '</t></is></c>';
    }
}
