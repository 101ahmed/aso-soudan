import api from '@/services/api'

export async function fetchSocialVisits(code, params = {}) {
  const { data } = await api.get(`/admin/departments/${code}/visits`, { params })
  return data
}

export async function createSocialVisit(code, payload) {
  const { data } = await api.post(`/admin/departments/${code}/visits`, payload)
  return data.data || data
}

export async function updateSocialVisit(code, id, payload) {
  const { data } = await api.put(`/admin/departments/${code}/visits/${id}`, payload)
  return data.data || data
}

export async function deleteSocialVisit(code, id) {
  await api.delete(`/admin/departments/${code}/visits/${id}`)
}
