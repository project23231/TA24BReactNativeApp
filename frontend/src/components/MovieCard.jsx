import { useState } from 'react'

export default function MovieCard({ movie }) {
  const [failedPoster, setFailedPoster] = useState(false)
  return (
    <article className="movie-card">
      <div className="poster">
        {movie.poster_url && !failedPoster
          ? <img src={movie.poster_url} alt={`Poster for ${movie.title}`} loading="lazy" onError={() => setFailedPoster(true)} />
          : <div className="poster-fallback"><span aria-hidden="true">AK</span><strong>{movie.title}</strong><small>THE BIG SCREEN AWAITS</small></div>}
        <span className="age-rating">{movie.age_rating}</span>
      </div>
      <div className="movie-copy">
        <p className="eyebrow genres">{movie.genres.map((genre) => genre.name).join(' / ') || 'Cinema'}</p>
        <h2>{movie.title}</h2>
        <p className="muted">{movie.duration_minutes} min{movie.release_date && ` · ${movie.release_date.slice(0, 4)}`}</p>
        <a className="screening-link" href={`/schedule?movie_id=${movie.id}`}>View screenings <span aria-hidden="true">↗</span></a>
      </div>
    </article>
  )
}
