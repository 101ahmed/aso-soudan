import api from '@/services/api'

export async function fetchPublicShuraMembers() {
  const { data } = await api.get('/public/shura/members')
  return data.data || data
}

export async function fetchPublicShuraMeetings() {
  const { data } = await api.get('/public/shura/meetings')
  return data.data || data
}

export async function fetchShuraOverview() {
  const { data } = await api.get('/admin/shura/overview')
  return data
}

export async function fetchShuraMembers(params = {}) {
  const { data } = await api.get('/admin/shura/members', { params })
  return data
}

export async function createShuraMember(payload) {
  const body = toFormData(payload)
  const { data } = await api.post('/admin/shura/members', body)
  return data.data || data
}

export async function updateShuraMember(id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(`/admin/shura/members/${id}`, body)
  return data.data || data
}

export async function deleteShuraMember(id) {
  await api.delete(`/admin/shura/members/${id}`)
}

export async function fetchShuraMeetings(params = {}) {
  const { data } = await api.get('/admin/shura/meetings', { params })
  return data
}

export async function createShuraMeeting(payload) {
  const { data } = await api.post('/admin/shura/meetings', payload)
  return data.data || data
}

export async function updateShuraMeeting(id, payload) {
  const { data } = await api.put(`/admin/shura/meetings/${id}`, payload)
  return data.data || data
}

export async function deleteShuraMeeting(id) {
  await api.delete(`/admin/shura/meetings/${id}`)
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
