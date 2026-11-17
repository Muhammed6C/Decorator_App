<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * MentionDecorator (Décorateur Métier n°1)
 *
 * Étend AbstractStudentReportDecorator.
 * Après avoir récupéré les données du composant décoré, calcule
 * la moyenne générale à partir des notes brutes et détermine
 * la mention correspondante (Très Bien, Bien, Assez Bien, Passable).
 * Ajoute les clés 'moyenne' et 'mention' au tableau de résultats.
 */
class MentionDecorator extends AbstractStudentReportDecorator
{
    // Logique : calcul de moyenne + seuils de mention.
}
