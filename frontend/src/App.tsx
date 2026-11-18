import { useState } from 'react'
import { Clock, FileText, GraduationCap, Layers } from 'lucide-react'
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

function EmptyState({ isLoading }: { isLoading: boolean }) {
  return (
    <div className="bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col items-center justify-center py-20 text-center">
      {isLoading ? (
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
      <header className="bg-white border-b border-slate-200">
        <div className="max-w-screen-xl mx-auto px-4 sm:px-8 py-4 flex items-center gap-3">
          <GraduationCap className="h-5 w-5 text-slate-500 flex-shrink-0" />
          <div className="flex-1 min-w-0">
            <h1 className="text-sm font-semibold text-slate-900 leading-none">
              Portail de Consultation des Relevés
            </h1>
            <p className="text-xs text-slate-400 mt-0.5 truncate">
              Démonstration de l'empilement dynamique des couches de traitement — Design Pattern Décorateur
            </p>
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
            <EmptyState isLoading={isLoading} />
          )}
        </div>
      </main>
    </div>
  )
}
