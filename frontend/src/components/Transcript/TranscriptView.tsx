import { BookOpen, CheckCircle2, Medal } from 'lucide-react'
import { ReportData } from '../../types'

// ─── Props ─────────────────────────────────────────────────────────────────

interface TranscriptViewProps {
  data: ReportData
}

const MENTION_FACET_POSITIONS: Record<string, string> = {
  Passable: '0% 0%',
  'Assez Bien': '100% 0%',
  Bien: '0% 100%',
  'Très Bien': '100% 100%',
}

// ─── Composant principal ───────────────────────────────────────────────────

export default function TranscriptView({ data }: TranscriptViewProps) {
  const { student, grades, moyenne, mention, rang, taillePromotion, cacheHit } = data
  const hasFooter = mention !== undefined || rang !== undefined
  const mentionFacetPosition = mention ? MENTION_FACET_POSITIONS[mention] : undefined

  return (
    <div className="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
      {/* En-tête du relevé */}
      <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div className="flex items-center gap-2.5">
          <BookOpen className="h-4 w-4 text-slate-400" />
          <div>
            <h3 className="text-sm font-semibold text-slate-900">
              {student.prenom} {student.nom}
            </h3>
            <p className="text-xs text-slate-400">{student.promotion}</p>
          </div>
        </div>

        {/* Indicateur cache hit */}
        {cacheHit === true && (
          <div className="flex items-center gap-1.5 text-xs text-emerald-600">
            <span className="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500" />
            Servi depuis le cache
          </div>
        )}
      </div>

      {/* Tableau des notes */}
      <table className="w-full">
        <thead>
          <tr className="bg-slate-50 border-b border-slate-100">
            <th className="text-left text-xs font-semibold uppercase tracking-wide text-slate-400 px-6 py-3">
              Matière
            </th>
            <th className="text-center text-xs font-semibold uppercase tracking-wide text-slate-400 px-4 py-3 w-20">
              Coeff.
            </th>
            <th className="text-right text-xs font-semibold uppercase tracking-wide text-slate-400 px-6 py-3 w-28">
              Note / 20
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-50">
          {grades.map((g, i) => (
            <tr key={i} className="hover:bg-slate-50/60 transition-colors">
              <td className="px-6 py-3 text-sm text-slate-700">{g.matiere}</td>
              <td className="px-4 py-3 text-center font-mono text-xs text-slate-400">
                {g.coefficient}
              </td>
              <td className="px-6 py-3 text-right font-mono text-sm font-semibold text-slate-900">
                {g.note.toFixed(1)}
              </td>
            </tr>
          ))}

          {/* Ligne Moyenne — injectée par MentionDecorator */}
          {moyenne !== undefined && (
            <tr className="bg-slate-50 border-t border-slate-200">
              <td className="px-6 py-3 text-sm font-semibold text-slate-900">Moyenne générale</td>
              <td className="px-4 py-3 text-center font-mono text-xs text-slate-300">—</td>
              <td className="px-6 py-3 text-right font-mono text-sm font-bold text-slate-900">
                {moyenne.toFixed(2)}
              </td>
            </tr>
          )}
        </tbody>
      </table>

      {/* Footer — Mention + Classement */}
      {hasFooter && (
        <div className="px-6 py-3 border-t border-slate-100 bg-gradient-to-r from-indigo-50/40 via-white to-emerald-50/40 flex flex-wrap items-center gap-4">
          {mention !== undefined && (
            <div className="flex min-w-0 items-center gap-3">
              {mentionFacetPosition && (
                <div
                  role="img"
                  aria-label={`Personnage souriant, facette « ${mention} »`}
                  className="h-20 w-20 flex-shrink-0 rounded-2xl border border-white bg-slate-50 shadow-sm ring-1 ring-indigo-100"
                  style={{
                    backgroundImage: "url('/images/student-mention-facets.png')",
                    backgroundPosition: mentionFacetPosition,
                    backgroundSize: '201% 201%',
                  }}
                />
              )}
              <div className="min-w-0">
                <div className="flex items-center gap-2">
                  <CheckCircle2 className="h-4 w-4 text-indigo-500" />
                  <span className="text-xs text-slate-500">Mention</span>
                </div>
                <span className="mt-0.5 block text-sm font-semibold text-slate-900">
                  {mention}
                </span>
              </div>
            </div>
          )}

          {rang !== undefined && taillePromotion !== undefined && (
            <div className="ml-auto flex flex-shrink-0 items-center gap-2">
              <Medal className="h-4 w-4 text-slate-400" />
              <span className="text-xs text-slate-500">Rang promotion</span>
              <span className="font-mono text-sm font-semibold text-slate-900">
                {rang}
                <span className="font-normal text-slate-400"> / {taillePromotion}</span>
              </span>
            </div>
          )}
        </div>
      )}
    </div>
  )
}
