<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Router
 *
 * Routeur HTTP minimaliste en Vanilla PHP.
 * Parse l'URI et la méthode HTTP de la requête entrante,
 * puis dispatche vers le contrôleur approprié.
 * Retourne systématiquement une réponse JSON (Content-Type: application/json).
 * Gère les en-têtes CORS pour autoriser les requêtes depuis le frontend Vite.
 */
class Router
{
    // get(string $path, callable $handler): void
    // post(string $path, callable $handler): void
    // dispatch(): void
}
