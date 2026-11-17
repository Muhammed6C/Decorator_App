/**
 * reportService.ts — Service d'appel API
 *
 * Encapsule toute communication HTTP avec le backend PHP.
 * Expose deux fonctions :
 *
 *   fetchStudents(): Promise<Student[]>
 *     GET /api/students — Récupère la liste des étudiants pour le <select>.
 *
 *   fetchReport(studentId: number, decorators: string[]): Promise<ReportData>
 *     POST /api/report — Envoie l'identifiant étudiant et la liste des
 *     décorateurs actifs, retourne les données enrichies du relevé.
 *
 * Gère les erreurs HTTP et les timeout.
 * Utilise l'API Fetch native (pas d'axios).
 */
