<?php

declare(strict_types=1);

namespace App\Decorators;

use App\Services\ExecutionLog;

/**
 * MentionDecorator (Décorateur Métier n°1)
 *
 * Étend AbstractStudentReportDecorator.
 * Après avoir récupéré les données du composant décoré, calcule
 * la moyenne générale à partir des notes brutes et détermine
 * la mention correspondante (Très Bien, Bien, Assez Bien, Passable).
 * Ajoute les clés 'moyenne' et 'mention' au tableau de résultats.
 */
class MentionDecorator extends AbstractStudentReportDecorator
{
    public function getReport(int $studentId): array
    {
        $report = $this->wrappee->getReport($studentId);
        $grades = $report['grades'];
        $totalCoefficient = array_sum(array_column($grades, 'coefficient'));

        if ($totalCoefficient <= 0) {
            throw new \RuntimeException(sprintf('Student %d has no graded courses.', $studentId));
        }

        $weightedTotal = array_sum(array_map(
            static fn (array $grade): float => $grade['note'] * $grade['coefficient'],
            $grades
        ));
        $average = round($weightedTotal / $totalCoefficient, 2);

        $mention = match (true) {
            $average >= 16 => 'Très Bien',
            $average >= 14 => 'Bien',
            $average >= 12 => 'Assez Bien',
            $average >= 10 => 'Passable',
            default => 'Insuffisant',
        };
        $report['moyenne'] = $average;
        $report['mention'] = $mention;

        $report = ExecutionLog::append(
            $report,
            'info',
            sprintf('MentionDecorator → moyenne pondérée calculée sur %d notes.', count($grades))
        );

        return ExecutionLog::append(
            $report,
            'info',
            sprintf('MentionDecorator → moyenne = %.2f/20, mention « %s ».', $average, $mention)
        );
    }
}
