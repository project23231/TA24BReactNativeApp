import MoviesPage from './pages/MoviesPage'
import MovieDetailsPage from './pages/MovieDetailsPage'
import SchedulePage from './pages/SchedulePage'
import './cinema.css'

export default function App() {
  const path = window.location.pathname.replace(/\/$/, '') || '/'
  const schedule = path === '/schedule'
  const movieId = path.match(/^\/movies\/(\d{1,9})$/)?.[1]
  const page = schedule ? <SchedulePage />
    : movieId ? <MovieDetailsPage key={movieId} id={movieId} />
      : path === '/' ? <MoviesPage />
        : <section><h1>Page not found<span>.</span></h1><a className="button" href="/">Back to movies</a></section>
  return (
    <>
      <header className="site-header"><a className="brand" href="/" aria-label="Apollo Kino home">apollo<span>kino</span><i aria-hidden="true">●</i></a><nav aria-label="Main navigation"><a href="/" aria-current={path === '/' || movieId ? 'page' : undefined}>Movies</a><a href="/schedule" aria-current={schedule ? 'page' : undefined}>Schedule</a></nav><span className="header-note">Your seat. Your story.</span></header>
      <main>{page}</main>
      <footer><span className="brand">apollo<span>kino</span></span><p>A great story starts here.</p><span>Your next escape is on the big screen.</span></footer>
    </>
  )
}
