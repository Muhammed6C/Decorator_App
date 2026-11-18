<?php

declare(strict_types=1);

namespace App\Decorators;

use PDO;

/**
 * Classe ConcreteStudentReport
 *
 * Role dans le Design Pattern Decorateur : Concrete Component (Composant Concret).
 *
 * Il s'agit de la brique fondamentale et indispensable. Cette classe implemente
 * l'interface StudentReportInterface et represente le comportement par defaut sans aucun enrichissement.
 *
 * Ses responsabilites principales sont :
 * 1. Se connecter a la source de donnees (via l'instance PDO injectee).
 * 2. Extraire les informations signaletiques de l'etudiant (nom, promotion).
 * 3. Recuperer la liste brute des matieres, leurs coefficients et les notes obtenues.
 * 4. Simuler une latence reseau/base si configuree (utile en demonstration pour souligner l'impact du CacheDecorator).
 *
 * Principe du pattern : Ce composant ne sait pas qu'il peut etre decore. Il se concentre
 * uniquement sur son metier d'origine (lire les notes brutes). Les enrichissements
 * (moyenne, mention, rang, cache, logs) sont delegues a des decorateurs autonomes.
 */
final class ConcreteStudentReport implements StudentReportInterface
{
    /**
     * Constructeur du composant de base.
     *
     * @param PDO $pdo Instance de connexion PDO prete a l'emploi
     * @param int $latencyMs Temps de pause simule en millisecondes (pour tester la reactivite avec et sans cache)
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly int $latencyMs = 0
    ) {
    }

    /**
     * Genere le releve brut d'un etudiant a partir de la base de donnees.
     *
     * @param int $studentId Identifiant de l'etudiant a rechercher
     * @return array<string, mixed> Donnees de base contenant l'etudiant et ses notes brutes
     *
     * @throws \OutOfBoundsException Si aucun etudiant ne correspond a l'identifiant fourni
     */
    public function getReport(int $studentId): array
    {
        // Simulation d'une latence d'acces base de donnees si specifiee dans la configuration.
        // Cela met en evidence visuelle le gain de performance procure par le CacheDecorator.
        if ($this->latencyMs > 0) {
            usleep($this->latencyMs * 1000);
        }

        // 1. Recuperation des informations personnelles et de la promotion de l'etudiant
        $requeteEtudiant = $this->pdo->prepare(
            'SELECT s.id, s.name, p.name AS promotion
             FROM students s
             JOIN promotions p ON p.id = s.promotion_id
             WHERE s.id = :id'
        );
        $requeteEtudiant->execute(['id' => $studentId]);
        $etudiant = $requeteEtudiant->fetch();

        // Declenchement d'une exception si l'etudiant est introuvable
        if ($etudiant === false) {
            throw new \OutOfBoundsException("Etudiant introuvable avec l'identifiant : $studentId");
        }

        // 2. Recuperation des cours, coefficients et notes associees a l'etudiant
        $requeteNotes = $this->pdo->prepare(
            'SELECT c.name AS course, c.coefficient, g.value
             FROM grades g
             JOIN courses c ON c.id = g.course_id
             WHERE g.student_id = :id
             ORDER BY c.id'
        );
        $requeteNotes->execute(['id' => $studentId]);
        
        // Typage et formatage strict de chaque ligne de note
        $notes = array_map(
            static fn (array $ligne): array => [
                'course' => (string) $ligne['course'],
                'coefficient' => (int) $ligne['coefficient'],
                'value' => (float) $ligne['value'],
            ],
            $requeteNotes->fetchAll()
        );

        // Retour du releve de base (sans calcul de moyenne, ni rang, ni decorateur externe)
        return [
            'studentId' => (int) $etudiant['id'],
            'student' => [
                'id' => (int) $etudiant['id'],
                'name' => (string) $etudiant['name'],
                'promotion' => (string) $etudiant['promotion'],
            ],
            'grades' => $notes,
        ];
    }
}
