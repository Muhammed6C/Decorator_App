<?php

declare(strict_types=1);

/**
 * Fichier public/index.php
 *
 * Role : Front Controller (Point d'entree unique de l'API HTTP).
 *
 * Toutes les requetes HTTP entrantes (serveur PHP integre, Apache, Nginx) sont
 * redirigees vers ce fichier unique.
 *
 * Ses responsabilites :
 * 1. Supprimer l'en-tete de signature serveur X-Powered-By pour des raisons de securite.
 * 2. Charger l'autoloader Composer (ou charger l'autoloader PSR-4 de secours si Composer n'est pas installe).
 * 3. Initialiser la connexion a la base de donnees via le Singleton DatabaseConnection.
 * 4. Instancier le Router et les controleurs avec leurs dependances.
 * 5. Parser l'URI et la methode HTTP pour declencher Router::dispatch().
 * 6. Capturer toute exception non geree et renvoyer une reponse 500 JSON standardisee.
 * 7. Emettre les en-tetes HTTP reelles et afficher le corps JSON.
 */

use App\Database\DatabaseConnection;
use App\Http\ReportController;
use App\Http\Router;
use App\Services\DecoratorBuilder;
use App\Services\StudentService;

// Suppression de la signature PHP dans les reponses
header_remove('X-Powered-By');

// 1. Chargement de l'autoloader Composer ou autoloader de secours PSR-4
$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
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

// 2. Chargement de la configuration applicative
$config = DatabaseConnection::config();

try {
    // 3. Recuperation de la connexion PDO et construction du routeur
    $pdo = DatabaseConnection::getInstance();
    $router = new Router(
        new ReportController(
            new DecoratorBuilder($pdo, $config),
            new StudentService($pdo),
            $config
        ),
        (string) ($config['cors_origin'] ?? 'http://localhost:5173')
    );

    // 4. Extraction propre de la methode HTTP et du chemin d'acces
    $methode = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $uriBrute = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $chemin = (string) parse_url($uriBrute, PHP_URL_PATH);
    $corpsEntrant = file_get_contents('php://input') ?: null;

    // 5. Routage et resolution de la requete
    [$statut, $enTetes, $corps] = $router->dispatch($methode, $chemin, $corpsEntrant);
} catch (Throwable $e) {
    // 6. Gestion globale des exceptions non capturees
    $statut = 500;
    $enTetes = [
        'Content-Type' => 'application/json; charset=utf-8',
        'Access-Control-Allow-Origin' => (string) ($config['cors_origin'] ?? '*'),
    ];
    $corps = (string) json_encode(
        [
            'error' => [
                'code' => 'server_error',
                'message' => ($config['debug'] ?? false) ? $e->getMessage() : 'Erreur interne du serveur.',
            ],
        ],
        JSON_UNESCAPED_UNICODE
    );
}

// 7. Envoi des en-tetes HTTP et affichage du flux de reponse
http_response_code($statut);
foreach ($enTetes as $nom => $valeur) {
    header($nom . ': ' . $valeur);
}
echo $corps;
