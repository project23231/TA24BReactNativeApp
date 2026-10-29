import useApi from '../hooks/useApi'
import LoadState from '../components/LoadState'
import Poster from '../components/Poster'
import { cinemaDay, cinemaTime } from '../time'

function backToMovies(event) {
  if (document.referrer.startsWith(window.location.origin) && window.history.length > 1) {
    event.preventDefault()
    window.history.back()
  }
}

export default function MovieDetailsPage({ id }) {
  const { data, loading, error, retry } = useApi(`/movies/${id}`)
  const movie = data?.data
  const trailer = /^https?:\/\//.test(movie?.trailer_url || '') ? movie.trailer_url : null
  return (
    <section aria-labelledby="movie-heading">
      <a className="back-link" href="/" onClick={backToMovies}><span aria-hidden="true">←</span> All movies</a>
      <LoadState loading={loading} error={error} retry={retry} label="Loading movie…" />
      {movie && <>
        <div className="movie-details">
          <Poster movie={movie} />
          <div>
            <p className="eyebrow">{movie.genres.map((genre) => genre.name).join(' / ') || 'Cinema'}</p>
            <h1 id="movie-heading">{movie.title}<span>.</span></h1>
            <p className="muted">{movie.duration_minutes} min{movie.release_date && ` · ${movie.release_date.slice(0, 4)}`} · Age {movie.age_rating}</p>
            <p className="description">{movie.description}</p>
            <div className="detail-actions">
              <a className="button" href={`/schedule?movie_id=${movie.id}`}>Find a screening</a>
              {trailer && <a className="button secondary" href={trailer} target="_blank" rel="noopener noreferrer">Watch trailer <span aria-hidden="true">↗</span></a>}
            </div>
          </div>
        </div>
        <h2 className="section-title">Upcoming screenings</h2>
        {movie.screenings.length === 0
          ? <div className="notice"><h3>No upcoming screenings.</h3><p>This movie is not on the schedule right now. Check back soon.</p></div>
          : <div className="schedule-list">{movie.screenings.map((screening) => (
            <article className="screening" key={screening.id}>
              <div className="screening-time"><time dateTime={screening.starts_at}>{cinemaTime(screening.starts_at)}</time><span>{cinemaDay(screening.starts_at)}</span></div>
              <div className="screening-copy"><p className="eyebrow">{screening.hall.cinema.city} · {screening.hall.cinema.name}</p><h3>{screening.hall.name}</h3><p className="muted">{screening.format} · {screening.language.toUpperCase()}{screening.subtitles && ` · Subtitles: ${screening.subtitles.toUpperCase()}`} · until {cinemaTime(screening.ends_at)}</p></div>
              <div className="screening-price">{new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' }).format(Number(screening.price))}<span>per ticket</span></div>
            </article>
          ))}</div>}
      </>}
    </section>
  )
}
