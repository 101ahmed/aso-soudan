import api from '@/services/api'

export async function fetchPublicParentMembers() {
  const { data } = await api.get('/public/parents/members')
  return data.data || data || []
}

export async function fetchPublicParentMeetings() {
  const { data } = await api.get('/public/parents/meetings')
  return data.data || data || []
}

export async function fetchPublicParentSurveys() {
  const { data } = await api.get('/public/parents/surveys')
  return data.data || data || []
}

export async function registerParentHousehold(payload) {
  const { data } = await api.post('/public/parents/registrations', payload)
  return data
}

export async function submitParentSurveyResponse(surveyId, payload) {
  const { data } = await api.post(`/public/parents/surveys/${surveyId}/responses`, payload)
  return data
}

export async function fetchParentMembers(params = {}) {
  const { data } = await api.get('/admin/parents/members', { params })
  return data
}

function toFormData(payload) {
  const body = new FormData()
  Object.entries(payload || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    if (typeof value === 'boolean') {
      body.append(key, value ? '1' : '0')
      return
    }
    body.append(key, value)
  })
  return body
}

export async function createParentMember(payload) {
  const { data } = await api.post('/admin/parents/members', toFormData(payload))
  return data.data || data
}

export async function updateParentMember(id, payload) {
  const body = toFormData(payload)
  body.append('_method', 'PUT')
  const { data } = await api.post(`/admin/parents/members/${id}`, body)
  return data.data || data
}

export async function deleteParentMember(id) {
  await api.delete(`/admin/parents/members/${id}`)
}

export async function fetchParentRegistrations(params = {}) {
  const { data } = await api.get('/admin/parents/registrations', { params })
  return data
}

export async function updateParentRegistration(id, payload) {
  const { data } = await api.put(`/admin/parents/registrations/${id}`, payload)
  return data.data || data
}

export async function deleteParentRegistration(id) {
  await api.delete(`/admin/parents/registrations/${id}`)
}

export async function fetchParentMeetings(params = {}) {
  const { data } = await api.get('/admin/parents/meetings', { params })
  return data
}

export async function createParentMeeting(payload) {
  const { data } = await api.post('/admin/parents/meetings', payload)
  return data.data || data
}

export async function updateParentMeeting(id, payload) {
  const { data } = await api.put(`/admin/parents/meetings/${id}`, payload)
  return data.data || data
}

export async function deleteParentMeeting(id) {
  await api.delete(`/admin/parents/meetings/${id}`)
}

export async function fetchParentSurveys(params = {}) {
  const { data } = await api.get('/admin/parents/surveys', { params })
  return data
}

export async function createParentSurvey(payload) {
  const { data } = await api.post('/admin/parents/surveys', payload)
  return data.data || data
}

export async function updateParentSurvey(id, payload) {
  const { data } = await api.put(`/admin/parents/surveys/${id}`, payload)
  return data.data || data
}

export async function deleteParentSurvey(id) {
  await api.delete(`/admin/parents/surveys/${id}`)
}

export async function fetchParentSurveyResponses(id) {
  const { data } = await api.get(`/admin/parents/surveys/${id}/responses`)
  return data
}
