import Poster from './Poster'

export default function MovieCard({ movie }) {
  return (
    <article className="movie-card">
      <a href={`/movies/${movie.id}`} aria-label={`${movie.title} details`}><Poster movie={movie} /></a>
      <div className="movie-copy">
        <p className="eyebrow genres">{movie.genres.map((genre) => genre.name).join(' / ') || 'Cinema'}</p>
        <h2><a className="title-link" href={`/movies/${movie.id}`}>{movie.title}</a></h2>
        <p className="muted">{movie.duration_minutes} min{movie.release_date && ` · ${movie.release_date.slice(0, 4)}`}</p>
        <a className="screening-link" href={`/schedule?movie_id=${movie.id}`}>View screenings <span aria-hidden="true">↗</span></a>
      </div>
    </article>
  )
}
