/**
 * ControlPanel.tsx — Panneau de contrôle utilisateur
 *
 * Affiche :
 *   - Un sélecteur <select> pour choisir l'étudiant (liste chargée au montage).
 *   - Quatre cases à cocher pour activer/désactiver chaque décorateur :
 *       [ ] MentionDecorator
 *       [ ] ClassementDecorator
 *       [ ] CacheDecorator
 *       [ ] LoggingDecorator
 *   - Un bouton "Consulter le relevé" qui déclenche l'appel API.
 *
 * Props attendues : selectedStudent, decorators, onChange callbacks, onSubmit.
 */
