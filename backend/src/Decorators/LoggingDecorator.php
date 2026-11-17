<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * LoggingDecorator (Décorateur Technique n°4)
 *
 * Étend AbstractStudentReportDecorator.
 * Chronomètre l'appel au composant décoré via microtime().
 * Enregistre dans un fichier de log (PSR-3-like) : timestamp,
 * studentId, durée d'exécution, et les décorateurs actifs dans la pile.
 * Injecte les clés 'logs' (tableau de traces) et 'execution_time_ms'
 * dans le tableau de résultats retourné.
 */
class LoggingDecorator extends AbstractStudentReportDecorator
{
    // Écriture dans /logs/app.log via file_put_contents() en mode append.
}
