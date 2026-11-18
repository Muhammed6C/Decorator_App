<?php

declare(strict_types=1);

/**
 * Script tests/run.php
 *
 * Role : Suite de tests automatisés autonome (sans dépendances externes).
 *
 * Fonctionnalites testees :
 * 1. Base de donnees SQLite en memoire et jeu de donnees initial.
 * 2. Composant concret de base (ConcreteStudentReport).
 * 3. Decorateur metier Mention (MentionDecorator) : calcul pondere et bareme.
 * 4. Decorateur metier Classement (ClassementDecorator) : calcul de rang et commutativite.
 * 5. Decorateur technique Cache (CacheDecorator) : MISS, HIT, variantes et purge.
 * 6. Decorateur technique Logging (LoggingDecorator) : horodatage, fichier et inversion de pile.
 * 7. Fabrique de decorateurs (DecoratorBuilder) : chaines d'appels et instanciation.
 * 8. Controleur et Routeur HTTP (Router & ReportController) : status codes, validation, CORS.
 *
 * Utilisation :
 *   php tests/run.php
 *   ou : composer test
 */

// Chargement de l'autoloader Composer ou autoloader de secours PSR-4
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

use App\Database\DatabaseConnection;
use App\Decorators\AbstractStudentReportDecorator;
use App\Decorators\CacheDecorator;
use App\Decorators\ClassementDecorator;
use App\Decorators\ConcreteStudentReport;
use App\Decorators\LoggingDecorator;
use App\Decorators\MentionDecorator;
use App\Decorators\StudentReportInterface;
use App\Http\ReportController;
use App\Http\Router;
use App\Services\DecoratorBuilder;
use App\Services\ExecutionLog;
use App\Services\StudentService;

// Compteurs de succes et d'echecs
$reussis = 0;
$echecs = 0;

/**
 * Fonction d'assertion verifiant une condition booleenne.
 *
 * @param string $nom Intitule du test
 * @param bool $condition Condition a valider
 */
function verifier(string $nom, bool $condition): void
{
    global $reussis, $echecs;
    if ($condition) {
        $reussis++;
        echo '[OK]    ' . $nom . PHP_EOL;
    } else {
        $echecs++;
        echo '[ECHEC] ' . $nom . PHP_EOL;
    }
}

/**
 * Supprime recursivement un dossier temporaire de test.
 *
 * @param string $dossier Chemin du repertoire a effacer
 */
function supprimerDossier(string $dossier): void
{
    foreach (glob($dossier . '/*') ?: [] as $f) {
        is_dir($f) ? supprimerDossier($f) : unlink($f);
    }
    @rmdir($dossier);
}

/**
 * Bouchon de test (Spy) permettant d'intercepter et compter les appels reels faits au composant de base.
 */
final class RapportEspion implements StudentReportInterface
{
    public int $appels = 0;

    public function __construct(private readonly StudentReportInterface $vrai)
    {
    }

    public function getReport(int $studentId): array
    {
        $this->appels++;

        return $this->vrai->getReport($studentId);
    }
}

// 1. Preparation : Base SQLite en memoire et dossiers temporaires isoles
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.sql'));
DatabaseConnection::setInstance($pdo);

$tmp = sys_get_temp_dir() . '/decorator_tests_' . bin2hex(random_bytes(4));
mkdir($tmp . '/cache', 0777, true);
mkdir($tmp . '/logs', 0777, true);

$config = [
    'simulated_latency_ms' => 0,
    'cache' => ['directory' => $tmp . '/cache', 'ttl' => 300],
    'logs' => ['directory' => $tmp . '/logs'],
    'cors_origin' => 'http://localhost:5173',
    'debug' => false,
];

// 2. Tests de la base et du composant concret (ConcreteStudentReport)
$etudiants = (new StudentService($pdo))->listStudents();
verifier('base : 4 etudiants de demonstration', count($etudiants) === 4);

$base = new ConcreteStudentReport($pdo);
$brut = $base->getReport(3);
verifier(
    'composant de base : notes brutes, sans moyenne ni rang',
    count($brut['grades']) === 4 && !isset($brut['average']) && !isset($brut['rank'])
);

$introuvable = false;
try {
    $base->getReport(999);
} catch (OutOfBoundsException) {
    $introuvable = true;
}
verifier('composant de base : etudiant inconnu declenche OutOfBoundsException', $introuvable);

// 3. Tests du decorateur metier MentionDecorator
$mentions = [];
foreach ([1, 2, 3, 4] as $id) {
    $r = (new MentionDecorator($base))->getReport($id);
    $mentions[$id] = $r['mention']['code'] . ':' . $r['average'];
}
verifier('mentions : B 15.7 / P 11.3 / TB 16.3 / AB 13.7', $mentions === [1 => 'B:15.7', 2 => 'P:11.3', 3 => 'TB:16.3', 4 => 'AB:13.7']);
verifier(
    'mention : seuils 9.99 NV / 10 P / 12 AB / 14 B / 16 TB',
    MentionDecorator::mentionFor(9.99)['code'] === 'NV'
    && MentionDecorator::mentionFor(10.0)['code'] === 'P'
    && MentionDecorator::mentionFor(12.0)['code'] === 'AB'
    && MentionDecorator::mentionFor(14.0)['code'] === 'B'
    && MentionDecorator::mentionFor(16.0)['code'] === 'TB'
);

// 4. Tests du decorateur metier ClassementDecorator
$rangs = [];
foreach ([1, 2, 3, 4] as $id) {
    $rangs[$id] = (new ClassementDecorator($base, $pdo))->getReport($id)['rank'];
}
verifier('classement : Salma 1er, Amina 2e, Nadia 3e, Karim 4e', $rangs === [1 => 2, 2 => 4, 3 => 1, 4 => 3]);

$seul = (new ClassementDecorator($base, $pdo))->getReport(1);
verifier('classement fonctionne SEUL (independant de Mention)', !isset($seul['average']) && $seul['classSize'] === 4);

$a = (new ClassementDecorator(new MentionDecorator($base), $pdo))->getReport(4);
$b = (new MentionDecorator(new ClassementDecorator($base, $pdo)))->getReport(4);
verifier(
    'commutativite des calculs : Classement(Mention) = Mention(Classement)',
    $a['rank'] === $b['rank'] && $a['mention'] === $b['mention'] && $a['average'] === $b['average']
);

// 5. Tests du decorateur technique CacheDecorator
$espion = new RapportEspion($base);
$cache = new CacheDecorator($espion, $tmp . '/cache', 300, 'base');
$m1 = $cache->getReport(1);
$m2 = $cache->getReport(1);
verifier('cache : 1 seul appel reel pour 2 demandes', $espion->appels === 1);
verifier('cache : premiere demande MISS, seconde HIT', $m1['cache']['status'] === 'MISS' && $m2['cache']['status'] === 'HIT');

$cacheMention = new CacheDecorator(new MentionDecorator($espion), $tmp . '/cache', 300, 'mention');
$m3 = $cacheMention->getReport(1);
verifier(
    'cache : variante differente = cle differente (pas de resultat perime)',
    $espion->appels === 2 && isset($m3['mention']) && !isset($m2['mention'])
);

$expire = new CacheDecorator($espion, $tmp . '/cache', -1, 'ttl');
$expire->getReport(2);
$expire->getReport(2);
verifier('cache : une entree expiree (TTL) est recalculee', $espion->appels === 4);
verifier('cache : purge supprime tous les fichiers de cache', CacheDecorator::purge($tmp . '/cache') >= 3 && glob($tmp . '/cache/report_*.json') === []);

// 6. Tests du decorateur technique LoggingDecorator et ordonnancement des couches
$log = new ExecutionLog($tmp . '/logs');
$loggingSeul = (new LoggingDecorator($base, $log))->getReport(1);
verifier(
    'logging : la reponse contient les traces d execution',
    isset($loggingSeul['logs'][0]) && str_contains($loggingSeul['logs'][0], 'getReport(1) execute en')
);
verifier('logging : les traces sont bien ecrites dans le fichier de log journalier', glob($tmp . '/logs/execution-*.log') !== []);

$builder = new DecoratorBuilder($pdo, $config);
$l1 = new ExecutionLog();
$defaut = $builder->build(['mention', 'classement', 'cache', 'logging'], $l1);
$defaut->getReport(3);
$d2 = $defaut->getReport(3);
verifier(
    'ordre par defaut Logging(Cache) : le HIT est journalise',
    count($d2['logs']) >= 3 && $d2['cache']['status'] === 'HIT'
);

CacheDecorator::purge($tmp . '/cache');
$l2 = new ExecutionLog();
$inverse = $builder->build(['cache', 'logging'], $l2, true);
$i1 = $inverse->getReport(3);
$i2 = $inverse->getReport(3);
verifier(
    'ordre inverse Cache(Logging) : premier appel journalise, second direct sans log',
    isset($i1['logs']) && !isset($i2['logs']) && $i2['cache']['status'] === 'HIT'
);

// 7. Tests de DecoratorBuilder
verifier(
    'chaine par defaut ordonnee correctement',
    $builder->chain(['mention', 'classement', 'cache', 'logging']) === [
        'LoggingDecorator',
        'CacheDecorator',
        'ClassementDecorator',
        'MentionDecorator',
        'ConcreteStudentReport',
    ]
);
verifier(
    'chaine inversee avec cacheOutermost',
    $builder->chain(['cache', 'logging'], true) === [
        'CacheDecorator',
        'LoggingDecorator',
        'ConcreteStudentReport',
    ]
);
verifier(
    'sans decorateur : composant de base seul',
    $builder->chain([]) === ['ConcreteStudentReport']
    && $builder->build([], new ExecutionLog()) instanceof ConcreteStudentReport
);
verifier(
    'tous les decorateurs heritent de la classe abstraite commune',
    (new ReflectionClass(MentionDecorator::class))->getParentClass()->getName() === AbstractStudentReportDecorator::class
    && (new ReflectionClass(CacheDecorator::class))->getParentClass()->getName() === AbstractStudentReportDecorator::class
);

// 8. Tests du controleur HTTP et du routeur (sans serveur HTTP externe)
CacheDecorator::purge($tmp . '/cache');
$router = new Router(
    new ReportController($builder, new StudentService($pdo), $config),
    'http://localhost:5173'
);

[$s, $h, $c] = $router->dispatch('GET', '/api/students');
verifier('GET /api/students : 200 et 4 etudiants retournes', $s === 200 && count(json_decode($c, true)['students']) === 4);

[$s, $h, $c] = $router->dispatch(
    'POST',
    '/api/report',
    json_encode(['studentId' => 3, 'decorators' => ['mention', 'classement']])
);
$corps = json_decode($c, true);
verifier(
    'POST /api/report : 200, mention TB et rang 1',
    $s === 200
    && $corps['mention']['code'] === 'TB'
    && $corps['rank'] === 1
    && $corps['meta']['chain'][0] === 'ClassementDecorator'
);

[$s, , $c] = $router->dispatch(
    'POST',
    '/api/report',
    json_encode(['studentId' => 3, 'decorators' => ['inconnu']])
);
verifier('decorateur inconnu retourne 422', $s === 422 && json_decode($c, true)['error']['code'] === 'validation_error');

[$s] = $router->dispatch('POST', '/api/report', json_encode(['decorators' => []]));
verifier('studentId manquant retourne 422', $s === 422);

[$s] = $router->dispatch('POST', '/api/report', json_encode(['studentId' => 999]));
verifier('etudiant inconnu retourne 404', $s === 404);

[$s] = $router->dispatch('POST', '/api/report', '{format_json_invalide');
verifier('JSON invalide retourne 400', $s === 400);

[$s] = $router->dispatch('GET', '/api/report');
verifier('methode non autorisee retourne 405', $s === 405);

[$s] = $router->dispatch('GET', '/api/inconnue');
verifier('route inexistante retourne 404', $s === 404);

[$s, $h] = $router->dispatch('OPTIONS', '/api/report');
verifier(
    'CORS : OPTIONS repond 204 avec Access-Control-Allow-Origin',
    $s === 204 && $h['Access-Control-Allow-Origin'] === 'http://localhost:5173'
);

$router->dispatch('POST', '/api/report', json_encode(['studentId' => 1, 'decorators' => ['cache']]));
[$s, , $c] = $router->dispatch('POST', '/api/cache/clear');
verifier('POST /api/cache/clear : vide le cache et retourne le nombre', $s === 200 && json_decode($c, true)['cleared'] >= 1);

// Nettoyage de l'environnement temporaire
supprimerDossier($tmp);

echo PHP_EOL . $reussis . ' reussis, ' . $echecs . ' echec(s).' . PHP_EOL;
exit($echecs === 0 ? 0 : 1);
