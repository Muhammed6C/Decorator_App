<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\DatabaseConnection;
use PDO;

class StudentService
{
    private PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? DatabaseConnection::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        $query = $this->connection->query(
            'SELECT s.id, s.nom, s.prenom, p.nom AS promotion, p.annee
             FROM students s
             INNER JOIN promotions p ON p.id = s.promotion_id
             ORDER BY s.id'
        );

        return array_map(
            static fn (array $student): array => [
                'id' => (int) $student['id'],
                'nom' => $student['nom'],
                'prenom' => $student['prenom'],
                'promotion' => sprintf('%s — %d', $student['promotion'], $student['annee']),
            ],
            $query->fetchAll()
        );
    }
}
