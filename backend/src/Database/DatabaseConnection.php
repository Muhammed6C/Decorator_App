<?php

declare(strict_types=1);

namespace App\Database;

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
    // Pattern Singleton : instance privée statique.
    // getInstance() retourne le PDO partagé.
}
