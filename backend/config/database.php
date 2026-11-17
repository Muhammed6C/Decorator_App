<?php

/**
 * config/database.php — Configuration de la connexion PDO
 *
 * Retourne un tableau de paramètres utilisé par DatabaseConnection.
 * Le pilote actif (sqlite / mysql) est sélectionnable via la constante APP_ENV.
 * Ne contient aucun identifiant de connexion en clair en production :
 * les valeurs sensibles doivent être injectées via des variables d'environnement.
 */

return [
    'driver'   => 'sqlite',   // 'sqlite' | 'mysql'
    'sqlite'   => [
        'path' => __DIR__ . '/../database/database.sqlite',
    ],
    'mysql'    => [
        'host'    => $_ENV['DB_HOST']     ?? 'localhost',
        'port'    => $_ENV['DB_PORT']     ?? '3306',
        'dbname'  => $_ENV['DB_NAME']     ?? 'decorator_app',
        'user'    => $_ENV['DB_USER']     ?? 'root',
        'pass'    => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
    ],
];
