<?php

declare(strict_types=1);

namespace App\Http;

use App\Decorators\CacheDecorator;
use App\Services\DecoratorBuilder;
use App\Services\ExecutionLog;
use App\Services\StudentService;

/**
 * Classe ReportController
 *
 * Role : Controleur HTTP de l'API.
 *
 * Ses responsabilites sont :
 * 1. Valider rigoureusement les parametres de la requete (presence de studentId, types, decorateurs valides).
 * 2. Instancier le service d'evenements d'execution (ExecutionLog).
 * 3. Demander au DecoratorBuilder d'assembler la pile personnalisee de decorateurs.
 * 4. Executer la requete et capturer la duree globale de traitement.
 * 5. Injecter les metadonnees 'meta' (liste des decorateurs, ordre de la chaine, temps en ms) dans la reponse.
 * 6. Retourner un couple normalise [codeHTTP, donnees].
 */
final class ReportController
{
    /**
     * Constructeur du controleur.
     *
     * @param DecoratorBuilder $builder Fabrique de composition des decorateurs
     * @param StudentService $students Service d'acces aux etudiants
     * @param array<string, mixed> $config Configuration generale de l'application
     */
    public function __construct(
        private readonly DecoratorBuilder $builder,
        private readonly StudentService $students,
        private readonly array $config
    ) {
    }

    /**
     * Endpoint de verification de disponibilite de l'API (Healthcheck).
     *
     * @return array{0: int, 1: array<string, mixed>} Statut HTTP et charge utile
     */
    public function health(): array
    {
        return [200, ['status' => 'ok']];
    }

    /**
     * Endpoint listant l'ensemble des etudiants enregistres.
     *
     * @return array{0: int, 1: array<string, mixed>} Statut HTTP et liste des etudiants
     */
    public function students(): array
    {
        return [200, ['students' => $this->students->listStudents()]];
    }

    /**
     * Endpoint principal : genere le releve de notes enrichi dynamiquement par les decorateurs.
     *
     * @param mixed $payload Corps JSON decode recu du client
     * @return array{0: int, 1: array<string, mixed>} Statut HTTP et releve complet ou message d'erreur
     */
    public function report(mixed $payload): array
    {
        // 1. Verification du format global de la charge utile (objet JSON requis)
        if (!is_array($payload)) {
            return $this->erreur(400, 'invalid_json', 'Le corps de la requete doit etre un objet JSON valide.');
        }

        // 2. Validation de l'identifiant de l'etudiant (studentId obligatoire et positif)
        $id = filter_var($payload['studentId'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return $this->erreur(422, 'validation_error', "Le champ 'studentId' doit etre un entier superieur a zero.");
        }

        // 3. Validation de la liste des decorateurs demandes
        $decorators = $payload['decorators'] ?? [];
        if (!is_array($decorators) || !array_is_list($decorators)) {
            return $this->erreur(422, 'validation_error', "Le champ 'decorators' doit etre une liste de chaines.");
        }
        foreach ($decorators as $nom) {
            if (!is_string($nom) || !in_array($nom, DecoratorBuilder::AVAILABLE, true)) {
                return $this->erreur(
                    422,
                    'validation_error',
                    "Decorateur inconnu '$nom'. Valeurs permises : " . implode(', ', DecoratorBuilder::AVAILABLE) . '.'
                );
            }
        }
        // Elimination d'eventuels doublons
        $decorators = array_values(array_unique($decorators));

        // 4. Validation des options d'agencement
        $options = $payload['options'] ?? [];
        if (!is_array($options)) {
            return $this->erreur(422, 'validation_error', "Le champ 'options' doit etre un objet associatif.");
        }
        $cacheOutermost = ($options['cacheOutermost'] ?? false) === true;

        // 5. Instanciation du logger et construction de la chaine de decorateurs
        $log = new ExecutionLog((string) ($this->config['logs']['directory'] ?? __DIR__ . '/../../logs'));
        $service = $this->builder->build($decorators, $log, $cacheOutermost);

        // 6. Mesure du temps total et execution de la requete
        $debut = hrtime(true);
        try {
            $report = $service->getReport($id);
        } catch (\OutOfBoundsException $e) {
            return $this->erreur(404, 'not_found', $e->getMessage());
        }
        $dureeMs = (hrtime(true) - $debut) / 1_000_000;

        // 7. Enrichissement de la reponse avec les metadonnees techniques
        $report['meta'] = [
            'decorators' => $decorators,
            'chain' => $this->builder->chain($decorators, $cacheOutermost),
            'cacheOutermost' => $cacheOutermost,
            'durationMs' => round($dureeMs, 1),
        ];

        return [200, $report];
    }

    /**
     * Endpoint pour vider integralement le cache fichier.
     *
     * @return array{0: int, 1: array<string, mixed>} Nombre de fichiers nettoyes
     */
    public function clearCache(): array
    {
        $dossierCache = (string) ($this->config['cache']['directory'] ?? __DIR__ . '/../../cache');
        $nbSupprimes = CacheDecorator::purge($dossierCache);

        return [200, ['cleared' => $nbSupprimes]];
    }

    /**
     * Methode d'aide interne pour construire une reponse d'erreur standardisee.
     *
     * @param int $statut Code de reponse HTTP (400, 404, 422, etc.)
     * @param string $code Identifiant textuel de l'erreur
     * @param string $message Explication a destination du client
     * @return array{0: int, 1: array<string, mixed>} Tuple normalise
     */
    private function erreur(int $statut, string $code, string $message): array
    {
        return [
            $statut,
            ['error' => ['code' => $code, 'message' => $message]],
        ];
    }
}
