<?php

declare(strict_types=1);

namespace App\Services;

use App\Decorators\CacheDecorator;
use App\Decorators\ClassementDecorator;
use App\Decorators\ConcreteStudentReport;
use App\Decorators\LoggingDecorator;
use App\Decorators\MentionDecorator;
use App\Decorators\StudentReportInterface;
use PDO;

/**
 * Classe DecoratorBuilder
 *
 * Role : Fabrique (Factory / Builder) responsable de l'assemblage dynamique de la chaine de decorateurs.
 *
 * C'est le point central ou le Design Pattern Decorateur exprime toute sa puissance :
 * Plutot que d'avoir une hierarchie de classes rigide creee par heritage statique
 * (ex: StudentReportWithMentionAndRankAndCacheAndLog), le Builder empile les couches
 * a l'execution (dynamically at runtime) selon les souhaits exacts de l'utilisateur.
 *
 * Gestion de l'ordre des couches :
 * - Du composant le plus interne au plus externe :
 *   1. ConcreteStudentReport (coeur metier, lecture base)
 *   2. MentionDecorator (si demande)
 *   3. ClassementDecorator (si demande)
 *   4. Cache / Logging :
 *      - Ordre standard : Cache enveloppe le metier, puis Logging enveloppe le Cache.
 *        Flux : Client -> Logging -> Cache -> Metier -> DB.
 *        Sur un cache HIT, Logging enregistre le HIT et la duree quasi nulle.
 *      - Ordre optionnel (cacheOutermost = true) : Cache enveloppe Logging qui enveloppe le Metier.
 *        Flux : Client -> Cache -> Logging -> Metier -> DB.
 *        Sur un cache HIT, la requete sort directement du Cache sans declencher de log.
 */
final class DecoratorBuilder
{
    /**
     * Noms officiels des decorateurs acceptes par l'API.
     */
    public const AVAILABLE = ['mention', 'classement', 'cache', 'logging'];

    /**
     * Constructeur de la fabrique de decorateurs.
     *
     * @param PDO $pdo Connexion PDO partagee
     * @param array<string, mixed> $config Configuration generale de l'application
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $config
    ) {
    }

    /**
     * Construit et retourne l'objet StudentReportInterface assemble selon les options choisies.
     *
     * @param list<string> $decorators Liste des decorateurs demandes par l'utilisateur
     * @param ExecutionLog $log Instance de journalisation pour LoggingDecorator
     * @param bool $cacheOutermost Si vrai, place le Cache au-dessus du Logging
     * @return StudentReportInterface Instance finale prete pour getReport()
     */
    public function build(
        array $decorators,
        ExecutionLog $log,
        bool $cacheOutermost = false
    ): StudentReportInterface {
        // Indexation rapide des decorateurs actifs
        $actifs = array_flip($decorators);

        // 1. Instanciation du composant de base (coeur de la pile)
        $report = new ConcreteStudentReport(
            $this->pdo,
            (int) ($this->config['simulated_latency_ms'] ?? 0)
        );

        // 2. Empilement des decorateurs metier (au-dessus du composant concret)
        if (isset($actifs['mention'])) {
            $report = new MentionDecorator($report);
        }
        if (isset($actifs['classement'])) {
            $report = new ClassementDecorator($report, $this->pdo);
        }

        // 3. Calcul de la cle de variante metier pour le cache
        $variante = $this->variante($actifs);

        // Definitions des fabriques pour les decorateurs techniques
        $couches = [
            'cache' => fn (StudentReportInterface $r): StudentReportInterface => new CacheDecorator(
                $r,
                (string) ($this->config['cache']['directory'] ?? __DIR__ . '/../../cache'),
                (int) ($this->config['cache']['ttl'] ?? 300),
                $variante
            ),
            'logging' => fn (StudentReportInterface $r): StudentReportInterface => new LoggingDecorator($r, $log),
        ];

        // 4. Empilement ordonne des couches techniques (de l'interieur vers l'exterieur)
        // Si cacheOutermost = true, logging est applique d'abord, puis cache au-dessus.
        // Sinon (defaut), cache est applique d'abord, puis logging au-dessus.
        $ordreTechnique = $cacheOutermost ? ['logging', 'cache'] : ['cache', 'logging'];

        foreach ($ordreTechnique as $nomTechnique) {
            if (isset($actifs[$nomTechnique])) {
                $report = $couches[$nomTechnique]($report);
            }
        }

        return $report;
    }

    /**
     * Retourne la representation ordonnee des classes composant la chaine,
     * de l'exterieur vers l'interieur (tel que la requete les traverse).
     *
     * @param list<string> $decorators Liste des decorateurs actifs
     * @param bool $cacheOutermost Position externe du cache
     * @return list<string> Noms des classes dans l'ordre d'interception
     */
    public function chain(array $decorators, bool $cacheOutermost = false): array
    {
        $actifs = array_flip($decorators);
        $chaine = ['ConcreteStudentReport'];

        // Insertion des couches metier
        if (isset($actifs['mention'])) {
            array_unshift($chaine, 'MentionDecorator');
        }
        if (isset($actifs['classement'])) {
            array_unshift($chaine, 'ClassementDecorator');
        }

        // Insertion des couches techniques (exterieures)
        $ordreTechnique = $cacheOutermost ? ['logging', 'cache'] : ['cache', 'logging'];
        foreach ($ordreTechnique as $nomTechnique) {
            if (isset($actifs[$nomTechnique])) {
                $classe = $nomTechnique === 'cache' ? 'CacheDecorator' : 'LoggingDecorator';
                array_unshift($chaine, $classe);
            }
        }

        return $chaine;
    }

    /**
     * Determine le discriminant de variante a integrer dans la cle de cache.
     * Cela garantit qu'un releve avec Mention seule n'ecrase pas un releve avec Mention + Classement.
     *
     * @param array<string, int> $actifs Noms des decorateurs actifs sous forme de cles
     * @return string Identifiant de variante (ex: 'base', 'mention', 'mention+classement')
     */
    private function variante(array $actifs): string
    {
        $elements = [];
        foreach (['mention', 'classement'] as $nom) {
            if (isset($actifs[$nom])) {
                $elements[] = $nom;
            }
        }

        return $elements === [] ? 'base' : implode('+', $elements);
    }
}
