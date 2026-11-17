/**
 * useReportState.ts — Hook personnalisé de gestion d'état
 *
 * Centralise toute la logique d'état de l'application :
 *   - selectedStudent : étudiant actuellement sélectionné
 *   - activeDecorators : tableau des décorateurs cochés
 *   - reportData : données retournées par l'API
 *   - isLoading : état de chargement pendant la requête
 *   - error : message d'erreur éventuel
 *
 * Expose les handlers :
 *   - handleStudentChange(id: number): void
 *   - handleDecoratorToggle(name: string): void
 *   - handleSubmit(): Promise<void> — appelle reportService.fetchReport()
 *
 * Sépare proprement la logique métier de la présentation.
 */
