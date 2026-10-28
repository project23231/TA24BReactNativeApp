export async function getJson(path, signal) {
  const response = await fetch(`/api${path}`, { signal, headers: { Accept: 'application/json' } })
  if (!response.ok) {
    throw new Error(response.status === 422
      ? 'Please check your selection and try again.'
      : 'Cinema data is unavailable. Please try again in a moment.')
  }
  return response.json()
}
