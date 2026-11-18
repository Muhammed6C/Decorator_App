<?php

declare(strict_types=1);

/**
 * Script database/initialize.php
 *
 * Role : Initialisation ou reinitialisation complete de la base de donnees de developpement.
 *
 * Ce script :
 * 1. Verifie que le pilote configure est bien SQLite.
 * 2. Cree automatiquement le repertoire cible si necessaire.
 * 3. Supprime l'ancienne base SQLite si elle existait deja.
 * 4. Cree une nouvelle base et execute le fichier schema.sql (tables et donnees initiales).
 * 5. Affiche le nombre d'etudiants inserees pour confirmer le succes de l'operation.
 *
 * Utilisation :
 *   php database/initialize.php
 *   ou : composer db:init
 */

$config = require __DIR__ . '/../config/database.php';

// Verification du pilote
if ($config['driver'] !== 'sqlite') {
    fwrite(
        STDERR,
        "Ce script d'initialisation rapide est prevu pour SQLite. Pour MySQL, veuillez adapter schema.sql et importer manuellement.\n"
    );
    exit(1);
}

$cheminBase = (string) $config['sqlite']['path'];
$dossier = dirname($cheminBase);

// Creation du dossier contenant le fichier sqlite si necessaire
if (!is_dir($dossier) && !mkdir($dossier, 0777, true) && !is_dir($dossier)) {
    fwrite(STDERR, "Erreur : Impossible de creer le dossier $dossier\n");
    exit(1);
}

// Suppression du fichier de base de donnees precedent s'il existe
if (is_file($cheminBase)) {
    unlink($cheminBase);
}

// Connexion et execution du schema SQL
$pdo = new PDO('sqlite:' . $cheminBase);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = (string) file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($sql);

// Comptage et verification
$nbEtudiants = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
echo "Base de donnees SQLite initialisee avec succes : $cheminBase ($nbEtudiants etudiants inseres).\n";
