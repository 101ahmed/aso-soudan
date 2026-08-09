import api from '@/services/api'

export async function listUsers(params = {}) {
  const { data } = await api.get('/users', { params })
  return data
}

export async function getUser(id) {
  const { data } = await api.get(`/users/${id}`)
  return data.data || data
}

export async function createUser(payload) {
  const { data } = await api.post('/users', payload)
  return data.data || data
}

export async function updateUser(id, payload) {
  const { data } = await api.put(`/users/${id}`, payload)
  return data.data || data
}

export async function disableUser(id) {
  const { data } = await api.post(`/users/${id}/disable`)
  return data.data || data
}

export async function deleteUser(id) {
  const { data } = await api.delete(`/users/${id}`)
  return data
}

export async function listRoles(params = {}) {
  const { data } = await api.get('/roles', { params })
  return data.data || data
}

export async function listPermissions(params = {}) {
  const { data } = await api.get('/permissions', { params })
  return data.data || data
}

export async function syncRolePermissions(roleId, permissionIds) {
  const { data } = await api.put(`/roles/${roleId}/permissions`, {
    permission_ids: permissionIds,
  })
  return data.data || data
}
