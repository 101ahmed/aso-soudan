import api from '@/services/api'

function deptPath(code, suffix = '') {
  return `/admin/departments/${code}${suffix}`
}

export async function fetchMyDepartments() {
  const { data } = await api.get('/admin/departments')
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

export async function fetchPublicAlbums(params = {}) {
  const { data } = await api.get('/public/albums', { params })
  return data
}

export async function fetchDepartmentNews(code, params = {}) {
  const { data } = await api.get(deptPath(code, '/news'), { params })
  return data
}

export async function createNews(code, payload) {
  const body = toFormData(payload)
  const { data } = await api.post(deptPath(code, '/news'), body, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data.data || data
}

export async function updateNews(code, id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(deptPath(code, `/news/${id}`), body, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
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
  const { data } = await api.post(deptPath(code, '/announcements'), payload)
  return data.data || data
}

export async function updateAnnouncement(code, id, payload) {
  const { data } = await api.put(deptPath(code, `/announcements/${id}`), payload)
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
  const { data } = await api.post(deptPath(code, '/albums'), body, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data.data || data
}

export async function updateAlbum(code, id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(deptPath(code, `/albums/${id}`), body, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data.data || data
}

export async function publishAlbum(code, id) {
  const { data } = await api.post(deptPath(code, `/albums/${id}/publish`))
  return data.data || data
}

export async function deleteAlbum(code, id) {
  await api.delete(deptPath(code, `/albums/${id}`))
}

export async function uploadAlbumMedia(code, albumId, file, captions = {}) {
  const body = new FormData()
  body.append('image', file)
  if (captions.caption_ar) body.append('caption_ar', captions.caption_ar)
  if (captions.caption_fr) body.append('caption_fr', captions.caption_fr)
  const { data } = await api.post(deptPath(code, `/albums/${albumId}/media`), body, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data.data || data
}

function toFormData(payload) {
  const body = new FormData()
  Object.entries(payload || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    if (typeof value === 'boolean') {
      body.append(key, value ? '1' : '0')
      return
    }
    body.append(key, value)
  })
  return body
}
