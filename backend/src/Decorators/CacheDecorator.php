<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * CacheDecorator (Décorateur Technique n°3)
 *
 * Étend AbstractStudentReportDecorator.
 * Avant de déléguer au composant décoré, vérifie si une entrée
 * de cache valide existe pour l'identifiant étudiant donné
 * (fichier JSON dans /cache avec une durée de vie limitée).
 * En cas de cache hit, retourne immédiatement sans appel BDD.
 * En cas de cache miss, stocke le résultat après récupération.
 * Ajoute la clé 'cache_hit' (bool) au tableau de résultats.
 */
class CacheDecorator extends AbstractStudentReportDecorator
{
    private const TTL_SECONDS = 60;

    private string $cacheScope;

    private string $cacheDirectory;

    public function __construct(StudentReportInterface $wrappee, string $cacheScope = 'default')
    {
        parent::__construct($wrappee);
        $this->cacheScope = $cacheScope;
        $this->cacheDirectory = __DIR__ . '/../../cache';
    }

    public function getReport(int $studentId): array
    {
        $cachePath = $this->getCachePath($studentId);
        $cachedReport = $this->readCache($cachePath);
        if ($cachedReport !== null) {
            $report = $cachedReport;
            $report['cache_hit'] = true;
            return $report;
        }

        $report = $this->wrappee->getReport($studentId);
        $this->writeCache($cachePath, $report);
        $report['cache_hit'] = false;

        return $report;
    }

    private function getCachePath(int $studentId): string
    {
        $scopeHash = hash('sha256', $this->cacheScope);
        return sprintf('%s/report_%d_%s.json', $this->cacheDirectory, $studentId, $scopeHash);
    }

    private function readCache(string $cachePath): ?array
    {
        if (!is_file($cachePath)) {
            return null;
        }

        $contents = file_get_contents($cachePath);
        if ($contents === false) {
            throw new \RuntimeException(sprintf('Unable to read cache file: %s', $cachePath));
        }

        $entry = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (
            !is_array($entry)
            || !isset($entry['expires_at'], $entry['report'])
            || !is_int($entry['expires_at'])
            || !is_array($entry['report'])
        ) {
            throw new \RuntimeException(sprintf('Invalid cache entry: %s', $cachePath));
        }

        if ($entry['expires_at'] <= time()) {
            if (!unlink($cachePath) && is_file($cachePath)) {
                throw new \RuntimeException(sprintf('Unable to remove expired cache file: %s', $cachePath));
            }
            return null;
        }

        return $entry['report'];
    }

    private function writeCache(string $cachePath, array $report): void
    {
        if (!is_dir($this->cacheDirectory)
            && !mkdir($this->cacheDirectory, 0775, true)
            && !is_dir($this->cacheDirectory)
        ) {
            throw new \RuntimeException(sprintf('Unable to create cache directory: %s', $this->cacheDirectory));
        }

        $contents = json_encode([
            'expires_at' => time() + self::TTL_SECONDS,
            'report' => $report,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $temporaryPath = tempnam($this->cacheDirectory, '.report-cache-');
        if ($temporaryPath === false) {
            throw new \RuntimeException(sprintf('Unable to create cache file in: %s', $this->cacheDirectory));
        }

        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
            unlink($temporaryPath);
            throw new \RuntimeException(sprintf('Unable to write cache file: %s', $temporaryPath));
        }

        if (!rename($temporaryPath, $cachePath)) {
            unlink($temporaryPath);
            throw new \RuntimeException(sprintf('Unable to publish cache file: %s', $cachePath));
        }
    }
}
