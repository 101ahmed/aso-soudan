import api from '@/services/api'

export async function fetchWomenMembers(code, params = {}) {
  const { data } = await api.get(`/admin/departments/${code}/women-members`, { params })
  return data
}

export async function createWomenMember(code, payload) {
  const { data } = await api.post(`/admin/departments/${code}/women-members`, payload)
  return data.data || data
}

export async function updateWomenMember(code, id, payload) {
  const { data } = await api.put(`/admin/departments/${code}/women-members/${id}`, payload)
  return data.data || data
}

export async function deleteWomenMember(code, id) {
  await api.delete(`/admin/departments/${code}/women-members/${id}`)
}
