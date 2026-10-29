import { useEffect, useState } from 'react'
import useApi from '../hooks/useApi'
import MovieCard from '../components/MovieCard'
import Pagination from '../components/Pagination'
import LoadState from '../components/LoadState'

const AGE_RATINGS = ['0+', '6+', '12+', '16+', '18+']
const SORTS = {
  title: { label: 'Title A–Z', sort: 'title', direction: 'asc' },
  newest: { label: 'Newest first', sort: 'release_date', direction: 'desc' },
  oldest: { label: 'Oldest first', sort: 'release_date', direction: 'asc' },
}
const NO_FILTERS = { search: '', genre: '', age: '', date: '', sort: 'title', page: 1 }

function readFilters() {
  const query = new URLSearchParams(window.location.search)
  const value = (name) => query.get(name) || ''
  const page = Number(value('page'))
  return {
    search: value('search').slice(0, 100),
    genre: /^\d{1,9}$/.test(value('genre')) ? value('genre') : '',
    age: AGE_RATINGS.includes(value('age')) ? value('age') : '',
    date: /^\d{4}-\d{2}-\d{2}$/.test(value('date')) ? value('date') : '',
    sort: Object.hasOwn(SORTS, value('sort')) ? value('sort') : 'title',
    page: Number.isInteger(page) && page > 0 ? page : 1,
  }
}

function withSearch(text) {
  const search = text.trim()
  return (current) => current.search === search ? current : { ...current, search, page: 1 }
}

export default function MoviesPage() {
  const [filters, setFilters] = useState(readFilters)
  const [searchText, setSearchText] = useState(filters.search)
  const genres = useApi('/genres')

  function change(field, value) {
    setFilters((current) => ({ ...current, [field]: value, page: 1 }))
  }
  function clearFilters() {
    setSearchText('')
    setFilters(NO_FILTERS)
  }

  useEffect(() => {
    const timer = setTimeout(() => setFilters(withSearch(searchText)), 300)
    return () => clearTimeout(timer)
  }, [searchText])

  const pageQuery = new URLSearchParams()
  if (filters.search) pageQuery.set('search', filters.search)
  if (filters.genre) pageQuery.set('genre', filters.genre)
  if (filters.age) pageQuery.set('age', filters.age)
  if (filters.date) pageQuery.set('date', filters.date)
  if (filters.sort !== 'title') pageQuery.set('sort', filters.sort)
  if (filters.page > 1) pageQuery.set('page', String(filters.page))
  const pageUrl = pageQuery.toString() ? `/?${pageQuery}` : '/'
  useEffect(() => { window.history.replaceState(null, '', pageUrl) }, [pageUrl])

  const params = new URLSearchParams({
    per_page: '12', page: String(filters.page), sort: SORTS[filters.sort].sort, direction: SORTS[filters.sort].direction,
  })
  if (filters.search) params.set('search', filters.search)
  if (filters.genre) params.append('genre_ids[]', filters.genre)
  if (filters.age) params.append('age_ratings[]', filters.age)
  if (filters.date) params.set('date', filters.date)
  const { data, loading, error, retry } = useApi(`/movies?${params}`)
  const filtered = Boolean(filters.search || filters.genre || filters.age || filters.date || searchText.trim())

  return (
    <section aria-labelledby="movies-heading">
      <div className="page-heading">
        <div><p className="eyebrow">Make time for a great story</p><h1 id="movies-heading">Movie listings<span>.</span></h1><p className="intro">Find your next big-screen experience.</p></div>
        {data && <p className="result-count">{data.meta.total} {data.meta.total === 1 ? 'movie' : 'movies'}<br /><span>{filtered ? 'match your selection' : 'in our collection'}</span></p>}
      </div>
      <form className="filter-panel" role="search" onSubmit={(event) => { event.preventDefault(); setFilters(withSearch(searchText)) }}>
        <label className="search-field">Search<input type="search" value={searchText} maxLength={100} placeholder="Movie title" onChange={(event) => setSearchText(event.target.value)} /></label>
        <label>Genre<select value={filters.genre} disabled={genres.loading || !!genres.error} onChange={(event) => change('genre', event.target.value)}>
          <option value="">{genres.loading ? 'Loading genres…' : 'All genres'}</option>
          {genres.data?.data.map((genre) => <option key={genre.id} value={genre.id}>{genre.name}</option>)}
        </select></label>
        <label>Age rating<select value={filters.age} onChange={(event) => change('age', event.target.value)}>
          <option value="">All ages</option>
          {AGE_RATINGS.map((rating) => <option key={rating} value={rating}>{rating}</option>)}
        </select></label>
        <label>Showing on<input type="date" value={filters.date} onChange={(event) => change('date', event.target.value)} /></label>
        <label>Sort by<select value={filters.sort} onChange={(event) => change('sort', event.target.value)}>
          {Object.entries(SORTS).map(([key, option]) => <option key={key} value={key}>{option.label}</option>)}
        </select></label>
        {filtered && <button type="button" className="secondary" onClick={clearFilters}>Clear filters</button>}
      </form>
      {genres.error && <div className="notice error" role="alert"><p>Could not load the genre list. Search and other filters still work.</p><button onClick={genres.retry}>Reload genres</button></div>}
      <LoadState loading={loading} error={error} retry={retry} />
      {data && <>
        {data.data.length === 0 && (filtered
          ? <div className="notice"><h2>No movies match your selection.</h2><p>Try another title or remove a filter.</p><button className="secondary" onClick={clearFilters}>Clear filters</button></div>
          : <div className="notice"><h2>The next story is on its way.</h2><p>No movies are available yet. Check back soon.</p></div>)}
        <div className="movie-grid">{data.data.map((movie) => <MovieCard key={movie.id} movie={movie} />)}</div>
        <Pagination meta={data.meta} onChange={(page) => setFilters((current) => ({ ...current, page }))} />
      </>}
    </section>
  )
}
