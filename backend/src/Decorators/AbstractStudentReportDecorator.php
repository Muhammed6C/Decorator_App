<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * AbstractStudentReportDecorator (Décorateur Abstrait)
 *
 * Classe de base pour tous les décorateurs concrets.
 * Maintient une référence au composant décoré (wrappee) via
 * l'interface StudentReportInterface, garantissant la transparence
 * de l'empilement et la délégation de l'appel au composant suivant.
 */
abstract class AbstractStudentReportDecorator implements StudentReportInterface
{
    // protected StudentReportInterface $wrappee;
    // __construct() reçoit le composant à décorer.
    // getReport() délègue à $this->wrappee->getReport() avant d'enrichir.
}
