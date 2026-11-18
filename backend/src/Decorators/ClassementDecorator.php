<?php

declare(strict_types=1);

namespace App\Decorators;

use App\Database\DatabaseConnection;
use PDO;

/**
 * Classe ClassementDecorator
 *
 * Role dans le Design Pattern Decorateur : Concrete Decorator (Decorateur Concret Metier).
 *
 * Ce decorateur etend AbstractStudentReportDecorator pour calculer et ajouter au releve :
 * - Le rang (classement) de l'etudiant parmi l'ensemble des etudiants de sa promotion.
 * - L'effectif total de la promotion (classSize).
 *
 * Particularite technique essentielle :
 * Afin de respecter le principe de responsabilite unique et l'independance absolue
 * des decorateurs, ce decorateur n'a AUCUNE dependance envers MentionDecorator.
 * Il execute lui-meme une requete SQL agregee sur la base de donnees pour calculer
 * les moyennes comparatives de tous les pairs de la promotion.
 * Ainsi, ClassementDecorator fonctionne aussi bien seul qu'empile avant ou apres MentionDecorator.
 */
final class ClassementDecorator extends AbstractStudentReportDecorator
{
    /**
     * Instance PDO pour interroger la base lors du calcul des rangs de la promotion.
     */
    private PDO $pdo;

    /**
     * Constructeur du decorateur de classement.
     *
     * @param StudentReportInterface $wrappee Composant enveloppe
     * @param PDO|null $pdo Connexion PDO facultative (si null, le Singleton DatabaseConnection est utilise)
     */
    public function __construct(
        StudentReportInterface $wrappee,
        ?PDO $pdo = null
    ) {
        parent::__construct($wrappee);
        $this->pdo = $pdo ?? DatabaseConnection::getInstance();
    }

    /**
     * Recupere le releve du wrappee, determine le rang de l'etudiant et injecte 'rank' et 'classSize'.
     *
     * @param int $studentId Identifiant de l'etudiant
     * @return array<string, mixed> Releve enrichi du rang et de la taille de classe
     *
     * @throws \OutOfBoundsException Si l'etudiant n'a aucune note ou n'appartient a aucune promotion
     */
    public function getReport(int $studentId): array
    {
        // 1. Appel du wrappee pour obtenir le releve sous-jacent
        $report = parent::getReport($studentId);

        // 2. Requete SQL pour calculer la moyenne ponderee de chaque etudiant de la meme promotion.
        // Utilisation de SUM(g.value * c.coefficient) * 1.0 / SUM(c.coefficient) pour obtenir une division reelle.
        $requetePromotion = $this->pdo->prepare(
            'SELECT s.id, SUM(g.value * c.coefficient) * 1.0 / SUM(c.coefficient) AS average
             FROM students s
             JOIN grades g ON g.student_id = s.id
             JOIN courses c ON c.id = g.course_id
             WHERE s.promotion_id = (SELECT promotion_id FROM students WHERE id = :id)
             GROUP BY s.id'
        );
        $requetePromotion->execute(['id' => $studentId]);

        // 3. Collecte des moyennes de chaque etudiant de la promotion
        $moyennes = [];
        foreach ($requetePromotion->fetchAll() as $ligne) {
            $moyennes[(int) $ligne['id']] = round((float) $ligne['average'], 2);
        }

        // Verifier que l'etudiant cible a bien une moyenne calculee
        $maMoyenne = $moyennes[$studentId] ?? null;
        if ($maMoyenne === null) {
            throw new \OutOfBoundsException("Aucune note trouvee pour calculer le rang de l'etudiant : $studentId");
        }

        // 4. Calcul du rang : nombre d'etudiants ayant une moyenne strictement superieure + 1
        $rang = 1;
        foreach ($moyennes as $moyenneAutre) {
            if ($moyenneAutre > $maMoyenne) {
                $rang++;
            }
        }

        // 5. Ajout des donnees de classement dans le releve
        $report['rank'] = $rang;
        $report['classSize'] = count($moyennes);

        return $report;
    }
}
