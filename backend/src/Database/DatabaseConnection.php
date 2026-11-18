<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/**
 * Classe DatabaseConnection
 *
 * Role : Singleton responsable de la gestion et du partage de l'instance de connexion PDO.
 *
 * Cette classe assure qu'une seule et unique connexion a la base de donnees est ouverte
 * et partagee tout au long du cycle de vie de la requete.
 * Elle supporte a la fois SQLite (environnement de developpement rapide et tests)
 * et MySQL (deploiement en production ou base distante).
 *
 * Fonctionnalite speciale pour les tests :
 * La methode setInstance(?PDO $pdo) permet d'injecter une connexion factice ou en memoire
 * ('sqlite::memory:'), facilitant les tests d'integration sans toucher aux fichiers physiques.
 */
final class DatabaseConnection
{
    /**
     * Instance unique de connexion PDO (Singleton).
     */
    private static ?PDO $instance = null;

    /**
     * Constructeur prive pour interdire toute instanciation directe depuis l'exterieur.
     */
    private function __construct()
    {
    }

    /**
     * Point d'entree unique pour recuperer l'instance PDO partagee.
     * Si l'instance n'existe pas encore, elle est initialisee a partir de la configuration.
     *
     * @return PDO L'instance PDO partagee
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::creer(self::config());
        }

        return self::$instance;
    }

    /**
     * Permet de definir manuellement l'instance PDO (utile pour les suites de tests automatisés).
     *
     * @param PDO|null $pdo Instance de test a injecter, ou null pour reinitialiser
     */
    public static function setInstance(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    /**
     * Charge et retourne le tableau de configuration de la base de donnees.
     *
     * @return array<string, mixed> Configuration definie dans config/database.php
     */
    public static function config(): array
    {
        /** @var array<string, mixed> $config */
        $config = require __DIR__ . '/../../config/database.php';

        return $config;
    }

    /**
     * Cree et configure l'instance PDO en fonction du pilote selectionne ('sqlite' ou 'mysql').
     *
     * @param array<string, mixed> $config Configuration de la base de donnees
     * @return PDO Instance PDO configuree avec gestion stricte des erreurs et modes de fetch
     *
     * @throws \RuntimeException Si la base SQLite est introuvable ou si le pilote n'est pas supporte
     */
    private static function creer(array $config): PDO
    {
        $driver = $config['driver'] ?? 'sqlite';

        if ($driver === 'mysql') {
            /** @var array{host: string, port: string, database: string, username: string, password: string, charset: string} $m */
            $m = $config['mysql'];
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $m['host'],
                $m['port'],
                $m['database'],
                $m['charset']
            );
            $pdo = new PDO($dsn, $m['username'], $m['password']);
        } else {
            $chemin = (string) ($config['sqlite']['path'] ?? '');
            if (!is_file($chemin)) {
                throw new \RuntimeException(
                    "Base SQLite introuvable au chemin '$chemin'. Veuillez executer : php database/initialize.php"
                );
            }
            $pdo = new PDO('sqlite:' . $chemin);
            // Activation stricte de l'integrite des cles etrangeres sous SQLite
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        // Configuration standard pour des exceptions sur erreurs et des tableaux associatifs
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }
}
