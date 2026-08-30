import api from '@/services/api'

function path(code, suffix = '') {
  return `/admin/departments/${code}/decisions${suffix}`
}

export const DECISION_KINDS = ['decision', 'directive']

export const DECISION_STATUSES = ['pending', 'in_progress', 'done', 'delayed', 'cancelled']

export async function fetchMediaDecisions(code, params = {}) {
  const { data } = await api.get(path(code, ''), { params })
  return data
}

export async function createMediaDecision(code, payload) {
  const { data } = await api.post(path(code, ''), payload)
  return data.data || data
}

export async function updateMediaDecision(code, id, payload) {
  const { data } = await api.put(path(code, `/${id}`), payload)
  return data.data || data
}

export async function deleteMediaDecision(code, id) {
  await api.delete(path(code, `/${id}`))
}
