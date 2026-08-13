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

export async function fetchTeachers(params = {}) {
  const { data } = await api.get('/admin/academic/teachers', { params })
  return data
}

export async function createTeacher(payload) {
  const { data } = await api.post('/admin/academic/teachers', payload)
  return data.data || data
}

export async function updateTeacher(id, payload) {
  const { data } = await api.put(`/admin/academic/teachers/${id}`, payload)
  return data.data || data
}

export async function deleteTeacher(id) {
  await api.delete(`/admin/academic/teachers/${id}`)
}

export async function fetchStudentCatalog() {
  const { data } = await api.get('/admin/academic/catalog')
  return data
}

export async function fetchStudents(params = {}) {
  const { data } = await api.get('/admin/academic/students', { params })
  return data
}

export async function createStudent(payload) {
  const { data } = await api.post('/admin/academic/students', payload)
  return data.data || data
}

export async function updateStudent(id, payload) {
  const { data } = await api.put(`/admin/academic/students/${id}`, payload)
  return data.data || data
}

export async function deleteStudent(id) {
  await api.delete(`/admin/academic/students/${id}`)
}
