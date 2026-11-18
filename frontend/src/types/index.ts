// ─── Entités domaine ───────────────────────────────────────────────────────

export interface Student {
  id: number
  nom: string
  prenom: string
  promotion: string
}

export interface Grade {
  matiere: string
  note: number
  coefficient: number
}

// ─── Pattern Décorateur — état et clés ────────────────────────────────────

export type DecoratorKey = 'mention' | 'classement' | 'cache' | 'logging'

export interface DecoratorState {
  mention: boolean
  classement: boolean
  cache: boolean
  logging: boolean
}

// ─── Logs de la console d'exécution ───────────────────────────────────────

export type LogLevel = 'info' | 'hit' | 'miss' | 'stored' | 'timing'

export interface LogEntry {
  timestamp: string
  level: LogLevel
  message: string
}

// ─── Résultat enrichi retourné par la pile de décorateurs ─────────────────

export interface ReportData {
  student: Student
  grades: Grade[]
  /** Calculée par MentionDecorator */
  moyenne?: number
  /** Déterminée par MentionDecorator */
  mention?: string
  /** Calculé par ClassementDecorator */
  rang?: number
  taillePromotion?: number
  /** true si CacheDecorator a servi depuis le cache */
  cacheHit?: boolean
  /** Mesuré systématiquement côté client */
  executionTimeMs: number
  /** Traces de LoggingDecorator — undefined si désactivé */
  logs?: LogEntry[]
  /** Chaîne de décorateurs traversés en ordre d'exécution */
  decoratorChain: string[]
}
