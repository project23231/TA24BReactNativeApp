export async function getJson(path, signal) {
  const response = await fetch(`/api${path}`, { signal, headers: { Accept: 'application/json' } })
  if (!response.ok) {
    throw new Error(response.status === 422
      ? 'Please check your selection and try again.'
      : response.status === 404
        ? 'We could not find what you were looking for.'
        : 'Cinema data is unavailable. Please try again in a moment.')
  }
  return response.json()
}
