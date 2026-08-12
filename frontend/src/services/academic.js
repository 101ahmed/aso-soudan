import api from '@/services/api'

export async function fetchAttendanceOverview() {
  const { data } = await api.get('/admin/academic/attendance/overview')
  return data
}

export async function fetchSubjects() {
  const { data } = await api.get('/admin/academic/subjects')
  return data.data || data
}

export async function fetchClassesBySubject(subjectId) {
  const { data } = await api.get(`/admin/academic/subjects/${subjectId}/classes`)
  return data
}

export async function fetchClassSessions(classId) {
  const { data } = await api.get(`/admin/academic/classes/${classId}/sessions`)
  return data
}

export async function createClassSession(classId, payload) {
  const { data } = await api.post(`/admin/academic/classes/${classId}/sessions`, payload)
  return data.data || data
}

export async function fetchAttendanceSheet(sessionId) {
  const { data } = await api.get(`/admin/academic/sessions/${sessionId}/sheet`)
  return data
}

export async function saveAttendanceSheet(sessionId, rows) {
  const { data } = await api.post(`/admin/academic/sessions/${sessionId}/sheet`, { rows })
  return data
}
