export default function LoadState({ loading, error, retry, label = 'Loading movies…' }) {
  if (loading) return <p className="notice" role="status">{label}</p>
  if (error) return (
    <div className="notice error" role="alert">
      <p>{error}</p>
      <button onClick={retry}>Try again</button>
    </div>
  )
  return null
}
