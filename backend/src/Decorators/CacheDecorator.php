<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * Classe CacheDecorator
 *
 * Role dans le Design Pattern Decorateur : Concrete Decorator (Decorateur Concret Technique).
 *
 * Ce decorateur illustre parfaitement l'interception transparente dans le pattern Decorateur :
 * - Si le resultat est deja en cache et non expire (HIT) : il est immediatement retourne SANS appeler
 *   le composant sous-jacent (court-circuit / bypass). Cela permet d'eviter l'acces base de donnees et
 *   les calculs lourds.
 * - Si le resultat n'est pas en cache ou est expire (MISS) : il delegue l'appel au wrappee via parent::getReport(),
 *   sauvegarde le resultat dans un fichier JSON, puis le retourne.
 *
 * Conception de la cle de cache :
 * La cle est composee de : studentId + '|' + variant.
 * La 'variant' represente la combinaison ordonnee des decorateurs metier actifs situes SOUS ce cache.
 * Ainsi, changer la selection de l'utilisateur (par exemple activer Mention sans Classement) produit
 * une cle distincte et empeche tout retour de donnees perimees ou incompletes.
 *
 * Nettoyage des donnees volatiles :
 * Les donnees ephemeres liees a la requete courante ('logs' et 'cache') ne sont jamais persistees sur disque.
 */
final class CacheDecorator extends AbstractStudentReportDecorator
{
    /**
     * Cles volatiles propres a l'appel en cours qui ne doivent pas etre figees dans le cache disque.
     */
    private const VOLATILE_KEYS = ['logs' => true, 'cache' => true];

    /**
     * Constructeur du decorateur de cache.
     *
     * @param StudentReportInterface $wrappee Composant enveloppe
     * @param string $directory Dossier dans lequel ecrire les fichiers de cache
     * @param int $ttlSeconds Duree de vie du cache en secondes (TTL : Time To Live)
     * @param string $variant Chaine identifiant la configuration des decorateurs internes
     */
    public function __construct(
        StudentReportInterface $wrappee,
        private readonly string $directory,
        private readonly int $ttlSeconds,
        private readonly string $variant
    ) {
        parent::__construct($wrappee);
    }

    /**
     * Intercepte l'appel : sert la reponse depuis le cache en cas de HIT, ou delegue et persiste en cas de MISS.
     *
     * @param int $studentId Identifiant de l'etudiant
     * @return array<string, mixed> Releve enrichi avec les metadonnees de cache { status: "HIT" | "MISS", key: string }
     */
    public function getReport(int $studentId): array
    {
        // Construction de la cle logique et calcul de l'empreinte sha1 pour le nom de fichier
        $cle = $studentId . '|' . $this->variant;
        $fichier = $this->directory . DIRECTORY_SEPARATOR . 'report_' . sha1($cle) . '.json';

        // 1. Verification de l'existence et de la validite du cache (HIT)
        $contenu = is_file($fichier) ? json_decode((string) file_get_contents($fichier), true) : null;
        if (
            is_array($contenu)
            && ($contenu['expiresAt'] ?? 0) > time()
            && is_array($contenu['payload'] ?? null)
        ) {
            /** @var array<string, mixed> $report */
            $report = $contenu['payload'];
            // Enrichissement avec les informations de statut de cache HIT
            $report['cache'] = [
                'status' => 'HIT',
                'key' => $cle,
            ];

            return $report;
        }

        // 2. En cas d'absence ou d'expiration du cache (MISS) : delegation a la chaine sous-jacente
        $report = parent::getReport($studentId);

        // Sauvegarde sur disque de la charge utile (en filtrant les attributs volatils comme logs)
        $this->ecrire($fichier, array_diff_key($report, self::VOLATILE_KEYS));

        // Enrichissement avec les informations de statut de cache MISS
        $report['cache'] = [
            'status' => 'MISS',
            'key' => $cle,
        ];

        return $report;
    }

    /**
     * Supprime tous les fichiers de cache generes dans le dossier specifie.
     *
     * @param string $directory Dossier de cache a purger
     * @return int Nombre de fichiers supprimes avec succes
     */
    public static function purge(string $directory): int
    {
        $supprimes = 0;
        $fichiers = glob($directory . DIRECTORY_SEPARATOR . 'report_*.json') ?: [];

        foreach ($fichiers as $fichier) {
            if (is_file($fichier) && unlink($fichier)) {
                $supprimes++;
            }
        }

        return $supprimes;
    }

    /**
     * Ecrit de maniere securisee la charge utile et la date d'expiration dans le fichier de cache.
     *
     * @param string $fichier Chemin complet du fichier cible
     * @param array<string, mixed> $payload Donnees du releve a mettre en cache
     */
    private function ecrire(string $fichier, array $payload): void
    {
        // Creation du dossier de cache si necessaire
        if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true) && !is_dir($this->directory)) {
            // Si le dossier ne peut etre cree, on abandonne l'ecriture sans interrompre la reponse
            return;
        }

        $donneesCache = [
            'expiresAt' => time() + $this->ttlSeconds,
            'payload' => $payload,
        ];

        file_put_contents(
            $fichier,
            json_encode($donneesCache, JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}
