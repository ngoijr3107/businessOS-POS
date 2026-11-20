const TIME_ZONE = 'Africa/Dar_es_Salaam'
const LOCALE = 'en-TZ'

function asDate(value: unknown): Date | null {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(String(value))
  return Number.isNaN(date.getTime()) ? null : date
}

export function formatDateTime(value: unknown): string {
  const date = asDate(value)
  if (!date) return '—'
  return new Intl.DateTimeFormat(LOCALE, {
    timeZone: TIME_ZONE,
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: false,
  }).format(date)
}

export function formatDate(value: unknown): string {
  if (!value) return '—'
  const raw = String(value)
  // Date-only database fields must not be shifted by the browser timezone.
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
    const [year, month, day] = raw.split('-').map(Number)
    return new Intl.DateTimeFormat(LOCALE, {
      timeZone: TIME_ZONE, day: '2-digit', month: 'short', year: 'numeric',
    }).format(new Date(Date.UTC(year, month - 1, day, 12)))
  }
  const date = asDate(value)
  if (!date) return '—'
  return new Intl.DateTimeFormat(LOCALE, {
    timeZone: TIME_ZONE, day: '2-digit', month: 'short', year: 'numeric',
  }).format(date)
}

export function formatTime(value: unknown): string {
  const date = asDate(value)
  if (!date) return '—'
  return new Intl.DateTimeFormat(LOCALE, {
    timeZone: TIME_ZONE, hour: '2-digit', minute: '2-digit', hour12: false,
  }).format(date)
}

export function formatDateTimeLong(value: unknown): string {
  const date = asDate(value)
  if (!date) return '—'
  return new Intl.DateTimeFormat(LOCALE, {
    timeZone: TIME_ZONE, weekday: 'short', day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: false,
  }).format(date)
}
