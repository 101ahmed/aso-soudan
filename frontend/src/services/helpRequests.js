import api from '@/services/api'

export async function submitHelpRequest(payload) {
  const { data } = await api.post('/public/help-requests', payload)
  return data
}

export async function fetchHelpRequests(code, params = {}) {
  const { data } = await api.get(`/admin/departments/${code}/help-requests`, { params })
  return data
}

export async function createHelpRequest(code, payload) {
  const { data } = await api.post(`/admin/departments/${code}/help-requests`, payload)
  return data.data || data
}

export async function updateHelpRequest(code, id, payload) {
  const { data } = await api.put(`/admin/departments/${code}/help-requests/${id}`, payload)
  return data.data || data
}

export async function deleteHelpRequest(code, id) {
  await api.delete(`/admin/departments/${code}/help-requests/${id}`)
}
