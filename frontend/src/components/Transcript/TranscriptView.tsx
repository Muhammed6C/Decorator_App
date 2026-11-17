/**
 * TranscriptView.tsx — Affichage du relevé de notes enrichi
 *
 * Reçoit en props les données retournées par l'API (objet ReportData).
 * Affiche selon les décorateurs actifs :
 *   - Tableau des notes brutes par matière (toujours affiché)
 *   - Moyenne générale et mention  (si MentionDecorator actif)
 *   - Rang dans la promotion       (si ClassementDecorator actif)
 *   - Badge "Résultat depuis le cache" (si CacheDecorator actif + cache_hit: true)
 *   - Chronomètre d'exécution en ms (si LoggingDecorator actif)
 *
 * Affiche un état de chargement (skeleton) pendant la requête.
 */
