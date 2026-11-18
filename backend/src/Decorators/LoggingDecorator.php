<?php

declare(strict_types=1);

namespace App\Decorators;

use App\Services\ExecutionLog;

/**
 * Classe LoggingDecorator
 *
 * Role dans le Design Pattern Decorateur : Concrete Decorator (Decorateur Concret Technique).
 *
 * Ce decorateur illustre l'action avant/apres (pre-action / post-action) sur l'appel delegue :
 * 1. Pre-action : Il capture le timestamp haute precision de debut (hrtime).
 * 2. Delegation : Il execute `parent::getReport($studentId)` sur l'objet enveloppe.
 * 3. Post-action : Il calcule la duree d'execution en millisecondes, enregistre
 *    les messages dans le journal d'evenements ExecutionLog (qui ecrit egalement sur disque),
 *    et injecte l'historique complet des logs dans la reponse finale via la cle 'logs'.
 *
 * Importance de la position dans la pile de decorateurs :
 * - Si LoggingDecorator enveloppe CacheDecorator (ordre par defaut) :
 *   Un cache HIT est trace et journalise avec un temps d'execution quasi instantane (< 1 ms).
 * - Si CacheDecorator enveloppe LoggingDecorator (option cacheOutermost = true) :
 *   Un cache HIT retourne directement la valeur depuis le cache sans passer par LoggingDecorator,
 *   ce qui demontre comment l'ordonnancement des decorateurs impacte directement le flux d'execution.
 */
final class LoggingDecorator extends AbstractStudentReportDecorator
{
    /**
     * Constructeur du decorateur de journalisation.
     *
     * @param StudentReportInterface $wrappee Composant enveloppe
     * @param ExecutionLog $log Service de collecte et de persistance des evenements de log
     */
    public function __construct(
        StudentReportInterface $wrappee,
        private readonly ExecutionLog $log
    ) {
        parent::__construct($wrappee);
    }

    /**
     * Mesure la duree du traitement, journalise les actions et enrichit la reponse avec la cle 'logs'.
     *
     * @param int $studentId Identifiant de l'etudiant
     * @return array<string, mixed> Releve comprenant les logs d'execution dans la propriete 'logs'
     */
    public function getReport(int $studentId): array
    {
        // 1. Horodatage de debut a haute precision (nanosecondes)
        $debut = hrtime(true);

        // 2. Delegation au composant ou decorateur suivant
        $report = parent::getReport($studentId);

        // 3. Calcul du temps ecoule converti en millisecondes
        $dureeMs = (hrtime(true) - $debut) / 1_000_000;

        // 4. Enregistrement des evenements dans le journal
        $this->log->add(
            'LoggingDecorator',
            sprintf('getReport(%d) execute en %.1f ms', $studentId, $dureeMs)
        );

        // Si le decorateur de cache a ete traverse, consigner son statut (HIT ou MISS)
        if (isset($report['cache']['status'])) {
            $this->log->add('CacheDecorator', 'cache ' . $report['cache']['status']);
        }

        // 5. Persistance sur disque dans le fichier de log journalier
        $this->log->persist();

        // 6. Injection des lignes de journalisation dans le resultat JSON envoye au client
        $report['logs'] = $this->log->lines();

        return $report;
    }
}
