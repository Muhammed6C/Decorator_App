<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * CacheDecorator (Décorateur Technique n°3)
 *
 * Étend AbstractStudentReportDecorator.
 * Avant de déléguer au composant décoré, vérifie si une entrée
 * de cache valide existe pour l'identifiant étudiant donné
 * (fichier JSON dans /cache ou variable statique en mémoire).
 * En cas de cache hit, retourne immédiatement sans appel BDD.
 * En cas de cache miss, stocke le résultat après récupération.
 * Ajoute la clé 'cache_hit' (bool) au tableau de résultats.
 */
class CacheDecorator extends AbstractStudentReportDecorator
{
    private const TTL_SECONDS = 60;

    private static array $cache = [];

    private string $cacheScope;

    public function __construct(StudentReportInterface $wrappee, string $cacheScope = 'default')
    {
        parent::__construct($wrappee);
        $this->cacheScope = $cacheScope;
    }

    public function getReport(int $studentId): array
    {
        $key = $this->cacheScope . ':' . $studentId;
        $cached = self::$cache[$key] ?? null;
        if ($cached !== null && $cached['expires_at'] > time()) {
            $report = $cached['report'];
            $report['cache_hit'] = true;
            return $report;
        }

        $report = $this->wrappee->getReport($studentId);
        self::$cache[$key] = [
            'expires_at' => time() + self::TTL_SECONDS,
            'report' => $report,
        ];
        $report['cache_hit'] = false;

        return $report;
    }
}
