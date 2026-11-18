<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/**
 * DatabaseConnection
 *
 * Singleton responsable de l'instanciation et du partage
 * de la connexion PDO unique à l'application.
 * Charge les paramètres depuis /config/database.php.
 * Supporte SQLite (développement) et MySQL (production).
 */
class DatabaseConnection
{
    private static ?self $instance = null;

    private PDO $connection;

    private function __clone()
    {
    }

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $driver = $config['driver'] ?? null;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if ($driver === 'sqlite') {
            $path = $config['sqlite']['path'] ?? null;
            if (!is_string($path) || $path === '') {
                throw new \RuntimeException('A valid SQLite database path is required.');
            }

            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create SQLite directory: %s', $directory));
            }

            $this->connection = new PDO('sqlite:' . $path, null, null, $options);
            $this->connection->exec('PRAGMA foreign_keys = ON');
            return;
        }

        if ($driver === 'mysql') {
            $mysql = $config['mysql'] ?? [];
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $mysql['host'] ?? 'localhost',
                $mysql['port'] ?? '3306',
                $mysql['dbname'] ?? '',
                $mysql['charset'] ?? 'utf8mb4'
            );
            $this->connection = new PDO(
                $dsn,
                $mysql['user'] ?? '',
                $mysql['pass'] ?? '',
                $options + [PDO::ATTR_EMULATE_PREPARES => false]
            );
            return;
        }

        throw new \RuntimeException(sprintf('Unsupported database driver: %s', (string) $driver));
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
