import { useState } from 'react'
import useApi from '../hooks/useApi'
import LoadState from '../components/LoadState'
import Pagination from '../components/Pagination'
import { cinemaDate, cinemaTime } from '../time'

export default function SchedulePage() {
  const [date, setDate] = useState(cinemaDate)
  const [cinema, setCinema] = useState('')
  const initialMovie = new URLSearchParams(window.location.search).get('movie_id') || ''
  const [movieId, setMovieId] = useState(/^\d{1,9}$/.test(initialMovie) ? initialMovie : '')
  const [page, setPage] = useState(1)
  const cinemas = useApi('/cinemas')
  const params = new URLSearchParams({ date, page: String(page), per_page: '20' })
  if (cinema) params.set('cinema_id', cinema)
  if (movieId) params.set('movie_id', movieId)
  const screenings = useApi(`/screenings?${params}`)
  function selectDate(value) {
    if (value) { setDate(value); setPage(1) }
  }
  return (
    <section aria-labelledby="schedule-heading">
      <p className="eyebrow">A time and a place for your next story</p>
      <h1 id="schedule-heading">Session schedule<span>.</span></h1>
      <p className="intro">Find a screening that fits your day. All times are local to Tallinn.</p>
      <div className="schedule-controls">
        <label>Date<input type="date" value={date} onChange={(event) => selectDate(event.target.value)} /></label>
        <label>Cinema<select value={cinema} disabled={cinemas.loading || !!cinemas.error} onChange={(event) => { setCinema(event.target.value); setPage(1) }}>
          <option value="">{cinemas.loading ? 'Loading cinemas…' : 'All cinemas'}</option>
          {cinemas.data?.data.map((item) => <option key={item.id} value={item.id}>{item.city} · {item.name}</option>)}
        </select></label>
        {movieId && <button className="secondary" onClick={() => { setMovieId(''); setPage(1); window.history.replaceState(null, '', '/schedule') }}>Show all movies</button>}
      </div>
      {cinemas.error && <div className="notice error" role="alert"><p>Could not load the cinema list. The schedule still shows all cinemas.</p><button onClick={cinemas.retry}>Reload cinemas</button></div>}
      <LoadState loading={screenings.loading} error={screenings.error} retry={screenings.retry} label="Finding your screenings…" />
      {screenings.data && <>
        <div className="schedule-summary"><span>{screenings.data.meta.total} screenings</span><span>{date} · Tallinn time</span></div>
        {screenings.data.data.length === 0
          ? <div className="notice"><h2>No screenings for this selection.</h2><p>Try another date or choose a different cinema.</p></div>
          : <div className="schedule-list">{screenings.data.data.map((screening) => (
            <article className="screening" key={screening.id}>
              <div className="screening-time"><time dateTime={screening.starts_at}>{cinemaTime(screening.starts_at)}</time><span>until {cinemaTime(screening.ends_at)}</span></div>
              <div className="screening-copy"><p className="eyebrow">{screening.hall.cinema.city} · {screening.hall.cinema.name}</p><h2>{screening.movie.title}</h2><p className="muted">{screening.hall.name} · {screening.format} · {screening.language.toUpperCase()}{screening.subtitles && ` · Subtitles: ${screening.subtitles.toUpperCase()}`}</p><p className="muted">{screening.movie.duration_minutes} min · {screening.movie.age_rating}</p></div>
              <div className="screening-price">{new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' }).format(Number(screening.price))}<span>per ticket</span></div>
            </article>
          ))}</div>}
        <Pagination meta={screenings.data.meta} onChange={setPage} />
      </>}
    </section>
  )
}
