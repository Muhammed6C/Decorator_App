<?php

declare(strict_types=1);

namespace App\Services;

use App\Decorators\CacheDecorator;
use App\Decorators\ClassementDecorator;
use App\Decorators\LoggingDecorator;
use App\Decorators\MentionDecorator;
use App\Decorators\StudentReportInterface;
use InvalidArgumentException;

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
    public function build(array $decorators, StudentReportInterface $base): StudentReportInterface
    {
        $cacheScope = implode('|', array_map(
            static fn ($decorator): string => (string) $decorator,
            $decorators
        ));

        foreach ($decorators as $decorator) {
            $base = match ($decorator) {
                'mention' => new MentionDecorator($base),
                'classement' => new ClassementDecorator($base),
                'cache' => new CacheDecorator($base, $cacheScope),
                'logging' => new LoggingDecorator($base),
                default => throw new InvalidArgumentException(
                    sprintf('Unknown report decorator: %s', (string) $decorator)
                ),
            };
        }

        return $base;
    }
}
