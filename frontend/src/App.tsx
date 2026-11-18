import { useState } from 'react'
import { Clock, FileText, GraduationCap, Heart, Layers, RefreshCw } from 'lucide-react'
import { useReportState } from './hooks/useReportState'
import { ReportData } from './types'
import ControlPanel from './components/ControlPanel/ControlPanel'
import TranscriptView from './components/Transcript/TranscriptView'
import LogConsole from './components/LogConsole/LogConsole'

// ─────────────────────────────────────────────────────────────────────────────
// Barre de métriques d'exécution
// ─────────────────────────────────────────────────────────────────────────────

function MetricsBar({ data }: { data: ReportData }) {
  const { executionTimeMs, cacheHit, decoratorChain } = data
  const timeInSeconds = (executionTimeMs / 1000).toFixed(3)
  const isFast = cacheHit === true
  const isSlow = !isFast && executionTimeMs >= 1000
  const timeColor = isFast
    ? 'text-emerald-600'
    : isSlow
      ? 'text-red-600'
      : 'text-slate-900'

  return (
    <div className="bg-white border border-slate-200 rounded-xl shadow-sm px-5 py-3.5 flex items-center gap-6">
      {/* Temps d'exécution */}
      <div className="flex items-center gap-2 flex-shrink-0">
        <Clock className={`h-4 w-4 ${isFast ? 'text-emerald-600' : isSlow ? 'text-red-600' : 'text-slate-400'}`} />
        <span className="text-xs font-medium text-slate-500 uppercase tracking-wide">Temps</span>
        <span className={`font-mono text-sm font-semibold ${timeColor}`}>
          {timeInSeconds} s
        </span>
      </div>

      {/* Diviseur vertical */}
      <div className="h-4 w-px bg-slate-200 flex-shrink-0" />

      {/* Chaîne des décorateurs traversés */}
      <div className="flex items-center gap-2 min-w-0">
        <Layers className="h-4 w-4 text-slate-400 flex-shrink-0" />
        <span className="text-xs font-medium text-slate-500 uppercase tracking-wide flex-shrink-0">
          Couches
        </span>
        <div className="flex items-center gap-1 overflow-x-auto">
          {decoratorChain.map((name, i) => (
            <span key={name} className="flex items-center gap-1">
              {i > 0 && <span className="text-slate-300 font-mono text-xs">→</span>}
              <span
                className={`font-mono text-xs font-medium whitespace-nowrap ${
                  'text-slate-600'
                }`}
              >
                {name}
              </span>
            </span>
          ))}
        </div>
      </div>
    </div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// État vide — avant la première requête
// ─────────────────────────────────────────────────────────────────────────────

function EmptyState({
  isLoading,
  isLoadingStudents,
  error,
  onRetry,
}: {
  isLoading: boolean
  isLoadingStudents: boolean
  error: string | null
  onRetry?: () => void
}) {
  return (
    <div className="bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col items-center justify-center py-20 text-center">
      {error ? (
        <>
          <FileText className="h-10 w-10 text-red-300 mb-4" />
          <p role="alert" className="text-sm font-medium text-red-700">{error}</p>
          <p className="text-xs text-slate-400 mt-1">
            Vérifiez que le backend PHP et la base SQLite sont disponibles.
          </p>
          {onRetry && (
            <button
              type="button"
              onClick={onRetry}
              disabled={isLoadingStudents}
              className="mt-4 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <RefreshCw className={`h-4 w-4 ${isLoadingStudents ? 'animate-spin' : ''}`} />
              Réessayer la connexion
            </button>
          )}
        </>
      ) : isLoadingStudents ? (
        <>
          <div className="relative mb-5">
            <div className="h-10 w-10 rounded-full border-2 border-slate-100 border-t-indigo-500 animate-spin" />
          </div>
          <p className="text-sm font-medium text-slate-600">Chargement des étudiants…</p>
          <p className="text-xs text-slate-400 mt-1">Lecture depuis la base de données</p>
        </>
      ) : isLoading ? (
        <>
          <div className="relative mb-5">
            <div className="h-10 w-10 rounded-full border-2 border-slate-100 border-t-indigo-500 animate-spin" />
          </div>
          <p className="text-sm font-medium text-slate-600">Exécution de la pile en cours…</p>
          <p className="text-xs text-slate-400 mt-1">Les décorateurs traitent la requête</p>
        </>
      ) : (
        <>
          <FileText className="h-10 w-10 text-slate-200 mb-4" />
          <p className="text-sm font-medium text-slate-500">Aucun relevé chargé</p>
          <p className="text-xs text-slate-400 mt-1">
            Sélectionnez un étudiant et lancez la consultation
          </p>
        </>
      )}
    </div>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// App — composant racine
// ─────────────────────────────────────────────────────────────────────────────

export default function App() {
  const {
    students,
    selectedStudentId,
    handleStudentChange,
    decorators,
    toggleDecorator,
    reportData,
    isLoading,
    isLoadingStudents,
    error,
    retryLoadStudents,
    handleSubmit,
  } = useReportState()

  /**
   * Les logs sont gérés localement dans App pour permettre le bouton "Effacer"
   * sans toucher aux données canoniques du reportData.
   */
  const [clearedLogs, setClearedLogs] = useState(false)

  // Réinitialise l'état "effacé" lors d'une nouvelle requête
  const handleSubmitWithReset = async () => {
    setClearedLogs(false)
    await handleSubmit()
  }

  const visibleLogs = reportData?.logs
    ? clearedLogs
      ? []
      : reportData.logs
    : undefined

  return (
    <div className="min-h-screen bg-slate-50 font-sans">
      {/* ── En-tête ─────────────────────────────────────────────────────── */}
      <header className="relative isolate overflow-hidden border-b border-indigo-100/80 bg-gradient-to-r from-white via-indigo-50/70 to-emerald-50/60 shadow-sm">
        <div
          aria-hidden="true"
          className="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-600 via-violet-500 to-emerald-400"
        />
        <div
          aria-hidden="true"
          className="pointer-events-none absolute -right-24 -top-32 h-72 w-72 rounded-full bg-indigo-200/30 blur-3xl"
        />
        <div className="relative mx-auto flex max-w-screen-xl items-center justify-between gap-6 px-4 py-4 sm:px-8 sm:py-5">
          <div className="flex min-w-0 items-center gap-4">
            <div className="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white shadow-lg shadow-indigo-200/70 ring-1 ring-white/80">
              <GraduationCap aria-hidden="true" className="h-7 w-7" />
            </div>
            <div className="min-w-0">
              <p className="mb-1.5 flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-600">
                <span aria-hidden="true" className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                Espace académique
              </p>
              <h1 className="text-lg font-semibold leading-tight tracking-tight text-slate-950 sm:text-xl">
                Portail de consultation des relevés
              </h1>
              <p className="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm">
                Un espace clair pour suivre les résultats et accompagner chaque progression.
              </p>
              <div className="mt-2.5 inline-flex max-w-full items-center gap-1.5 rounded-full border border-indigo-100/80 bg-white/70 px-2.5 py-1 text-[10px] font-medium text-slate-500">
                <Layers aria-hidden="true" className="h-3 w-3 flex-shrink-0 text-indigo-400" />
                <span className="truncate">
                  Démonstration pédagogique <span aria-hidden="true">·</span> Design pattern Décorateur
                </span>
              </div>
            </div>
          </div>

          <div className="hidden flex-shrink-0 items-center gap-3 rounded-2xl border border-white/80 bg-white/65 px-4 py-3 shadow-sm sm:flex">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-500">
              <Heart aria-hidden="true" className="h-5 w-5" />
            </div>
            <div>
              <p className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                Un suivi bienveillant
              </p>
              <p className="mt-0.5 text-sm font-semibold text-slate-700">Chaque parcours compte.</p>
            </div>
          </div>
        </div>
      </header>

      {/* ── Grille principale — 35 % / 65 % ─────────────────────────────── */}
      <main className="max-w-screen-xl mx-auto px-4 sm:px-8 py-6 grid grid-cols-1 lg:grid-cols-[7fr_13fr] gap-6 items-start">
        {/* Colonne gauche — Configuration */}
        <ControlPanel
          students={students}
          selectedStudentId={selectedStudentId}
          onStudentChange={handleStudentChange}
          decorators={decorators}
          onDecoratorToggle={toggleDecorator}
          onSubmit={handleSubmitWithReset}
          isLoading={isLoading}
          isLoadingStudents={isLoadingStudents}
        />

        {/* Colonne droite — Résultats */}
        <div className="space-y-4">
          {reportData && !isLoading ? (
            <>
              <MetricsBar data={reportData} />
              <TranscriptView data={reportData} />
              {visibleLogs && (
                <LogConsole
                  logs={visibleLogs}
                  onClear={() => setClearedLogs(true)}
                />
              )}
            </>
          ) : (
            <EmptyState
              isLoading={isLoading}
              isLoadingStudents={isLoadingStudents}
              error={error}
              onRetry={students.length === 0 ? retryLoadStudents : undefined}
            />
          )}
        </div>
      </main>
    </div>
  )
}
