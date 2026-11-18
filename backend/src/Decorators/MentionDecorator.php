<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * Classe MentionDecorator
 *
 * Role dans le Design Pattern Decorateur : Concrete Decorator (Decorateur Concret Metier).
 *
 * Ce decorateur etend AbstractStudentReportDecorator pour enrichir le releve
 * avec les metriques academiques fondamentales :
 * - Le calcul de la moyenne generale ponderee en fonction des coefficients des matieres.
 * - L'attribution d'une mention standardisee selon l'echelle universitaire francaise.
 *
 * Propriete importante : Ce decorateur est totalement autonome. Il ne depend d'aucun autre
 * decorateur et opere uniquement sur les notes brutes fournies par l'objet interne.
 */
final class MentionDecorator extends AbstractStudentReportDecorator
{
    /**
     * Calcule la moyenne ponderee d'une liste de notes en tenant compte de leurs coefficients.
     *
     * Formule : Somme(note * coefficient) / Somme(coefficients)
     *
     * @param list<array{coefficient: int, value: float}> $notes Liste des notes et coefficients
     * @return float Moyenne arrondie a deux decimales
     */
    public static function weightedAverage(array $notes): float
    {
        $totalPondere = 0.0;
        $totalCoefficients = 0;

        foreach ($notes as $note) {
            $coefficient = $note['coefficient'];
            $valeur = $note['value'];

            $totalPondere += $valeur * $coefficient;
            $totalCoefficients += $coefficient;
        }

        // Si aucun coefficient n'est present, retourne 0.0 pour eviter une division par zero
        if ($totalCoefficients === 0) {
            return 0.0;
        }

        return round($totalPondere / $totalCoefficients, 2);
    }

    /**
     * Determine la mention academique attribuee selon le bareme officiel.
     *
     * Bareme :
     * - Moyenne >= 16 : Tres bien (TB)
     * - Moyenne >= 14 : Bien (B)
     * - Moyenne >= 12 : Assez bien (AB)
     * - Moyenne >= 10 : Passable (P)
     * - Moyenne < 10  : Non valide (NV)
     *
     * @param float $moyenne Moyenne generale sur 20
     * @return array{code: string, label: string} Code abrege et libelle de la mention
     */
    public static function mentionFor(float $moyenne): array
    {
        return match (true) {
            $moyenne >= 16.0 => ['code' => 'TB', 'label' => 'Tres bien'],
            $moyenne >= 14.0 => ['code' => 'B', 'label' => 'Bien'],
            $moyenne >= 12.0 => ['code' => 'AB', 'label' => 'Assez bien'],
            $moyenne >= 10.0 => ['code' => 'P', 'label' => 'Passable'],
            default => ['code' => 'NV', 'label' => 'Non valide'],
        };
    }

    /**
     * Intercepte l'appel getReport, delegue a l'objet enveloppe, puis greffe
     * la moyenne ponderee et la mention avant de retourner le tableau enrichi.
     *
     * @param int $studentId Identifiant de l'etudiant
     * @return array<string, mixed> Releve contenant desormais 'average' et 'mention'
     */
    public function getReport(int $studentId): array
    {
        // 1. Delegation a l'element sous-jacent (composant de base ou autre decorateur)
        $report = parent::getReport($studentId);

        // 2. Traitement metier specifique : calcul de la moyenne a partir des notes
        /** @var list<array{coefficient: int, value: float}> $grades */
        $grades = $report['grades'] ?? [];
        $moyenne = self::weightedAverage($grades);

        // 3. Attribution de la mention correspondante
        $mention = self::mentionFor($moyenne);

        // 4. Enrichissement du releve avec les nouvelles proprietes
        $report['average'] = $moyenne;
        $report['mention'] = $mention;

        return $report;
    }
}
