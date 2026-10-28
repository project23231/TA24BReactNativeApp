export const CINEMA_TIMEZONE = 'Europe/Tallinn'

export function cinemaDate() {
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: CINEMA_TIMEZONE, year: 'numeric', month: '2-digit', day: '2-digit',
  }).formatToParts(new Date())
  const part = (type) => parts.find((value) => value.type === type).value
  return `${part('year')}-${part('month')}-${part('day')}`
}

export function cinemaTime(iso) {
  return new Intl.DateTimeFormat('en-GB', {
    timeZone: CINEMA_TIMEZONE, hour: '2-digit', minute: '2-digit',
  }).format(new Date(iso))
}
