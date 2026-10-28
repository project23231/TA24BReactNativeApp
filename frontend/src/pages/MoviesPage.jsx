import { useState } from 'react'
import useApi from '../hooks/useApi'
import MovieCard from '../components/MovieCard'
import Pagination from '../components/Pagination'
import LoadState from '../components/LoadState'

export default function MoviesPage() {
  const [page, setPage] = useState(1)
  const { data, loading, error, retry } = useApi(`/movies?per_page=12&page=${page}`)
  return (
    <section aria-labelledby="movies-heading">
      <div className="page-heading">
        <div><p className="eyebrow">Make time for a great story</p><h1 id="movies-heading">Movie listings<span>.</span></h1><p className="intro">Find your next big-screen experience.</p></div>
        {data && <p className="result-count">{data.meta.total} movies<br /><span>in our collection</span></p>}
      </div>
      <LoadState loading={loading} error={error} retry={retry} />
      {data && <>
        {data.data.length === 0 && <div className="notice"><h2>The next story is on its way.</h2><p>No movies are available yet. Check back soon.</p></div>}
        <div className="movie-grid">{data.data.map((movie) => <MovieCard key={movie.id} movie={movie} />)}</div>
        <Pagination meta={data.meta} onChange={setPage} />
      </>}
    </section>
  )
}
