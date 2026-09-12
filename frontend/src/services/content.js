import api from '@/services/api'
import { toRaw } from 'vue'

function deptPath(code, suffix = '') {
  return `/admin/departments/${code}${suffix}`
}

export async function fetchMyDepartments() {
  const { data } = await api.get('/admin/departments')
  return data.data || data
}

export async function fetchDepartment(code) {
  const { data } = await api.get(deptPath(code, ''))
  return data.data || data
}

export async function updateDepartmentOfficer(code, payload) {
  const body = toFormData(payload)
  const { data } = await api.post(deptPath(code, '/officer'), body)
  return data.data || data
}

export async function updateDepartmentDeputy(code, payload) {
  const body = toFormData(payload)
  const { data } = await api.post(deptPath(code, '/deputy'), body)
  return data.data || data
}

export async function fetchPublicDepartments() {
  const { data } = await api.get('/public/departments')
  return data.data || data
}

export async function fetchSecretariatFeed(code) {
  const { data } = await api.get(`/public/secretariats/${code}/feed`)
  return data
}

export async function fetchPublicNews(params = {}) {
  const { data } = await api.get('/public/news', { params })
  return data
}

export async function fetchPublicNewsItem(slug) {
  const { data } = await api.get(`/public/news/${slug}`)
  return data.data || data
}

export async function fetchPublicDecisions(params = {}) {
  const { data } = await api.get('/public/decisions', { params })
  return data
}

export async function fetchPublicAnnouncements(params = {}) {
  const { data } = await api.get('/public/announcements', { params })
  return data
}

export async function fetchPublicAlbums(params = {}) {
  const { data } = await api.get('/public/albums', { params })
  return data
}

export async function fetchSiteNews(params = {}) {
  const { data } = await api.get('/admin/content/news', { params })
  return data
}

export async function createSiteNews(payload) {
  const body = toFormData(payload)
  const { data } = await api.post('/admin/content/news', body)
  return data.data || data
}

export async function updateSiteNews(id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(`/admin/content/news/${id}`, body)
  return data.data || data
}

export async function publishSiteNews(id) {
  const { data } = await api.post(`/admin/content/news/${id}/publish`)
  return data.data || data
}

export async function archiveSiteNews(id) {
  const { data } = await api.post(`/admin/content/news/${id}/archive`)
  return data.data || data
}

export async function deleteSiteNews(id) {
  await api.delete(`/admin/content/news/${id}`)
}

export async function fetchSiteAnnouncements(params = {}) {
  const { data } = await api.get('/admin/content/announcements', { params })
  return data
}

export async function createSiteAnnouncement(payload) {
  const body = toFormData(payload)
  const { data } = await api.post('/admin/content/announcements', body)
  return data.data || data
}

export async function updateSiteAnnouncement(id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(`/admin/content/announcements/${id}`, body)
  return data.data || data
}

export async function publishSiteAnnouncement(id) {
  const { data } = await api.post(`/admin/content/announcements/${id}/publish`)
  return data.data || data
}

export async function deleteSiteAnnouncement(id) {
  await api.delete(`/admin/content/announcements/${id}`)
}

export async function fetchDepartmentNews(code, params = {}) {
  const { data } = await api.get(deptPath(code, '/news'), { params })
  return data
}

export async function createNews(code, payload) {
  const body = toFormData(payload)
  const { data } = await api.post(deptPath(code, '/news'), body)
  return data.data || data
}

export async function updateNews(code, id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(deptPath(code, `/news/${id}`), body)
  return data.data || data
}

export async function publishNews(code, id) {
  const { data } = await api.post(deptPath(code, `/news/${id}/publish`))
  return data.data || data
}

export async function archiveNews(code, id) {
  const { data } = await api.post(deptPath(code, `/news/${id}/archive`))
  return data.data || data
}

export async function deleteNews(code, id) {
  await api.delete(deptPath(code, `/news/${id}`))
}

export async function fetchDepartmentAnnouncements(code, params = {}) {
  const { data } = await api.get(deptPath(code, '/announcements'), { params })
  return data
}

export async function createAnnouncement(code, payload) {
  const body = toFormData(payload)
  const { data } = await api.post(deptPath(code, '/announcements'), body)
  return data.data || data
}

export async function updateAnnouncement(code, id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(deptPath(code, `/announcements/${id}`), body)
  return data.data || data
}

export async function publishAnnouncement(code, id) {
  const { data } = await api.post(deptPath(code, `/announcements/${id}/publish`))
  return data.data || data
}

export async function deleteAnnouncement(code, id) {
  await api.delete(deptPath(code, `/announcements/${id}`))
}

export async function fetchDepartmentAlbums(code, params = {}) {
  const { data } = await api.get(deptPath(code, '/albums'), { params })
  return data
}

export async function createAlbum(code, payload) {
  const body = toFormData(payload)
  const { data } = await api.post(deptPath(code, '/albums'), body)
  return data.data || data
}

export async function updateAlbum(code, id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(deptPath(code, `/albums/${id}`), body)
  return data.data || data
}

export async function publishAlbum(code, id) {
  const { data } = await api.post(deptPath(code, `/albums/${id}/publish`))
  return data.data || data
}

export async function deleteAlbum(code, id) {
  await api.delete(deptPath(code, `/albums/${id}`))
}

export async function uploadAlbumMedia(code, albumId, files, captions = {}) {
  const body = new FormData()
  const list = (Array.isArray(files) ? files : [files]).filter(Boolean)
  if (list.length === 1) {
    body.append('image', list[0])
  } else {
    list.forEach((file) => body.append('images[]', file))
  }
  if (captions.caption_ar) body.append('caption_ar', captions.caption_ar)
  if (captions.caption_fr) body.append('caption_fr', captions.caption_fr)
  const { data } = await api.post(deptPath(code, `/albums/${albumId}/media`), body)
  return data
}

export async function deleteAlbumMedia(code, albumId, mediaId) {
  await api.delete(deptPath(code, `/albums/${albumId}/media/${mediaId}`))
}

export async function fetchPublicAlbum(slug) {
  const { data } = await api.get(`/public/albums/${slug}`)
  return data.data || data
}

export async function fetchDepartmentEvents(code, params = {}) {
  const { data } = await api.get(deptPath(code, '/events'), { params })
  return data
}

export async function createEvent(code, payload) {
  const body = toFormData(payload)
  const { data } = await api.post(deptPath(code, '/events'), body)
  return data.data || data
}

export async function updateEvent(code, id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(deptPath(code, `/events/${id}`), body)
  return data.data || data
}

export async function publishEvent(code, id) {
  const { data } = await api.post(deptPath(code, `/events/${id}/publish`))
  return data.data || data
}

export async function archiveEvent(code, id) {
  const { data } = await api.post(deptPath(code, `/events/${id}/archive`))
  return data.data || data
}

export async function deleteEvent(code, id) {
  await api.delete(deptPath(code, `/events/${id}`))
}

export async function fetchPublicEvents(params = {}) {
  const { data } = await api.get('/public/events', { params })
  return data
}

export async function fetchPublicStats() {
  const { data } = await api.get('/public/stats')
  return data
}

export async function fetchPublicEvent(slug) {
  const { data } = await api.get(`/public/events/${slug}`)
  return data.data || data
}

export function mapPublicEvent(item) {
  if (!item) return null
  return {
    id: item.id,
    slug: item.slug,
    type: item.type,
    image: item.image_url || '/logo.png',
    date: (item.starts_at || item.published_at || '').slice(0, 10),
    time: item.starts_at ? item.starts_at.slice(11, 16) : '',
    title: { ar: item.title_ar, fr: item.title_fr },
    summary: { ar: item.description_ar, fr: item.description_fr },
    place: { ar: item.location_ar || item.location, fr: item.location_fr || item.location },
    organizer: { ar: item.department?.name_ar, fr: item.department?.name_fr },
    departmentCode: item.department?.code,
    rating_avg: Number(item.rating_avg) || 0,
    rating_count: Number(item.rating_count) || 0,
  }
}

const VISITOR_KEY = 'rdp-event-rater'
const RATINGS_KEY = 'rdp-event-ratings'

export function eventVisitorKey() {
  try {
    let key = localStorage.getItem(VISITOR_KEY)
    if (!key) {
      key = crypto.randomUUID()
      localStorage.setItem(VISITOR_KEY, key)
    }
    return key
  } catch {
    return crypto.randomUUID()
  }
}

export function storedEventRating(eventId) {
  try {
    const map = JSON.parse(localStorage.getItem(RATINGS_KEY) || '{}')
    return Number(map[String(eventId)]) || 0
  } catch {
    return 0
  }
}

export function rememberEventRating(eventId, stars) {
  try {
    const map = JSON.parse(localStorage.getItem(RATINGS_KEY) || '{}')
    map[String(eventId)] = stars
    localStorage.setItem(RATINGS_KEY, JSON.stringify(map))
  } catch {
    /* ignore quota / private mode */
  }
}

export async function ratePublicEvent(slug, stars) {
  const { data } = await api.post(`/public/events/${slug}/rate`, {
    stars,
    visitor_key: eventVisitorKey(),
  })
  return data.data || data
}

function toFormData(payload) {
  const body = new FormData()
  Object.entries(payload || {}).forEach(([key, value]) => {
    const raw = unwrapUploadValue(value)
    if (raw === undefined || raw === null || raw === '') return
    if (typeof raw === 'boolean') {
      body.append(key, raw ? '1' : '0')
      return
    }
    if (Array.isArray(raw)) {
      raw.forEach((item) => body.append(`${key}[]`, unwrapUploadValue(item)))
      return
    }
    if (raw instanceof File) {
      body.append(key, raw, raw.name)
      return
    }
    if (raw instanceof Blob) {
      body.append(key, raw)
      return
    }
    body.append(key, raw)
  })
  return body
}

function unwrapUploadValue(value) {
  if (value === undefined || value === null) return value
  const raw = toRaw(value)
  if (raw instanceof File || raw instanceof Blob) return raw
  if (typeof File !== 'undefined' && value instanceof File) return value
  return raw
}
