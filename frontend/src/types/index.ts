/**
 * types/index.ts — Définitions TypeScript partagées
 *
 * Interfaces et types utilisés dans toute l'application frontend :
 *
 *   Student      : { id: number; nom: string; prenom: string; promotion: string }
 *   Grade        : { matiere: string; note: number; coefficient: number }
 *   ReportData   : {
 *     student: Student;
 *     grades: Grade[];
 *     moyenne?: number;
 *     mention?: string;
 *     rang?: number;
 *     taille_promotion?: number;
 *     cache_hit?: boolean;
 *     execution_time_ms?: number;
 *     logs?: LogEntry[];
 *   }
 *   LogEntry     : { timestamp: string; level: string; message: string }
 *   DecoratorKey : 'mention' | 'classement' | 'cache' | 'logging'
 */
