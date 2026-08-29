import api from '@/services/api'

export async function fetchMembers(params = {}) {
  const { data } = await api.get('/admin/statistics/members', { params })
  return data
}

export async function createMember(payload) {
  const { data } = await api.post('/admin/statistics/members', payload)
  return data.data || data
}

export async function updateMember(id, payload) {
  const { data } = await api.put(`/admin/statistics/members/${id}`, payload)
  return data.data || data
}

export async function deleteMember(id) {
  await api.delete(`/admin/statistics/members/${id}`)
}

export async function sendMemberMessage(payload) {
  const { data } = await api.post('/admin/statistics/members/message', payload)
  return data
}

export async function registerPublicMember(payload) {
  const { data } = await api.post('/public/members', payload)
  return data
}
