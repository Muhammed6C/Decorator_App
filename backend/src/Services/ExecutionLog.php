<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use UnexpectedValueException;

final class ExecutionLog
{
    public static function createEntry(string $level, string $message): array
    {
        return [
            'timestamp' => date('H:i:s'),
            'level' => $level,
            'message' => $message,
        ];
    }

    public static function append(array $report, string $level, string $message): array
    {
        $logs = $report['logs'] ?? [];
        if (!is_array($logs)) {
            throw new UnexpectedValueException('Report logs must be an array.');
        }

        $logs[] = self::createEntry($level, $message);
        $report['logs'] = $logs;

        return $report;
    }

    public static function writeToFile(int $studentId, array $entries): void
    {
        $logDirectory = __DIR__ . '/../../logs';
        if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
            throw new RuntimeException(sprintf('Unable to create log directory: %s', $logDirectory));
        }

        $lines = [];
        foreach ($entries as $entry) {
            if (
                !is_array($entry)
                || !isset($entry['timestamp'], $entry['level'], $entry['message'])
                || !is_string($entry['timestamp'])
                || !is_string($entry['level'])
                || !is_string($entry['message'])
            ) {
                throw new UnexpectedValueException('A report log entry has an invalid shape.');
            }

            $lines[] = json_encode(
                [
                    'timestamp' => $entry['timestamp'],
                    'student_id' => $studentId,
                    'level' => $entry['level'],
                    'message' => $entry['message'],
                ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        }

        if ($lines === []) {
            return;
        }

        $content = implode(PHP_EOL, $lines) . PHP_EOL;
        if (file_put_contents($logDirectory . '/app.log', $content, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the report execution logs.');
        }
    }
}
