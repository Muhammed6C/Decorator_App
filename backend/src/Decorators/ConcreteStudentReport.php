<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * ConcreteStudentReport (Composant Concret)
 *
 * Implémentation de base de StudentReportInterface.
 * Responsable uniquement de la récupération des notes brutes
 * depuis la base de données via PDO, avec une latence simulée.
 * N'applique aucune transformation ni enrichissement des données.
 */
class ConcreteStudentReport implements StudentReportInterface
{
    // Dépend de DatabaseConnection pour exécuter les requêtes PDO.
    // Simule une latence réseau/BDD avec usleep().
}
