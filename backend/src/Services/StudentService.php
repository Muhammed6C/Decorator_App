<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\DatabaseConnection;
use PDO;

/**
 * Classe StudentService
 *
 * Role : Service metier pour l'acces aux donnees des etudiants.
 *
 * Ce service offre des methodes utilitaires simples utilisees notamment par
 * l'interface utilisateur frontend pour alimenter la liste deroulante
 * des etudiants disponibles sans declencher la chaine lourde des decorateurs.
 */
final class StudentService
{
    /**
     * Instance de connexion a la base de donnees.
     */
    private PDO $pdo;

    /**
     * Constructeur du service des etudiants.
     *
     * @param PDO|null $pdo Connexion PDO facultative (recupere le Singleton DatabaseConnection par defaut)
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DatabaseConnection::getInstance();
    }

    /**
     * Retourne la liste complete des etudiants accompagnes du nom de leur promotion.
     *
     * @return list<array{id: int, name: string, promotion: string}> Liste ordonnee des etudiants
     */
    public function listStudents(): array
    {
        $requete = $this->pdo->query(
            'SELECT s.id, s.name, p.name AS promotion
             FROM students s
             JOIN promotions p ON p.id = s.promotion_id
             ORDER BY s.id'
        );

        $lignes = $requete->fetchAll();

        return array_map(
            static fn (array $ligne): array => [
                'id' => (int) $ligne['id'],
                'name' => (string) $ligne['name'],
                'promotion' => (string) $ligne['promotion'],
            ],
            $lignes
        );
    }
}
