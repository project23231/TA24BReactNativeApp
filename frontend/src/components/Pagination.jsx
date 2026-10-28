export default function Pagination({ meta, onChange }) {
  if (!meta || meta.last_page < 2) return null
  return (
    <nav className="pagination" aria-label="Result pages">
      <button disabled={meta.current_page <= 1} onClick={() => onChange(meta.current_page - 1)}>Previous</button>
      <span aria-live="polite">Page {meta.current_page} of {meta.last_page}</span>
      <button disabled={meta.current_page >= meta.last_page} onClick={() => onChange(meta.current_page + 1)}>Next</button>
    </nav>
  )
}
