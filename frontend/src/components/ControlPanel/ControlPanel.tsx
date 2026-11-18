import { Award, BarChart2, ChevronDown, Database, Loader2, Play, Terminal, User } from 'lucide-react'
import { DecoratorKey, DecoratorState, Student } from '../../types'

// ─── Configuration des décorateurs affichés dans le panneau ───────────────

const DECORATOR_ITEMS: {
  key: DecoratorKey
  label: string
  description: string
  Icon: React.ElementType
}[] = [
  {
    key: 'mention',
    label: 'Calcul de la Mention',
    description: 'Moyenne générale et appréciation',
    Icon: Award,
  },
  {
    key: 'classement',
    label: 'Classement Promotion',
    description: 'Positionnement par rapport aux autres étudiants',
    Icon: BarChart2,
  },
  {
    key: 'cache',
    label: 'Mise en Cache',
    description: 'Mémorisation du résultat en mémoire',
    Icon: Database,
  },
  {
    key: 'logging',
    label: 'Journalisation',
    description: 'Traçage détaillé et mesure du temps',
    Icon: Terminal,
  },
]

// ─── Composant Toggle ──────────────────────────────────────────────────────

function Toggle({ active, label, onToggle, disabled }: { active: boolean; label: string; onToggle: () => void; disabled: boolean }) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={active}
      aria-label={label}
      disabled={disabled}
      onClick={onToggle}
      className={`
        relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent
        transition-colors duration-200 ease-in-out
        focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2
        disabled:cursor-not-allowed disabled:opacity-60
        ${active ? 'bg-indigo-600' : 'bg-slate-200'}
      `}
    >
      <span
        className={`
          pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm
          transition-transform duration-200 ease-in-out
          ${active ? 'translate-x-4' : 'translate-x-0'}
        `}
      />
    </button>
  )
}

// ─── Props ─────────────────────────────────────────────────────────────────

interface ControlPanelProps {
  students: Student[]
  selectedStudentId: number
  onStudentChange: (id: number) => void
  decorators: DecoratorState
  onDecoratorToggle: (key: DecoratorKey) => void
  onSubmit: () => void
  isLoading: boolean
}

// ─── Composant principal ───────────────────────────────────────────────────

export default function ControlPanel({
  students,
  selectedStudentId,
  onStudentChange,
  decorators,
  onDecoratorToggle,
  onSubmit,
  isLoading,
}: ControlPanelProps) {
  return (
    <div className="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
      {/* En-tête du panneau */}
      <div className="px-6 py-4 border-b border-slate-100">
        <p className="text-xs font-semibold uppercase tracking-widest text-slate-400">
          Configuration
        </p>
        <h2 className="mt-0.5 text-sm font-semibold text-slate-900">
          Paramètres de la requête
        </h2>
      </div>

      <div className="px-6 py-5 space-y-5">
        {/* Sélecteur d'étudiant */}
        <div className="space-y-1.5">
          <label htmlFor="student" className="text-xs font-medium text-slate-500">Étudiant</label>
          <div className="relative">
            <User className="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
            <select
              id="student"
              value={selectedStudentId}
              disabled={isLoading}
              onChange={e => onStudentChange(Number(e.target.value))}
              className="
                w-full appearance-none bg-white border border-slate-200 rounded-lg
                pl-8 pr-8 py-2.5 text-sm text-slate-900
                focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                transition-shadow
                disabled:cursor-not-allowed disabled:bg-slate-50
              "
            >
              {students.map(s => (
                <option key={s.id} value={s.id}>
                  {s.prenom} {s.nom}
                </option>
              ))}
            </select>
            <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
          </div>
        </div>

        {/* Séparateur */}
        <div className="border-t border-slate-100" />

        {/* Liste des décorateurs */}
        <div className="space-y-1">
          <p className="text-xs font-medium text-slate-500 mb-3">Couches de traitement</p>

          <ul className="space-y-0.5">
            {DECORATOR_ITEMS.map(({ key, label, description, Icon }) => {
              const active = decorators[key]
              return (
                <li
                  key={key}
                  className={`
                    flex items-center gap-3 px-3 py-3 rounded-lg
                    transition-colors duration-150
                    ${active ? 'bg-slate-50' : 'hover:bg-slate-50'}
                  `}
                >
                  <Icon
                    className={`h-4 w-4 flex-shrink-0 ${active ? 'text-slate-500' : 'text-slate-300'}`}
                  />
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium leading-none text-slate-700">
                      {label}
                    </p>
                    <p className="text-xs text-slate-400 mt-0.5 truncate">{description}</p>
                  </div>
                  <Toggle active={active} label={label} onToggle={() => onDecoratorToggle(key)} disabled={isLoading} />
                </li>
              )
            })}
          </ul>
        </div>

        {/* Séparateur */}
        <div className="border-t border-slate-100" />

        {/* Bouton principal */}
        <button
          onClick={onSubmit}
          disabled={isLoading}
          className="
            w-full flex items-center justify-center gap-2
            bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400
            text-white text-sm font-semibold
            py-2.5 px-4 rounded-lg
            transition-colors duration-150
            focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2
            disabled:cursor-not-allowed
          "
        >
          {isLoading ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              Exécution en cours…
            </>
          ) : (
            <>
              <Play className="h-4 w-4" />
              Consulter le relevé
            </>
          )}
        </button>
      </div>
    </div>
  )
}
