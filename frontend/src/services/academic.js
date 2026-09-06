import api from '@/services/api'

function withoutFrenchSubject(items = []) {
  // Children's FR only. Adult French (FR_ADULT / اللغة الفرنسية للبالغين) stays visible.
  return (items || []).filter(
    (item) => item?.code !== 'FR' && item?.name_ar !== 'اللغة الفرنسية',
  )
}

export async function fetchTeacherRegister(params = {}) {
  const { data } = await api.get('/admin/academic/register', { params })
  return data
}

export async function downloadTeacherRegisterPdf(params = {}) {
  const { data, headers } = await api.get('/admin/academic/register/pdf', {
    params,
    responseType: 'blob',
  })
  return { blob: data, contentType: headers['content-type'] || data.type }
}

export async function upsertTeacherRegister(studentId, payload) {
  const { data } = await api.put(`/admin/academic/register/${studentId}`, payload)
  return data
}

export async function renameTeacherRegisterStudent(studentId, payload) {
  const { data } = await api.patch(`/admin/academic/register/${studentId}`, payload)
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

export async function fetchLessonPreparations() {
  const { data } = await api.get('/admin/academic/lesson-preparations')
  return data.data || data
}

export async function createLessonPreparation(payload) {
  const { data } = await api.post('/admin/academic/lesson-preparations', payload)
  return data.data || data
}

export async function updateLessonPreparation(id, payload) {
  const { data } = await api.put(`/admin/academic/lesson-preparations/${id}`, payload)
  return data.data || data
}

export async function deleteLessonPreparation(id) {
  await api.delete(`/admin/academic/lesson-preparations/${id}`)
}

export async function downloadLessonPreparationPdf(id, params = {}) {
  const { data, headers } = await api.get(`/admin/academic/lesson-preparations/${id}/pdf`, {
    params,
    responseType: 'blob',
  })
  return { blob: data, contentType: headers['content-type'] || data.type }
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

export async function fetchStudent(id) {
  const { data } = await api.get(`/admin/academic/students/${id}`)
  return data.data || data
}

export async function createStudent(payload) {
  const body = studentPayload(payload)
  const { data } = await api.post('/admin/academic/students', body)
  return data.data || data
}

export async function updateStudent(id, payload) {
  const body = studentPayload(payload)
  if (body instanceof FormData) {
    body.append('_method', 'PUT')
    const { data } = await api.post(`/admin/academic/students/${id}`, body)
    return data.data || data
  }
  const { data } = await api.put(`/admin/academic/students/${id}`, body)
  return data.data || data
}

export async function deleteStudent(id) {
  await api.delete(`/admin/academic/students/${id}`)
}

export async function downloadStudentDossierPdf(id, params = {}) {
  const { data, headers } = await api.get(`/admin/academic/students/${id}/pdf`, {
    params,
    responseType: 'blob',
  })
  return { blob: data, contentType: headers['content-type'] || data.type }
}

function studentPayload(payload) {
  const hasFile = payload?.photo instanceof File || payload?.photo instanceof Blob
  const removePhoto = Boolean(payload?.remove_photo)
  if (!hasFile && !removePhoto) {
    const { photo, remove_photo, ...json } = payload || {}
    return json
  }
  const body = new FormData()
  Object.entries(payload || {}).forEach(([key, value]) => {
    if (value === undefined || value === null) return
    if (key === 'photo' && !(value instanceof File) && !(value instanceof Blob)) return
    if (key === 'subject_ids') {
      const ids = Array.isArray(value) ? value : []
      if (!ids.length) {
        body.append('subject_ids', '')
        return
      }
      ids.forEach((id) => body.append('subject_ids[]', String(id)))
      return
    }
    if (typeof value === 'boolean') {
      body.append(key, value ? '1' : '0')
      return
    }
    if (value instanceof File) {
      body.append(key, value, value.name)
      return
    }
    if (value instanceof Blob) {
      body.append(key, value, 'photo.jpg')
      return
    }
    body.append(key, value)
  })
  return body
}
