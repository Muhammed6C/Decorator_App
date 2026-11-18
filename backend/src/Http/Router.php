<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Classe Router
 *
 * Role : Routeur HTTP et gestionnaire de politiques CORS pour l'API REST JSON.
 *
 * Principes de conception :
 * - Aucun effet de bord global : Cette classe ne manipule directement ni $_SERVER ni la fonction
 *   PHP native header(). Elle prend la methode, le chemin et le corps brut en parametre
 *   et retourne un triplet pur : [statutHTTP, enTetes, corpsJSON].
 *   Cette approche decouplee rend le routage immediatement testable unitairement sans demarrer de serveur HTTP.
 * - Prise en charge native du protocole CORS (Cross-Origin Resource Sharing) :
 *   Repond automatiquement au preflight OPTIONS avec le statut 204 No Content et les en-tetes adequats.
 */
final class Router
{
    /**
     * Constructeur du routeur.
     *
     * @param ReportController $controller Instance du controleur des releves
     * @param string $corsOrigin Origine autorisee pour les requetes transverses (ex: http://localhost:5173)
     */
    public function __construct(
        private readonly ReportController $controller,
        private readonly string $corsOrigin
    ) {
    }

    /**
     * Achemine la requete HTTP vers l'action de controleur correspondante.
     *
     * @param string $method Methode HTTP (GET, POST, OPTIONS, etc.)
     * @param string $path Chemin URL demande (ex: '/api/report')
     * @param string|null $rawBody Corps brut de la requete HTTP (flux php://input)
     * @return array{0: int, 1: array<string, string>, 2: string} [statut, en-tetes, corps JSON]
     */
    public function dispatch(string $method, string $path, ?string $rawBody = null): array
    {
        // En-tetes HTTP envoyes avec chaque reponse
        $enTetes = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Access-Control-Allow-Origin' => $this->corsOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
        ];

        $method = strtoupper($method);

        // 1. Traitement des requetes CORS preflight (OPTIONS)
        if ($method === 'OPTIONS') {
            return [204, $enTetes, ''];
        }

        // Normalisation du chemin URL (avec slash initial garanti)
        $path = '/' . trim($path, '/');

        // Table de correspondance des routes de l'application
        $routes = [
            'GET /api/health' => fn (): array => $this->controller->health(),
            'GET /api/students' => fn (): array => $this->controller->students(),
            'POST /api/report' => fn (): array => $this->controller->report($this->decoder($rawBody)),
            'POST /api/cache/clear' => fn (): array => $this->controller->clearCache(),
        ];

        $cleRoute = $method . ' ' . $path;

        // 2. Resolution de la route
        if (isset($routes[$cleRoute])) {
            [$statut, $corps] = $routes[$cleRoute]();
        } elseif ($this->cheminConnu($path, array_keys($routes))) {
            // Le chemin existe mais la methode HTTP demandee n'est pas supportee
            [$statut, $corps] = [
                405,
                ['error' => ['code' => 'method_not_allowed', 'message' => 'Methode non autorisee pour cette route.']],
            ];
        } else {
            // Route completement inexistante
            [$statut, $corps] = [
                404,
                ['error' => ['code' => 'not_found', 'message' => 'Route introuvable : ' . $path]],
            ];
        }

        // Serialisation propre du corps en JSON
        $reponseJson = (string) json_encode($corps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [$statut, $enTetes, $reponseJson];
    }

    /**
     * Decode une chaine JSON brute en tableau PHP associatif.
     *
     * @param string|null $rawBody Chaine de caracteres JSON brute
     * @return mixed Tableau associatif decode, ou null si la chaine est vide ou invalide
     */
    private function decoder(?string $rawBody): mixed
    {
        if ($rawBody === null || trim($rawBody) === '') {
            return null;
        }

        return json_decode($rawBody, true);
    }

    /**
     * Verifie si un chemin d'URL fait partie des routes enregistrees dans l'application.
     *
     * @param string $path Chemin d'URL a tester
     * @param list<string> $routes Liste des cles de routes "METHODE /chemin"
     * @return bool Vrai si le chemin est associe a au moins une methode
     */
    private function cheminConnu(string $path, array $routes): bool
    {
        foreach ($routes as $route) {
            if (str_ends_with($route, ' ' . $path)) {
                return true;
            }
        }

        return false;
    }
}
