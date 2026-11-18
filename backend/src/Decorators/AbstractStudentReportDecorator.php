<?php

declare(strict_types=1);

namespace App\Decorators;

/**
 * Classe abstraite AbstractStudentReportDecorator
 *
 * Role dans le Design Pattern Decorateur : Base Decorator (Decorateur Abstrait de Base).
 *
 * Cette classe remplit deux conditions cles du motif de conception Decorateur :
 * 1. Relation "EST UN" (is-a) : elle implemente StudentReportInterface, ce qui la rend interchangeable
 *    avec n'importe quel autre composant ou decorateur du systeme.
 * 2. Relation "A UN" (has-a) : elle maintient une reference protegee vers une instance de StudentReportInterface
 *    (le composant enveloppe ou "wrappee").
 *
 * Fonctionnement :
 * Par defaut, le decorateur abstrait retransmet purement et simplement l'appel getReport() a l'objet enveloppe.
 * Les decorateurs concrets (MentionDecorator, ClassementDecorator, CacheDecorator, LoggingDecorator)
 * heritent de cette classe et viennent y greffer leurs comportements specifiques avant ou apres
 * la delegation vers `$this->wrappee`.
 */
abstract class AbstractStudentReportDecorator implements StudentReportInterface
{
    /**
     * Constructeur recevant l'element a envelopper.
     *
     * @param StudentReportInterface $wrappee L'instance enveloppee (soit ConcreteStudentReport, soit un autre decorateur)
     */
    public function __construct(
        protected readonly StudentReportInterface $wrappee
    ) {
    }

    /**
     * Implementation par defaut : delegation directe au composant enveloppe.
     * Les sous-classes concretes peuvent surcharger cette methode pour introduire des calculs,
     * du caching, de la journalisation ou modifier la charge utile de retour.
     *
     * @param int $studentId Identifiant de l'etudiant
     * @return array<string, mixed> Donnees du releve produites par le wrappee
     */
    public function getReport(int $studentId): array
    {
        return $this->wrappee->getReport($studentId);
    }
}
