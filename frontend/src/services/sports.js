import api from '@/services/api'

export const AGE_CATEGORIES = ['u7', 'u9', 'u11', 'u13', 'u15', 'u17', 'u19', 'seniors', 'women', 'veterans']
export const STAFF_KINDS = ['technical', 'administrative']
export const MATCH_STATUSES = ['scheduled', 'played', 'cancelled']
export const JOIN_STATUSES = ['new', 'reviewing', 'accepted', 'rejected']

function adminPath(code, suffix = '') {
  return `/admin/departments/${code}/sports${suffix}`
}

function toFormData(payload) {
  const body = new FormData()
  Object.entries(payload || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    if (typeof value === 'boolean') {
      body.append(key, value ? '1' : '0')
      return
    }
    if (value instanceof File) {
      body.append(key, value, value.name)
      return
    }
    body.append(key, value)
  })
  return body
}

async function postForm(path, payload, asPut = false) {
  const body = toFormData(payload)
  if (asPut) body.append('_method', 'PUT')
  const { data } = await api.post(path, body)
  return data.data || data
}

export async function fetchSportsTeams(code, params = {}) {
  const { data } = await api.get(adminPath(code, '/teams'), { params })
  return data
}

export async function fetchSportsTeam(code, id) {
  const { data } = await api.get(adminPath(code, `/teams/${id}`))
  return data.data || data
}

export function createSportsTeam(code, payload) {
  return postForm(adminPath(code, '/teams'), payload)
}

export function updateSportsTeam(code, id, payload) {
  return postForm(adminPath(code, `/teams/${id}`), payload, true)
}

export async function deleteSportsTeam(code, id) {
  await api.delete(adminPath(code, `/teams/${id}`))
}

export async function fetchSportsPlayers(code, params = {}) {
  const { data } = await api.get(adminPath(code, '/players'), { params })
  return data
}

export function createSportsPlayer(code, payload) {
  return postForm(adminPath(code, '/players'), payload)
}

export function updateSportsPlayer(code, id, payload) {
  return postForm(adminPath(code, `/players/${id}`), payload, true)
}

export async function deleteSportsPlayer(code, id) {
  await api.delete(adminPath(code, `/players/${id}`))
}

export async function fetchSportsStaff(code, params = {}) {
  const { data } = await api.get(adminPath(code, '/staff'), { params })
  return data
}

export function createSportsStaff(code, payload) {
  return postForm(adminPath(code, '/staff'), payload)
}

export function updateSportsStaff(code, id, payload) {
  return postForm(adminPath(code, `/staff/${id}`), payload, true)
}

export async function deleteSportsStaff(code, id) {
  await api.delete(adminPath(code, `/staff/${id}`))
}

export async function fetchSportsMatches(code, params = {}) {
  const { data } = await api.get(adminPath(code, '/matches'), { params })
  return data
}

export async function createSportsMatch(code, payload) {
  const { data } = await api.post(adminPath(code, '/matches'), payload)
  return data.data || data
}

export async function updateSportsMatch(code, id, payload) {
  const { data } = await api.put(adminPath(code, `/matches/${id}`), payload)
  return data.data || data
}

export async function deleteSportsMatch(code, id) {
  await api.delete(adminPath(code, `/matches/${id}`))
}

export async function createSportsTraining(code, payload) {
  const { data } = await api.post(adminPath(code, '/trainings'), payload)
  return data.data || data
}

export async function updateSportsTraining(code, id, payload) {
  const { data } = await api.put(adminPath(code, `/trainings/${id}`), payload)
  return data.data || data
}

export async function deleteSportsTraining(code, id) {
  await api.delete(adminPath(code, `/trainings/${id}`))
}

export async function fetchSportsTournaments(code, params = {}) {
  const { data } = await api.get(adminPath(code, '/tournaments'), { params })
  return data
}

export async function createSportsTournament(code, payload) {
  const { data } = await api.post(adminPath(code, '/tournaments'), payload)
  return data.data || data
}

export async function updateSportsTournament(code, id, payload) {
  const { data } = await api.put(adminPath(code, `/tournaments/${id}`), payload)
  return data.data || data
}

export async function deleteSportsTournament(code, id) {
  await api.delete(adminPath(code, `/tournaments/${id}`))
}

export async function fetchSportsCamps(code, params = {}) {
  const { data } = await api.get(adminPath(code, '/camps'), { params })
  return data
}

export async function createSportsCamp(code, payload) {
  const { data } = await api.post(adminPath(code, '/camps'), payload)
  return data.data || data
}

export async function updateSportsCamp(code, id, payload) {
  const { data } = await api.put(adminPath(code, `/camps/${id}`), payload)
  return data.data || data
}

export async function deleteSportsCamp(code, id) {
  await api.delete(adminPath(code, `/camps/${id}`))
}

export async function fetchSportsJoinRequests(code, params = {}) {
  const { data } = await api.get(adminPath(code, '/join-requests'), { params })
  return data
}

export async function updateSportsJoinRequest(code, id, payload) {
  const { data } = await api.put(adminPath(code, `/join-requests/${id}`), payload)
  return data.data || data
}

export async function deleteSportsJoinRequest(code, id) {
  await api.delete(adminPath(code, `/join-requests/${id}`))
}

export async function fetchPublicSports() {
  const { data } = await api.get('/public/sports')
  return data
}

export async function fetchPublicSportsTeam(id) {
  const { data } = await api.get(`/public/sports/teams/${id}`)
  return data.data || data
}

export async function fetchPublicNationalTeam() {
  const { data } = await api.get('/public/sports/national')
  return data
}

export async function submitSportsJoin(payload) {
  const { data } = await api.post('/public/sports/join', payload)
  return data
}
