import { DecoratorKey, ReportData, Student } from '../types'

async function requestJson<T>(url: string, init?: RequestInit): Promise<T> {
  const headers = new Headers(init?.headers)
  headers.set('Accept', 'application/json')

  let response: Response
  try {
    response = await fetch(url, { ...init, headers })
  } catch (error) {
    if (error instanceof TypeError) {
      throw new Error('Backend PHP injoignable. Démarrez le serveur backend sur le port 8000.')
    }
    throw error
  }

  const body = await response.text()
  if (body.trim() === '') {
    throw new Error(
      response.ok
        ? 'Le backend PHP a renvoyé une réponse vide.'
        : `Le backend PHP a renvoyé une réponse vide (HTTP ${response.status}).`
    )
  }

  let payload: unknown
  try {
    payload = JSON.parse(body)
  } catch (error) {
    if (error instanceof SyntaxError) {
      throw new Error(`Réponse JSON invalide du backend (HTTP ${response.status}).`)
    }
    throw error
  }

  if (!response.ok) {
    const message =
      typeof payload === 'object' &&
      payload !== null &&
      'error' in payload &&
      typeof payload.error === 'string'
        ? payload.error
        : `La requête a échoué (${response.status}).`
    throw new Error(message)
  }

  return payload as T
}

export function fetchStudents(signal?: AbortSignal): Promise<Student[]> {
  return requestJson<Student[]>('/api/students', { signal })
}

export function fetchReport(
  studentId: number,
  decorators: DecoratorKey[]
): Promise<ReportData> {
  return requestJson<ReportData>('/api/report', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ studentId, decorators }),
  })
}
