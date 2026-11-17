<?php

declare(strict_types=1);

namespace App\Http;

/**
 * ReportController
 *
 * Contrôleur unique de l'API.
 * Reçoit la requête POST /api/report contenant l'identifiant étudiant
 * et la liste des décorateurs à activer.
 * Délègue la construction de la pile au DecoratorBuilder,
 * puis appelle getReport() et retourne la réponse JSON encodée.
 */
class ReportController
{
    // Dépend de DecoratorBuilder et de ConcreteStudentReport.
    // handleRequest(): void — point d'entrée HTTP.
}
