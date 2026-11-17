<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * ClassementDecorator (Décorateur Métier n°2)
 *
 * Étend AbstractStudentReportDecorator.
 * Compare la moyenne de l'étudiant avec celle de l'ensemble
 * de sa promotion (requête agrégée en BDD) pour calculer et
 * injecter son rang dans le tableau de résultats.
 * Ajoute les clés 'rang' et 'taille_promotion'.
 */
class ClassementDecorator extends AbstractStudentReportDecorator
{
    // Dépend d'un accès base de données pour récupérer les moyennes de la promo.
}
