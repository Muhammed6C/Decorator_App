/**
 * LogConsole.tsx — Console de logs en temps réel
 *
 * Affiche les traces d'exécution retournées par LoggingDecorator
 * sous forme de terminal scrollable (police monospace, fond sombre).
 * Chaque entrée de log indique : timestamp, décorateur concerné,
 * durée partielle et message descriptif.
 * Propose un bouton "Effacer" pour vider l'historique affiché.
 *
 * Ne s'affiche (ou ne montre de contenu) que si LoggingDecorator est actif.
 */
