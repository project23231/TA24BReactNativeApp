import MoviesPage from './pages/MoviesPage'
import './cinema.css'

export default function App() {
  return (
    <>
      <header className="site-header"><a className="brand" href="/" aria-label="Apollo Kino home">apollo<span>kino</span><i aria-hidden="true">●</i></a><nav aria-label="Main navigation"><a href="/" aria-current="page">Movies</a></nav><span className="header-note">Your seat. Your story.</span></header>
      <main><MoviesPage /></main>
      <footer><span className="brand">apollo<span>kino</span></span><p>A great story starts here.</p><span>Student cinema project</span></footer>
    </>
  )
}
