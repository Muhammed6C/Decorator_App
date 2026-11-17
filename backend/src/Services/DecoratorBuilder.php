<?php

declare(strict_types=1);

namespace App\Services;

/**
 * DecoratorBuilder
 *
 * Service Factory/Builder chargé de construire dynamiquement
 * la pile de décorateurs selon la liste reçue dans la requête HTTP.
 * Reçoit le composant de base (ConcreteStudentReport) et enveloppe
 * les décorateurs dans l'ordre défini par la requête entrante.
 * Point d'entrée de composition du pattern à l'exécution (Runtime).
 */
class DecoratorBuilder
{
    // build(array $decorators, StudentReportInterface $base): StudentReportInterface
    // Itère sur $decorators et enveloppe successivement.
}
