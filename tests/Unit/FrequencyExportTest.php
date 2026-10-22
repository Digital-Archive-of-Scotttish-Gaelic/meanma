<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainException;
use models\frequencyexport;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FrequencyExportTest extends TestCase
{
    private array $paths = [];

    protected function tearDown(): void
    {
        putenv('MEANMA_EXPORT_TMP_DIR');
        foreach (array_reverse($this->paths) as $path) {
            if (is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                rmdir($path);
            }
        }
    }

    public function testLemmaExportPreservesFiltersAndWritesEveryBatch(): void
    {
        $requests = [];
        $total = 1203;
        $export = new frequencyexport(function (array $params) use (&$requests, $total): array {
            $requests[] = $params;
            $rows = [];
            $end = min($total, $params['start'] + $params['limit'] - 1);
            for ($rank = $params['start']; $rank <= $end; $rank++) {
                $rows[] = [
                    'term' => $rank === 1 ? '=HYPERLINK("https://invalid.test")' : ($rank === 2 ? 'Gàidhlig' : 'lemma-' . $rank),
                    'frequency' => 5000 - $rank,
                    'pos_counts' => ['n' => 5000 - $rank],
                ];
            }
            return [
                'error' => null, 'type' => 'lemma', 'total' => $total,
                'start' => $params['start'], 'limit' => $params['limit'],
                'order' => $params['order'], 'generated' => '2026-10-22T09:00:00+00:00',
                'rows' => $rows,
            ];
        });

        $path = $this->temporaryFile();
        $result = $export->write([
            'type' => 'lemma', 'pos' => 'n', 'min' => '2', 'max' => '5000',
            'order' => 'asc', 'start' => '999', 'limit' => '1',
        ], $path);

        self::assertSame(1203, $result['rows']);
        self::assertSame(3, $result['batches']);
        self::assertMatchesRegularExpression('/^meanma-lemma-frequency-\d{4}-\d{2}-\d{2}\.xlsx$/', $result['filename']);
        self::assertSame([1, 501, 1001], array_column($requests, 'start'));
        self::assertSame([500, 500, 500], array_column($requests, 'limit'));
        foreach ($requests as $request) {
            self::assertSame('lemma', $request['type']);
            self::assertSame('n', $request['pos']);
            self::assertSame(2, $request['min']);
            self::assertSame(5000, $request['max']);
            self::assertSame('asc', $request['order']);
        }

        $workbook = IOFactory::load($path);
        $sheet = $workbook->getSheetByName('Frequency list');
        self::assertNotNull($sheet);
        self::assertSame(1204, $sheet->getHighestDataRow());
        self::assertSame(['Rank', 'Lemma', 'Frequency', 'Part-of-speech counts'], $sheet->rangeToArray('A1:D1')[0]);
        self::assertSame('=HYPERLINK("https://invalid.test")', (string) $sheet->getCell('B2')->getValue());
        self::assertSame(DataType::TYPE_INLINE, $sheet->getCell('B2')->getDataType());
        self::assertSame('Gàidhlig', (string) $sheet->getCell('B3')->getValue());
        self::assertSame(4999, $sheet->getCell('C2')->getValue());
        self::assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('C2')->getDataType());
        self::assertSame('n: 4999', (string) $sheet->getCell('D2')->getValue());
        self::assertSame('lemma-1203', (string) $sheet->getCell('B1204')->getValue());

        $details = $workbook->getSheetByName('Export details');
        self::assertNotNull($details);
        self::assertSame('n', $details->getCell('B3')->getValue());
        self::assertSame(2, $details->getCell('B4')->getValue());
        self::assertSame(5000, $details->getCell('B5')->getValue());
        self::assertSame('Least frequent first', $details->getCell('B6')->getValue());
        $workbook->disconnectWorksheets();
    }

    public function testWordFormAndEmptyExportsRemainValidWorkbooks(): void
    {
        $path = $this->temporaryFile();
        $export = new frequencyexport(static fn(array $params): array => [
            'error' => null, 'type' => 'word-form', 'total' => 0,
            'start' => 1, 'limit' => 500, 'order' => 'desc', 'rows' => [],
        ]);
        $result = $export->write(['type' => 'word-form'], $path);
        self::assertSame(0, $result['rows']);
        self::assertSame(0, $result['batches']);
        self::assertMatchesRegularExpression('/^meanma-word-form-frequency-\d{4}-\d{2}-\d{2}\.xlsx$/', $result['filename']);

        $workbook = IOFactory::load($path);
        $sheet = $workbook->getSheetByName('Frequency list');
        self::assertSame(1, $sheet->getHighestDataRow());
        self::assertSame('Word form', (string) $sheet->getCell('B1')->getValue());
        $workbook->disconnectWorksheets();
    }

    /** @dataProvider invalidParameterProvider */
    public function testInvalidParametersAreRejected(array $params): void
    {
        $this->expectException(DomainException::class);
        frequencyexport::normalizeParameters($params);
    }

    public function invalidParameterProvider(): array
    {
        return [
            'type' => [['type' => 'anything']],
            'array type' => [['type' => ['lemma']]],
            'order' => [['order' => 'random']],
            'pos' => [['pos' => "n\r\nInjected"]],
            'zero minimum' => [['min' => '0']],
            'non-integer maximum' => [['max' => '1.5']],
            'reversed range' => [['min' => '10', 'max' => '2']],
        ];
    }

    public function testExcelRowLimitIsRejectedWithoutSilentTruncation(): void
    {
        $export = new frequencyexport(static fn(array $params): array => [
            'error' => null, 'total' => frequencyexport::MAX_DATA_ROWS + 1,
            'start' => 1, 'rows' => [],
        ]);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Excel worksheet row limit');
        $export->write([], $this->temporaryFile());
    }

    public function testIncompleteApiResponseFailsAndRemovesWorksheetTemporaryFile(): void
    {
        $directory = sys_get_temp_dir() . '/meanma-frequency-export-test-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $this->paths[] = $directory;
        putenv('MEANMA_EXPORT_TMP_DIR=' . $directory);
        $calls = 0;
        $export = new frequencyexport(static function (array $params) use (&$calls): array {
            $calls++;
            return [
                'error' => null, 'total' => 2, 'start' => $params['start'],
                'rows' => $calls === 1 ? [['term' => 'a', 'frequency' => 1]] : [],
            ];
        });
        $output = $this->temporaryFile();

        try {
            $export->write([], $output);
            self::fail('Incomplete API response was accepted.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('ended before all', $exception->getMessage());
        }
        self::assertSame([], array_values(array_diff(scandir($directory), ['.', '..'])));
    }

    private function temporaryFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'meanma-frequency-result-');
        self::assertNotFalse($path);
        $this->paths[] = $path;
        return $path;
    }
}
