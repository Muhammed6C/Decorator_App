<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Database/DatabaseConnection.php';

use App\Database\DatabaseConnection;

$schemaPath = __DIR__ . '/schema.sql';
$schema = file_get_contents($schemaPath);

if ($schema === false) {
    throw new RuntimeException(sprintf('Unable to read database schema: %s', $schemaPath));
}

DatabaseConnection::getInstance()->getConnection()->exec($schema);

echo "SQLite database initialized with the demo students, courses, and grades.\n";
