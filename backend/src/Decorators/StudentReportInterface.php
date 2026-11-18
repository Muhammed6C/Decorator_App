<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * Interface StudentReportInterface
 *
 * Role dans le Design Pattern Decorateur : Component Interface.
 *
 * Cette interface definit le contrat commun a tous les objets composant le
 * systeme de releves de notes, qu'il s'agisse du composant de base concret
 * (ConcreteStudentReport) ou des differents decorateurs qui l'enrichissent
 * (MentionDecorator, ClassementDecorator, CacheDecorator, LoggingDecorator).
 *
 * Grace a cette interface commune, le client (controleur, routeur ou service)
 * traite de maniere totalement transparente un objet brut ou un objet enveloppe
 * ("wrapped") par un ou plusieurs decorateurs.
 */
interface StudentReportInterface
{
    /**
     * Recupere et retourne le releve de notes complet d'un etudiant.
     *
     * Selon les decorateurs actifs dans la chaine de responsabilite, le tableau
     * associatif retourne contiendra les donnees brutes (etudiant, notes) et
     * pourra etre enrichi dynamiquement avec :
     * - la moyenne ponderee et la mention academique (MentionDecorator)
     * - le rang au sein de la promotion et l'effectif total (ClassementDecorator)
     * - les informations de statut de cache HIT/MISS (CacheDecorator)
     * - les traces d'execution et temps de traitement (LoggingDecorator)
     *
     * @param int $studentId Identifiant unique de l'etudiant
     * @return array<string, mixed> Structure de donnees contenant les informations du releve
     *
     * @throws \OutOfBoundsException Si l'etudiant demande n'existe pas en base de donnees
     */
    public function getReport(int $studentId): array;
}
