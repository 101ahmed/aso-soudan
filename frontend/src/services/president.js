import api from '@/services/api'

export async function fetchPresidentOverview() {
  const { data } = await api.get('/admin/president/overview')
  return data
}

export async function fetchPresidentSecretariats() {
  const { data } = await api.get('/admin/president/secretariats')
  return data.data || data || []
}

export async function fetchPresidentMeetings(params = {}) {
  const { data } = await api.get('/admin/president/meetings', { params })
  return data
}

export async function createPresidentMeeting(payload) {
  const { data } = await api.post('/admin/president/meetings', payload)
  return data.data || data
}

export async function updatePresidentMeeting(id, payload) {
  const { data } = await api.put(`/admin/president/meetings/${id}`, payload)
  return data.data || data
}

export async function deletePresidentMeeting(id) {
  await api.delete(`/admin/president/meetings/${id}`)
}

export async function fetchPresidentDirectives(params = {}) {
  const { data } = await api.get('/admin/president/directives', { params })
  return data
}

export async function sendPresidentDirective(payload) {
  const { data } = await api.post('/admin/president/directives', payload)
  return data.data || data
}

export async function fetchPresidentArchive(params = {}) {
  const { data } = await api.get('/admin/president/archive', { params })
  return data
}

export async function createPresidentArchiveItem(payload) {
  const { data } = await api.post('/admin/president/archive', payload)
  return data.data || data
}

export async function updatePresidentArchiveItem(id, payload) {
  const { data } = await api.put(`/admin/president/archive/${id}`, payload)
  return data.data || data
}

export async function deletePresidentArchiveItem(id) {
  await api.delete(`/admin/president/archive/${id}`)
}

export async function fetchSecretariatPresidentialDirectives(code, params = {}) {
  const { data } = await api.get(`/admin/departments/${code}/presidential-directives`, { params })
  return data
}

export async function updateSecretariatPresidentialDirective(code, id, payload) {
  const { data } = await api.put(`/admin/departments/${code}/presidential-directives/${id}`, payload)
  return data.data || data
}
