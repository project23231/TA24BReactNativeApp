import { useEffect, useState } from 'react'
import { getJson } from '../api'

export default function useApi(path) {
  const [attempt, setAttempt] = useState(0)
  const [result, setResult] = useState({ key: null })
  const key = `${path}:${attempt}`

  useEffect(() => {
    const controller = new AbortController()
    getJson(path, controller.signal)
      .then((data) => {
        if (!controller.signal.aborted) setResult({ key, data })
      })
      .catch((error) => {
        if (!controller.signal.aborted) {
          setResult({ key, error: error.name === 'TypeError'
            ? 'Cannot reach the cinema. Please check your connection and try again.'
            : error.message })
        }
      })
    return () => controller.abort()
  }, [path, key])

  return {
    loading: result.key !== key,
    data: result.key === key ? result.data : null,
    error: result.key === key ? result.error : null,
    retry: () => setAttempt((value) => value + 1),
  }
}
