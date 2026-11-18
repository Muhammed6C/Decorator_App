import { useCallback, useEffect, useState } from 'react'
import { fetchReport, fetchStudents } from '../services/reportService'
import { DecoratorKey, DecoratorState, ReportData, Student } from '../types'

const DECORATOR_KEYS: DecoratorKey[] = ['mention', 'classement', 'cache', 'logging']

function getErrorMessage(error: unknown): string {
  return error instanceof Error ? error.message : 'Une erreur inattendue est survenue.'
}

export function useReportState() {
  const [students, setStudents] = useState<Student[]>([])
  const [selectedStudentId, setSelectedStudentId] = useState(0)
  const [decorators, setDecorators] = useState<DecoratorState>({
    mention: false,
    classement: false,
    cache: false,
    logging: false,
  })
  const [isLoadingStudents, setIsLoadingStudents] = useState(true)
  const [isLoading, setIsLoading] = useState(false)
  const [reportData, setReportData] = useState<ReportData | null>(null)
  const [error, setError] = useState<string | null>(null)

  const loadStudents = useCallback(async (signal?: AbortSignal) => {
    setIsLoadingStudents(true)
    setError(null)

    try {
      const loadedStudents = await fetchStudents(signal)
      if (signal?.aborted) return

      setStudents(loadedStudents)
      if (loadedStudents.length > 0) {
        setSelectedStudentId(currentId =>
          loadedStudents.some(student => student.id === currentId)
            ? currentId
            : loadedStudents[0].id
        )
      } else {
        setSelectedStudentId(0)
        setError('Aucun étudiant n’est enregistré dans la base de données.')
      }
    } catch (loadError) {
      if (signal?.aborted) return
      setStudents([])
      setSelectedStudentId(0)
      setError(getErrorMessage(loadError))
    } finally {
      if (!signal?.aborted) setIsLoadingStudents(false)
    }
  }, [])

  useEffect(() => {
    const controller = new AbortController()
    void loadStudents(controller.signal)

    return () => controller.abort()
  }, [loadStudents])

  const handleStudentChange = (id: number) => {
    setSelectedStudentId(id)
    setReportData(null)
    setError(null)
  }

  const toggleDecorator = (key: DecoratorKey) => {
    setDecorators(previous => ({ ...previous, [key]: !previous[key] }))
  }

  const handleSubmit = async () => {
    if (!students.some(student => student.id === selectedStudentId)) {
      setError('Veuillez sélectionner un étudiant valide.')
      return
    }

    setIsLoading(true)
    setReportData(null)
    setError(null)

    try {
      const activeDecorators = DECORATOR_KEYS.filter(key => decorators[key])
      setReportData(await fetchReport(selectedStudentId, activeDecorators))
    } catch (requestError) {
      setError(getErrorMessage(requestError))
    } finally {
      setIsLoading(false)
    }
  }

  return {
    students,
    selectedStudentId,
    handleStudentChange,
    decorators,
    toggleDecorator,
    reportData,
    isLoading,
    isLoadingStudents,
    error,
    retryLoadStudents: () => loadStudents(),
    handleSubmit,
  }
}
