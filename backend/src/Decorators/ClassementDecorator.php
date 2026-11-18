<?php

declare(strict_types=1);

namespace App\Decorators;

use App\Database\DatabaseConnection;
use App\Services\ExecutionLog;
use PDO;
use RuntimeException;

/**
 * ClassementDecorator (Décorateur Métier n°2)
 *
 * Étend AbstractStudentReportDecorator.
 * Compare la moyenne de l'étudiant avec celle de l'ensemble
 * de sa promotion (requête agrégée en BDD) pour calculer et
 * injecter son rang dans le tableau de résultats.
 * Ajoute les clés 'rang' et 'taille_promotion'.
 */
class ClassementDecorator extends AbstractStudentReportDecorator
{
    private PDO $connection;

    public function __construct(StudentReportInterface $wrappee, ?PDO $connection = null)
    {
        parent::__construct($wrappee);
        $this->connection = $connection ?? DatabaseConnection::getInstance()->getConnection();
    }

    public function getReport(int $studentId): array
    {
        $report = $this->wrappee->getReport($studentId);
        $query = $this->connection->prepare(
            'SELECT s.id AS student_id,
                    SUM(g.note * c.coefficient) / SUM(c.coefficient) AS moyenne
             FROM students s
             INNER JOIN grades g ON g.student_id = s.id
             INNER JOIN courses c ON c.id = g.course_id
             WHERE s.promotion_id = (
                 SELECT promotion_id FROM students WHERE id = :student_id
             )
             GROUP BY s.id
             ORDER BY moyenne DESC'
        );
        $query->execute(['student_id' => $studentId]);
        $averages = $query->fetchAll();

        $targetAverage = null;
        foreach ($averages as $row) {
            if ((int) $row['student_id'] === $studentId) {
                $targetAverage = (float) $row['moyenne'];
                break;
            }
        }

        if ($targetAverage === null) {
            throw new RuntimeException(sprintf('Student %d has no grades to rank.', $studentId));
        }

        $report['rang'] = 1;
        foreach ($averages as $row) {
            if ((float) $row['moyenne'] > $targetAverage) {
                $report['rang']++;
            }
        }
        $report['taille_promotion'] = count($averages);

        $report = ExecutionLog::append(
            $report,
            'info',
            sprintf(
                'ClassementDecorator → comparaison des moyennes de %d étudiants dans la promotion.',
                count($averages)
            )
        );

        return ExecutionLog::append(
            $report,
            'info',
            sprintf('ClassementDecorator → rang calculé : %d/%d.', $report['rang'], $report['taille_promotion'])
        );
    }
}
