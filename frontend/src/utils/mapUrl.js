function trim(value) {
  return String(value || '').trim()
}

function withHttps(url) {
  if (/^https?:\/\//i.test(url)) return url
  if (url.startsWith('//')) return `https:${url}`
  return `https://${url.replace(/^\/+/, '')}`
}

function looksLikeMapUrl(url) {
  return (
    /^(https?:)?\/\//i.test(url)
    || /google\.[^/\s]+\/maps/i.test(url)
    || /maps\.google/i.test(url)
    || /maps\.app\.goo\.gl/i.test(url)
    || /openstreetmap\.org/i.test(url)
  )
}

export function mapQuery(url, location) {
  const raw = trim(url)
  if (raw) {
    try {
      const parsed = new URL(withHttps(raw))
      const q = parsed.searchParams.get('query') || parsed.searchParams.get('q')
      if (q) return q
    } catch {
      /* ignore invalid URL */
    }
  }
  return trim(location)
}

export function mapHref(url, location) {
  const raw = trim(url)
  if (raw && looksLikeMapUrl(raw)) {
    try {
      const parsed = new URL(withHttps(raw))
      const q = parsed.searchParams.get('query') || parsed.searchParams.get('q')
      const host = parsed.hostname.replace(/^www\./, '')
      const isSimpleGoogleQuery = q && (host === 'maps.google.com' || parsed.pathname === '/maps' || parsed.pathname === '/')
      if (isSimpleGoogleQuery && !parsed.pathname.includes('/place/')) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q)}`
      }
      return parsed.toString()
    } catch {
      return withHttps(raw)
    }
  }
  const q = mapQuery(url, location)
  if (!q) return ''
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q)}`
}

export function mapEmbedSrc(url, location) {
  const q = mapQuery(url, location)
  if (!q) return ''
  return `https://maps.google.com/maps?q=${encodeURIComponent(q)}&hl=fr&z=16&output=embed`
}
