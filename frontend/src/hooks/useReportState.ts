import { useState, useRef } from 'react'
import { Student, Grade, ReportData, DecoratorState, DecoratorKey, LogEntry } from '../types'

// ─────────────────────────────────────────────────────────────────────────────
// Données de démonstration (remplacées par l'API PHP en production)
// ─────────────────────────────────────────────────────────────────────────────

const MOCK_STUDENTS: Student[] = [
  { id: 1, nom: 'Martin',   prenom: 'Sophie',   promotion: 'L3 Informatique — 2024' },
  { id: 2, nom: 'Dubois',   prenom: 'Julien',   promotion: 'L3 Informatique — 2024' },
  { id: 3, nom: 'Chen',     prenom: 'Léna',     promotion: 'L3 Informatique — 2024' },
  { id: 4, nom: 'Rousseau', prenom: 'Antoine',  promotion: 'L3 Informatique — 2024' },
]

const MOCK_GRADES: Record<number, Grade[]> = {
  1: [
    { matiere: 'Algorithmique avancée',    note: 15.5, coefficient: 4 },
    { matiere: 'Bases de données',         note: 13.0, coefficient: 3 },
    { matiere: 'Réseaux & Protocoles',     note: 11.5, coefficient: 2 },
    { matiere: 'Mathématiques discrètes', note: 14.0, coefficient: 3 },
    { matiere: 'Développement Web',        note: 16.0, coefficient: 3 },
    { matiere: "Systèmes d'exploitation", note: 12.5, coefficient: 2 },
  ],
  2: [
    { matiere: 'Algorithmique avancée',    note: 11.0, coefficient: 4 },
    { matiere: 'Bases de données',         note: 14.5, coefficient: 3 },
    { matiere: 'Réseaux & Protocoles',     note: 13.0, coefficient: 2 },
    { matiere: 'Mathématiques discrètes', note: 10.5, coefficient: 3 },
    { matiere: 'Développement Web',        note: 12.0, coefficient: 3 },
    { matiere: "Systèmes d'exploitation", note: 15.0, coefficient: 2 },
  ],
  3: [
    { matiere: 'Algorithmique avancée',    note: 17.5, coefficient: 4 },
    { matiere: 'Bases de données',         note: 16.0, coefficient: 3 },
    { matiere: 'Réseaux & Protocoles',     note: 15.0, coefficient: 2 },
    { matiere: 'Mathématiques discrètes', note: 18.0, coefficient: 3 },
    { matiere: 'Développement Web',        note: 14.5, coefficient: 3 },
    { matiere: "Systèmes d'exploitation", note: 16.5, coefficient: 2 },
  ],
  4: [
    { matiere: 'Algorithmique avancée',    note: 8.5,  coefficient: 4 },
    { matiere: 'Bases de données',         note: 10.0, coefficient: 3 },
    { matiere: 'Réseaux & Protocoles',     note: 9.0,  coefficient: 2 },
    { matiere: 'Mathématiques discrètes', note: 11.5, coefficient: 3 },
    { matiere: 'Développement Web',        note: 10.5, coefficient: 3 },
    { matiere: "Systèmes d'exploitation", note: 9.5,  coefficient: 2 },
  ],
}

const MOCK_RANKINGS: Record<number, { rang: number; taille: number }> = {
  1: { rang: 8,  taille: 32 },
  2: { rang: 18, taille: 32 },
  3: { rang: 2,  taille: 32 },
  4: { rang: 29, taille: 32 },
}

// ─────────────────────────────────────────────────────────────────────────────
// Fonctions utilitaires métier
// ─────────────────────────────────────────────────────────────────────────────

function computeMoyenne(grades: Grade[]): number {
  const totalCoeff   = grades.reduce((s, g) => s + g.coefficient, 0)
  const weightedSum  = grades.reduce((s, g) => s + g.note * g.coefficient, 0)
  return Math.round((weightedSum / totalCoeff) * 100) / 100
}

function getMention(moyenne: number): string {
  if (moyenne >= 16) return 'Très Bien'
  if (moyenne >= 14) return 'Bien'
  if (moyenne >= 12) return 'Assez Bien'
  if (moyenne >= 10) return 'Passable'
  return 'Insuffisant'
}

function getTimestamp(): string {
  const d = new Date()
  return (
    String(d.getHours()).padStart(2, '0') + ':' +
    String(d.getMinutes()).padStart(2, '0') + ':' +
    String(d.getSeconds()).padStart(2, '0') + '.' +
    String(d.getMilliseconds()).padStart(3, '0')
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Hook principal — état global de l'application
// ─────────────────────────────────────────────────────────────────────────────

export function useReportState() {
  const [selectedStudentId, setSelectedStudentId] = useState<number>(1)
  const [decorators, setDecorators] = useState<DecoratorState>({
    mention:    false,
    classement: false,
    cache:      false,
    logging:    false,
  })
  const [isLoading,  setIsLoading]  = useState(false)
  const [reportData, setReportData] = useState<ReportData | null>(null)

  /**
   * Cache en mémoire simulant le CacheDecorator côté PHP.
   * Clé : identifiant étudiant. Valeur : données brutes mémorisées.
   * On utilise une ref pour ne pas déclencher de re-render à l'écriture.
   */
  const cacheStore = useRef<Record<string, { student: Student; grades: Grade[] }>>({})

  const handleStudentChange = (id: number) => {
    setSelectedStudentId(id)
    setReportData(null) // Réinitialise l'affichage lors du changement d'étudiant
  }

  const toggleDecorator = (key: DecoratorKey) => {
    setDecorators(prev => ({ ...prev, [key]: !prev[key] }))
  }

  /**
   * Simule l'exécution de la pile de décorateurs construite dynamiquement
   * par DecoratorBuilder côté backend PHP.
   * Les délais reproduisent fidèlement la latence BDD et le gain du cache.
   */
  const handleSubmit = async () => {
    setIsLoading(true)
    setReportData(null)

    const dec     = decorators
    const student = MOCK_STUDENTS.find(s => s.id === selectedStudentId)!
    const grades  = MOCK_GRADES[selectedStudentId]
    const { rang, taille } = MOCK_RANKINGS[selectedStudentId]
    const moyenne = computeMoyenne(grades)
    const mention = getMention(moyenne)

    const cacheKey  = String(selectedStudentId)
    const cached    = dec.cache ? cacheStore.current[cacheKey] : undefined
    const isCacheHit = !!cached

    const logs: LogEntry[] = []
    const addLog = (level: LogEntry['level'], message: string) =>
      logs.push({ timestamp: getTimestamp(), level, message })

    const t0 = performance.now()

    // ── Traversée de la pile — ordre extérieur → intérieur ──────────────────

    if (dec.logging) {
      addLog('info', 'LoggingDecorator → interception de la requête entrante')
    }

    if (dec.cache) {
      addLog('info', `CacheDecorator → vérification du cache pour l'étudiant #${selectedStudentId}`)
      if (isCacheHit) {
        addLog('hit', 'CacheDecorator → entrée valide trouvée — court-circuit de la chaîne')
        await new Promise(r => setTimeout(r, 12 + Math.random() * 18))
      } else {
        addLog('miss', 'CacheDecorator → cache vide — délégation vers la couche suivante')
      }
    }

    if (!isCacheHit) {
      if (dec.classement) {
        addLog('info', 'ClassementDecorator → requête agrégée sur la promotion en cours...')
      }
      if (dec.mention) {
        addLog('info', 'MentionDecorator → calcul de la moyenne pondérée...')
      }

      // Latence simulée de la base de données
      await new Promise(r => setTimeout(r, 650 + Math.random() * 550))

      addLog('info', `ConcreteStudentReport → ${grades.length} notes récupérées depuis la base de données`)

      if (dec.mention) {
        addLog('info', `MentionDecorator → moyenne = ${moyenne.toFixed(2)} → mention « ${mention} »`)
      }
      if (dec.classement) {
        addLog('info', `ClassementDecorator → rang ${rang}/${taille} dans la promotion`)
      }
      if (dec.cache) {
        cacheStore.current[cacheKey] = { student, grades }
        addLog('stored', `CacheDecorator → résultat stocké en cache (clé : student_${cacheKey})`)
      }
    }

    const executionTimeMs = performance.now() - t0

    if (dec.logging) {
      addLog('timing', `LoggingDecorator → temps total d'exécution : ${executionTimeMs.toFixed(1)} ms`)
    }

    // ── Construction de la chaîne affichée dans la barre de métriques ───────
    const chain: string[] = ['Base']
    if (dec.mention)    chain.push('Mention')
    if (dec.classement) chain.push('Classement')
    if (dec.cache)      chain.push('Cache')
    if (dec.logging)    chain.push('Logging')

    setReportData({
      student,
      grades,
      moyenne:          dec.mention    ? moyenne : undefined,
      mention:          dec.mention    ? mention : undefined,
      rang:             dec.classement ? rang    : undefined,
      taillePromotion:  dec.classement ? taille  : undefined,
      cacheHit:         dec.cache      ? isCacheHit : undefined,
      executionTimeMs,
      logs:             dec.logging    ? logs    : undefined,
      decoratorChain:   chain,
    })

    setIsLoading(false)
  }

  return {
    students: MOCK_STUDENTS,
    selectedStudentId,
    handleStudentChange,
    decorators,
    toggleDecorator,
    reportData,
    isLoading,
    handleSubmit,
  }
}
