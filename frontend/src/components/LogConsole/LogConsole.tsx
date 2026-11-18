import { useEffect, useRef } from 'react'
import { Terminal, Trash2 } from 'lucide-react'
import { LogEntry } from '../../types'

// ─── Mapping visuel des niveaux de log ────────────────────────────────────

const LEVEL_CONFIG: Record<
  LogEntry['level'],
  { label: string; className: string }
> = {
  info:   { label: 'INFO ',  className: 'text-slate-400' },
  hit:    { label: 'HIT  ',  className: 'text-emerald-400' },
  miss:   { label: 'MISS ',  className: 'text-amber-400' },
  stored: { label: 'STORE',  className: 'text-blue-400' },
  timing: { label: 'TIME ',  className: 'text-yellow-400' },
}

// ─── Props ─────────────────────────────────────────────────────────────────

interface LogConsoleProps {
  logs: LogEntry[]
  onClear?: () => void
}

// ─── Composant principal ───────────────────────────────────────────────────

export default function LogConsole({ logs, onClear }: LogConsoleProps) {
  const scrollRef = useRef<HTMLDivElement>(null)

  // Défilement automatique vers le bas à chaque nouvel entrée
  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight
    }
  }, [logs])

  return (
    <div className="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden shadow-sm">
      {/* Barre de titre du terminal */}
      <div className="flex items-center justify-between px-4 py-2.5 border-b border-slate-800">
        <div className="flex items-center gap-2">
          <Terminal className="h-3.5 w-3.5 text-slate-500" />
          <span className="text-xs font-medium text-slate-500 tracking-wide">
            Console d'exécution
          </span>
        </div>
        {onClear && (
          <button
            onClick={onClear}
            className="flex items-center gap-1 text-xs text-slate-600 hover:text-slate-400 transition-colors"
          >
            <Trash2 className="h-3 w-3" />
            Effacer
          </button>
        )}
      </div>

      {/* Corps du terminal */}
      <div
        ref={scrollRef}
        className="console-scroll p-4 max-h-52 overflow-y-auto space-y-1 font-mono text-xs"
      >
        {logs.length === 0 ? (
          <p className="text-slate-600 italic">Aucune trace disponible.</p>
        ) : (
          logs.map((entry, i) => {
            const cfg = LEVEL_CONFIG[entry.level]
            return (
              <div key={i} className="flex gap-3 leading-relaxed">
                {/* Horodatage */}
                <span className="text-green-400 flex-shrink-0 select-none">
                  {entry.timestamp}
                </span>
                {/* Niveau */}
                <span className={`flex-shrink-0 select-none ${cfg.className}`}>
                  {cfg.label}
                </span>
                {/* Message */}
                <span className="text-slate-300 break-all">{entry.message}</span>
              </div>
            )
          })
        )}
      </div>
    </div>
  )
}
