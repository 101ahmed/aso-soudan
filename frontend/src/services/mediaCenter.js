import api from '@/services/api'

export const PRESS_KINDS = ['official', 'statement', 'coverage', 'conference', 'interview']

export const PRESS_STATUSES = ['draft', 'pending_review', 'published', 'archived']

function path(code, suffix = '') {
  return `/admin/departments/${code}/media-center${suffix}`
}

function toFormData(payload) {
  const body = new FormData()
  Object.entries(payload || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    if (value instanceof File) {
      body.append(key, value, value.name)
      return
    }
    body.append(key, value)
  })
  return body
}

export async function fetchMediaCenter(code, params = {}) {
  const { data } = await api.get(path(code, ''), { params })
  return data
}

export async function createMediaCenterItem(code, payload) {
  const { data } = await api.post(path(code, ''), toFormData(payload))
  return data.data || data
}

export async function updateMediaCenterItem(code, id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(path(code, `/${id}`), body)
  return data.data || data
}

export async function publishMediaCenterItem(code, id) {
  const { data } = await api.post(path(code, `/${id}/publish`))
  return data.data || data
}

export async function archiveMediaCenterItem(code, id) {
  const { data } = await api.post(path(code, `/${id}/archive`))
  return data.data || data
}

export async function deleteMediaCenterItem(code, id) {
  await api.delete(path(code, `/${id}`))
}

export async function fetchPublicMediaCenter(params = {}) {
  const { data } = await api.get('/public/media-center', { params })
  return data
}

export async function fetchPublicMediaCenterItem(slug) {
  const { data } = await api.get(`/public/media-center/${slug}`)
  return data.data || data
}
