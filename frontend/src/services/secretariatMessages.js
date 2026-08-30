import api from '@/services/api'

export async function submitSecretariatMessage(code, payload) {
  const { data } = await api.post(`/public/secretariats/${code}/messages`, payload)
  return data
}

export async function fetchSecretariatMessages(code, params = {}) {
  const { data } = await api.get(`/admin/departments/${code}/messages`, { params })
  return data
}

export async function createSecretariatMessage(code, payload) {
  const { data } = await api.post(`/admin/departments/${code}/messages`, payload)
  return data.data || data
}

export async function updateSecretariatMessage(code, id, payload) {
  const { data } = await api.put(`/admin/departments/${code}/messages/${id}`, payload)
  return data.data || data
}

export async function deleteSecretariatMessage(code, id) {
  await api.delete(`/admin/departments/${code}/messages/${id}`)
}
