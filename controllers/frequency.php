<?php

namespace controllers;
use DomainException;
use models;
use views;

class frequency
{

    public function run($action = '') {
        if ($action === 'export') {
            $this->export();
            return;
        }
        require_once 'views/frequency.php';
    }

    private function export(): void
    {
        models\security::requireMethod('GET');
        ob_start();
        $outputPath = null;
        try {
            $outputPath = tempnam(models\frequencyexport::temporaryDirectory(), 'meanma-frequency-');
            if ($outputPath === false) {
                throw new \RuntimeException('Unable to create the export file.');
            }
            $export = new models\frequencyexport();
            $result = $export->write($_GET, $outputPath);
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Content-Length: ' . filesize($outputPath));
            header('Cache-Control: private, no-store, max-age=0');
            header('X-Content-Type-Options: nosniff');
            readfile($outputPath);
        } catch (DomainException $exception) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo $exception->getMessage();
        } catch (\Throwable $exception) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            error_log('Frequency export failed: ' . $exception->getMessage());
            http_response_code(502);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'The frequency export could not be generated.';
        } finally {
            if (is_string($outputPath) && is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }

}
