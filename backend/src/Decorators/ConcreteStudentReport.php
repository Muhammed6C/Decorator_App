<?php

declare(strict_types=1);

namespace App\Decorators;

use App\Database\DatabaseConnection;
use PDO;
use RuntimeException;

/**
 * ConcreteStudentReport (Composant Concret)
 *
 * Implémentation de base de StudentReportInterface.
 * Responsable uniquement de la récupération des notes brutes
 * depuis la base de données via PDO, avec une latence simulée.
 * N'applique aucune transformation ni enrichissement des données.
 */
class ConcreteStudentReport implements StudentReportInterface
{
    private PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? DatabaseConnection::getInstance()->getConnection();
    }

    public function getReport(int $studentId): array
    {
        $studentQuery = $this->connection->prepare(
            'SELECT s.id, s.nom, s.prenom, p.nom AS promotion, p.annee
             FROM students s
             INNER JOIN promotions p ON p.id = s.promotion_id
             WHERE s.id = :student_id'
        );
        $studentQuery->execute(['student_id' => $studentId]);
        $student = $studentQuery->fetch();

        if ($student === false) {
            throw new RuntimeException(sprintf('Student %d was not found.', $studentId));
        }

        $gradeQuery = $this->connection->prepare(
            'SELECT c.intitule AS matiere, g.note, c.coefficient
             FROM grades g
             INNER JOIN courses c ON c.id = g.course_id
             WHERE g.student_id = :student_id
             ORDER BY c.id'
        );
        $gradeQuery->execute(['student_id' => $studentId]);

        $grades = array_map(
            static fn (array $grade): array => [
                'matiere' => $grade['matiere'],
                'note' => (float) $grade['note'],
                'coefficient' => (float) $grade['coefficient'],
            ],
            $gradeQuery->fetchAll()
        );

        return [
            'student' => [
                'id' => (int) $student['id'],
                'nom' => $student['nom'],
                'prenom' => $student['prenom'],
                'promotion' => sprintf('%s — %d', $student['promotion'], $student['annee']),
            ],
            'grades' => $grades,
        ];
    }
}
