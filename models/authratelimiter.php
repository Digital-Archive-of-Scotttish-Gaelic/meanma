<?php

namespace models;

class authratelimiter
{
    public static function consume(string $bucket, string $key, int $limit, int $windowSeconds): bool
    {
        $directory = (string) getenv('MEANMA_RATE_LIMIT_DIR');
        if ($directory === '') {
            $directory = sys_get_temp_dir() . '/meanma-auth-rate-limits';
        }

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            error_log('MEANMA authentication rate limiter directory is unavailable');
            return false;
        }

        $filename = $directory . '/' . hash('sha256', $bucket . "\0" . $key) . '.json';
        $handle = @fopen($filename, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            error_log('MEANMA authentication rate limiter storage is unavailable');
            return false;
        }

        $now = time();
        $contents = stream_get_contents($handle);
        $state = json_decode($contents ?: '', true);
        if (!is_array($state) || ($state['started'] ?? 0) <= $now - $windowSeconds) {
            $state = ['started' => $now, 'count' => 0];
        }

        $allowed = (int) $state['count'] < $limit;
        if ($allowed) {
            $state['count']++;
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($state));
            fflush($handle);
            @chmod($filename, 0600);
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        return $allowed;
    }
}
