import api from '@/services/api'

function path(code, suffix = '') {
  return `/admin/departments/${code}/meeting-outputs${suffix}`
}

export async function fetchMeetingOutputs(code, params = {}) {
  const { data } = await api.get(path(code, ''), { params })
  return data
}

export async function createMeetingOutput(code, payload) {
  const { data } = await api.post(path(code, ''), payload)
  return data.data || data
}

export async function updateMeetingOutput(code, id, payload) {
  const { data } = await api.put(path(code, `/${id}`), payload)
  return data.data || data
}

export async function deleteMeetingOutput(code, id) {
  await api.delete(path(code, `/${id}`))
}

export async function fetchPublicMeetingOutputs(code = 'general', params = {}) {
  const { data } = await api.get(`/public/secretariats/${code}/meeting-outputs`, { params })
  return data.data || data || []
}
