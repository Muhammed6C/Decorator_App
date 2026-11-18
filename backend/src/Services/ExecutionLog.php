<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Classe ExecutionLog
 *
 * Role : Service de capture, formatage et persistance des traces d'execution.
 *
 * Utilise principalement par LoggingDecorator, ce composant permet :
 * 1. D'enregistrer en memoire des evenements horodates avec precision microseconde.
 * 2. De formater ces traces pour l'affichage JSON renvoye au client.
 * 3. De persister les lignes de log dans un fichier journalier tournant
 *    (ex : logs/execution-2026-11-19.log) en utilisant un verrou exclusif (LOCK_EX).
 */
final class ExecutionLog
{
    /**
     * Liste des evenements enregistres durant la requete courante.
     *
     * @var list<array{time: float, source: string, message: string}>
     */
    private array $events = [];

    /**
     * Constructeur du gestionnaire de logs.
     *
     * @param string|null $directory Dossier de destination des fichiers de log (null = pas d'ecriture disque)
     */
    public function __construct(
        private readonly ?string $directory = null
    ) {
    }

    /**
     * Ajoute un nouvel evenement au journal avec l'horodatage precis actuel.
     *
     * @param string $source Origine de l'evenement (ex : 'LoggingDecorator', 'CacheDecorator')
     * @param string $message Description detaillee de l'evenement
     */
    public function add(string $source, string $message): void
    {
        $this->events[] = [
            'time' => microtime(true),
            'source' => $source,
            'message' => $message,
        ];
    }

    /**
     * Formate et retourne l'ensemble des evenements sous forme de lignes lisibles.
     *
     * Format : [HH:MM:SS.mmm] [Source] Message
     *
     * @return list<string> Liste des lignes formates
     */
    public function lines(): array
    {
        return array_map(
            static function (array $evenement): string {
                $temps = (float) $evenement['time'];
                $secondes = (int) $temps;
                $millisecondes = (int) (($temps - floor($temps)) * 1000);

                return sprintf(
                    '[%s.%03d] [%s] %s',
                    date('H:i:s', $secondes),
                    $millisecondes,
                    $evenement['source'],
                    $evenement['message']
                );
            },
            $this->events
        );
    }

    /**
     * Persiste les traces d'execution dans le fichier journalier correspondant sur le disque.
     * Si aucun dossier n'est defini ou si aucun evenement n'existe, l'operation est ignoree.
     */
    public function persist(): void
    {
        if ($this->directory === null || $this->events === []) {
            return;
        }

        // Creation recursive du repertoire de logs si inexistant
        if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true) && !is_dir($this->directory)) {
            return;
        }

        $fichierJournalier = $this->directory . DIRECTORY_SEPARATOR . 'execution-' . date('Y-m-d') . '.log';
        $contenu = implode(PHP_EOL, $this->lines()) . PHP_EOL;

        // Ecriture a la suite avec verrouillage exclusif
        file_put_contents($fichierJournalier, $contenu, FILE_APPEND | LOCK_EX);
    }
}
