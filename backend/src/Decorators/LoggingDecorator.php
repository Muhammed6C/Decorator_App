<?php

declare(strict_types=1);

namespace App\Decorators;

use App\Services\ExecutionLog;

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
        $logs = [
            ExecutionLog::createEntry(
                'info',
                sprintf('LoggingDecorator → interception de la requête pour l’étudiant #%d.', $studentId)
            ),
        ];
        $report = $this->wrappee->getReport($studentId);
        $executionTimeMs = (microtime(true) - $startedAt) * 1000;
        $logs = array_merge($logs, $report['logs'] ?? []);
        $logs[] = ExecutionLog::createEntry(
            'timing',
            sprintf('LoggingDecorator → temps total du traitement : %.2f ms.', $executionTimeMs)
        );

        $report['execution_time_ms'] = $executionTimeMs;
        $report['logs'] = $logs;
        ExecutionLog::writeToFile($studentId, $logs);

        return $report;
    }
}
