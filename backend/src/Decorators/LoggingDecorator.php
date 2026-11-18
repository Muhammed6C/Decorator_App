<?php

declare(strict_types=1);

namespace App\Decorators;

use RuntimeException;

/**
 * LoggingDecorator (Décorateur Technique n°4)
 *
 * Étend AbstractStudentReportDecorator.
 * Chronomètre l'appel au composant décoré via microtime().
 * Enregistre dans un fichier de log (PSR-3-like) : timestamp,
 * studentId, durée d'exécution, et les décorateurs actifs dans la pile.
 * Injecte les clés 'logs' (tableau de traces) et 'execution_time_ms'
 * dans le tableau de résultats retourné.
 */
class LoggingDecorator extends AbstractStudentReportDecorator
{
    public function getReport(int $studentId): array
    {
        $startedAt = microtime(true);
        $report = $this->wrappee->getReport($studentId);
        $executionTimeMs = (microtime(true) - $startedAt) * 1000;
        $timestamp = date('H:i:s');
        $message = sprintf(
            'Student report #%d generated in %.2f ms',
            $studentId,
            $executionTimeMs
        );

        $logDirectory = __DIR__ . '/../../logs';
        if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
            throw new RuntimeException(sprintf('Unable to create log directory: %s', $logDirectory));
        }

        $logLine = json_encode(
            ['timestamp' => $timestamp, 'student_id' => $studentId, 'message' => $message],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        if (file_put_contents($logDirectory . '/app.log', $logLine . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the report execution log.');
        }

        $report['execution_time_ms'] = $executionTimeMs;
        $report['logs'] = [[
            'timestamp' => $timestamp,
            'level' => 'info',
            'message' => $message,
        ]];

        return $report;
    }
}
