import api from '@/services/api'

function deptPath(code, suffix = '') {
  return `/admin/departments/${code}${suffix}`
}

export async function fetchSecretariatDirectiveTargets(code) {
  const { data } = await api.get(deptPath(code, '/secretariat-directives/targets'))
  return data.data || data
}

export async function fetchSecretariatDirectives(code, params = {}) {
  const { data } = await api.get(deptPath(code, '/secretariat-directives'), { params })
  return data
}

export async function sendSecretariatDirective(code, payload) {
  const { data } = await api.post(deptPath(code, '/secretariat-directives'), payload)
  return data.data || data
}

export async function updateSecretariatDirective(code, id, payload) {
  const { data } = await api.put(deptPath(code, `/secretariat-directives/${id}`), payload)
  return data.data || data
}
