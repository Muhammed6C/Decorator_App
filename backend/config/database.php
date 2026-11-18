<?php

declare(strict_types=1);

/**
 * Fichier config/database.php
 *
 * Role : Configuration centrale de l'application et de la base de donnees.
 *
 * Chaque parametre dispose d'une valeur par defaut adaptee a l'environnement de developpement local,
 * mais peut etre surcharge par des variables d'environnement systeme (fichiers .env, conteneurs Docker, CI/CD).
 *
 * Exemple de personnalisation sous Windows (PowerShell) :
 *   $env:SIMULATED_LATENCY_MS = "0"
 *   $env:DB_DRIVER = "sqlite"
 *   php -S localhost:8000 -t public
 */

/**
 * Fonction utilitaire anonyme pour lire une variable d'environnement avec valeur de repli.
 *
 * @param string $nom Nom de la variable d'environnement
 * @param string $defaut Valeur par defaut a utiliser si la variable est absente ou vide
 * @return string
 */
$env = static function (string $nom, string $defaut): string {
    $valeur = getenv($nom);

    return $valeur === false || $valeur === '' ? $defaut : $valeur;
};

return [
    // Pilote de base de donnees a utiliser : 'sqlite' (par defaut) ou 'mysql'
    'driver' => $env('DB_DRIVER', 'sqlite'),

    // Configuration specifique au pilote SQLite
    'sqlite' => [
        'path' => $env('DB_SQLITE_PATH', __DIR__ . '/../database/database.sqlite'),
    ],

    // Configuration specifique au pilote MySQL
    'mysql' => [
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => $env('DB_PORT', '3306'),
        'database' => $env('DB_NAME', 'decorator_app'),
        'username' => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],

    // Latence simulee en millisecondes pour accentuer visuellement l'interet du CacheDecorator lors des tests
    'simulated_latency_ms' => (int) $env('SIMULATED_LATENCY_MS', '2000'),

    // Configuration du decorateur de cache
    'cache' => [
        'directory' => $env('CACHE_DIR', __DIR__ . '/../cache'),
        'ttl' => (int) $env('CACHE_TTL', '300'), // Duree de vie par defaut : 300 secondes (5 minutes)
    ],

    // Configuration du decorateur de logging
    'logs' => [
        'directory' => $env('LOG_DIR', __DIR__ . '/../logs'),
    ],

    // Autorisation CORS pour le frontend (ex: application Vue, React ou Angular sur Vite)
    'cors_origin' => $env('CORS_ORIGIN', 'http://localhost:5173'),

    // Mode debogage : affiche les messages detailles des exceptions en cas d'erreur 500
    'debug' => $env('APP_DEBUG', '0') === '1',
];
