function trim(value) {
  return String(value || '').trim()
}

function withHttps(url) {
  if (/^https?:\/\//i.test(url)) return url
  if (url.startsWith('//')) return `https:${url}`
  return `https://${url.replace(/^\/+/, '')}`
}

function isKnownMapHost(hostname) {
  const host = String(hostname || '').replace(/^www\./, '')
  return (
    /google\.[^/\s]+$/i.test(host)
    || host === 'maps.google.com'
    || host === 'maps.app.goo.gl'
    || host === 'goo.gl'
    || host === 'openstreetmap.org'
  )
}

export function isRealHttpUrl(url) {
  const raw = trim(url)
  if (!raw || /\s/.test(raw)) return false
  if (!/^(https?:)?\/\//i.test(raw) && !isKnownMapHost(raw.split('/')[0])) return false
  try {
    const parsed = new URL(withHttps(raw))
    if (!parsed.hostname.includes('.')) return false
    if (parsed.hostname.startsWith('xn--') && !isKnownMapHost(parsed.hostname)) return false
    return true
  } catch {
    return false
  }
}

function looksLikeMapUrl(url) {
  const raw = trim(url)
  if (!raw || /\s/.test(raw)) return false
  return (
    /google\.[^/\s]+\/maps/i.test(raw)
    || /maps\.google/i.test(raw)
    || /maps\.app\.goo\.gl/i.test(raw)
    || /openstreetmap\.org/i.test(raw)
  )
}

function decodeAddressCandidate(value) {
  try {
    return decodeURIComponent(String(value || '').replace(/\+/g, ' ')).trim()
  } catch {
    return String(value || '').trim()
  }
}

export function mapQuery(url, location) {
  const raw = trim(url)
  const loc = trim(location)

  if (raw && isRealHttpUrl(raw) && looksLikeMapUrl(raw)) {
    try {
      const parsed = new URL(withHttps(raw))
      const q = parsed.searchParams.get('query') || parsed.searchParams.get('q')
      if (q) return q.trim()
      const place = parsed.pathname.match(/\/maps\/place\/([^/]+)/)
      if (place) return decodeAddressCandidate(place[1])
    } catch {
      /* ignore invalid URL */
    }
    return loc
  }

  if (raw) {
    const stripped = decodeAddressCandidate(raw.replace(/^https?:\/\//i, ''))
    if (stripped && (!isRealHttpUrl(raw) || !looksLikeMapUrl(raw))) {
      return stripped
    }
  }

  return loc
}

export function osmSearchHref(url, location) {
  const q = mapQuery(url, location)
  if (!q) return ''
  return `https://www.openstreetmap.org/search?query=${encodeURIComponent(q)}`
}

export function googleSearchHref(url, location) {
  const q = mapQuery(url, location)
  if (!q) return ''
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q)}`
}

export function mapHref(url, location) {
  return osmSearchHref(url, location)
}

export function osmEmbedSrc(lat, lon) {
  const latitude = Number(lat)
  const longitude = Number(lon)
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return ''
  const pad = 0.012
  const bbox = [longitude - pad, latitude - pad / 1.6, longitude + pad, latitude + pad / 1.6].join(',')
  return `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(bbox)}&layer=mapnik&marker=${latitude}%2C${longitude}`
}

export async function geocodeQuery(query) {
  const q = trim(query)
  if (!q) return null

  const attempts = []
  if (!/rennes|france|سودان/i.test(q)) {
    attempts.push(`${q}, Rennes, France`)
  }
  attempts.push(q)

  for (const attempt of attempts) {
    const params = new URLSearchParams({
      format: 'jsonv2',
      limit: '1',
      q: attempt,
    })
    try {
      const response = await fetch(`https://nominatim.openstreetmap.org/search?${params}`, {
        headers: { Accept: 'application/json' },
      })
      if (!response.ok) continue
      const rows = await response.json()
      const hit = Array.isArray(rows) ? rows[0] : null
      if (!hit?.lat || !hit?.lon) continue
      return {
        lat: Number(hit.lat),
        lon: Number(hit.lon),
        label: hit.display_name || attempt,
      }
    } catch {
      /* try next / API fallback */
    }
  }

  const apiBase = String(import.meta.env.VITE_API_URL || '/api').replace(/\/$/, '')
  try {
    const response = await fetch(`${apiBase}/public/geocode?q=${encodeURIComponent(q)}`, {
      headers: { Accept: 'application/json' },
    })
    if (!response.ok) return null
    const payload = await response.json()
    const hit = payload?.data
    if (!hit?.lat || !hit?.lon) return null
    return {
      lat: Number(hit.lat),
      lon: Number(hit.lon),
      label: hit.label || q,
    }
  } catch {
    return null
  }
}
