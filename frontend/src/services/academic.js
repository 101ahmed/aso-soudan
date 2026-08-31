import api from '@/services/api'

function withoutFrenchSubject(items = []) {
  return (items || []).filter(
    (item) => item?.code !== 'FR' && item?.name_ar !== 'اللغة الفرنسية',
  )
}

export async function fetchTeacherRegister(params = {}) {
  const { data } = await api.get('/admin/academic/register', { params })
  return data
}

export async function upsertTeacherRegister(studentId, payload) {
  const { data } = await api.put(`/admin/academic/register/${studentId}`, payload)
  return data
}

export async function fetchAttendanceOverview() {
  const { data } = await api.get('/admin/academic/attendance/overview')
  return {
    ...data,
    subjects: withoutFrenchSubject(data.subjects),
  }
}

export async function fetchSubjects() {
  const { data } = await api.get('/admin/academic/subjects')
  return withoutFrenchSubject(data.data || data)
}

export async function fetchClassesByLevel(levelId) {
  const { data } = await api.get(`/admin/academic/levels/${levelId}/classes`)
  return data
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

export async function updateClassSession(sessionId, payload) {
  const { data } = await api.put(`/admin/academic/sessions/${sessionId}`, payload)
  return data.data || data
}

export async function deleteClassSession(sessionId) {
  await api.delete(`/admin/academic/sessions/${sessionId}`)
}

export async function fetchClassRoster(classId, sessionId = null) {
  const { data } = await api.get(`/admin/academic/classes/${classId}/roster`, {
    params: sessionId ? { session_id: sessionId } : {},
  })
  return data
}

export async function addStudentToClass(classId, studentId, sessionId = null) {
  const { data } = await api.post(`/admin/academic/classes/${classId}/students`, {
    student_id: studentId,
    session_id: sessionId || undefined,
  })
  return data
}

export async function removeStudentFromClass(classId, studentId, sessionId = null) {
  const { data } = await api.delete(`/admin/academic/classes/${classId}/students/${studentId}`, {
    params: sessionId ? { session_id: sessionId } : {},
  })
  return data
}

export async function upsertStudentAttendance(sessionId, studentId, payload) {
  const { data } = await api.put(
    `/admin/academic/sessions/${sessionId}/students/${studentId}/attendance`,
    payload,
  )
  return data
}

export async function deleteStudentAttendance(sessionId, studentId) {
  const { data } = await api.delete(`/admin/academic/sessions/${sessionId}/students/${studentId}/attendance`)
  return data
}

export async function fetchAttendanceSheet(sessionId) {
  const { data } = await api.get(`/admin/academic/sessions/${sessionId}/sheet`)
  return data
}

export async function saveAttendanceSheet(sessionId, rows) {
  const { data } = await api.post(`/admin/academic/sessions/${sessionId}/sheet`, { rows })
  return data
}

export async function fetchLevels() {
  const { data } = await api.get('/admin/academic/levels')
  return data.data || data
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

export async function fetchTimetable(params = {}) {
  const { data } = await api.get('/admin/academic/timetable', { params })
  return data
}

export async function createTimetableEntry(payload) {
  const { data } = await api.post('/admin/academic/timetable', payload)
  return data.data || data
}

export async function updateTimetableEntry(id, payload) {
  const { data } = await api.put(`/admin/academic/timetable/${id}`, payload)
  return data.data || data
}

export async function deleteTimetableEntry(id) {
  await api.delete(`/admin/academic/timetable/${id}`)
}

export async function fetchStudentCatalog() {
  const { data } = await api.get('/admin/academic/catalog')
  return {
    ...data,
    subjects: withoutFrenchSubject(data.subjects),
  }
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
