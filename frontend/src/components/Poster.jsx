import { useState } from 'react'

export default function Poster({ movie }) {
  const [failedPoster, setFailedPoster] = useState(false)
  return (
    <div className="poster">
      {movie.poster_url && !failedPoster
        ? <img src={movie.poster_url} alt={`Poster for ${movie.title}`} loading="lazy" onError={() => setFailedPoster(true)} />
        : <div className="poster-fallback"><span aria-hidden="true">AK</span><strong>{movie.title}</strong><small>THE BIG SCREEN AWAITS</small></div>}
      <span className="age-rating">{movie.age_rating}</span>
    </div>
  )
}
