import api from '@/services/api'

export async function fetchPublicStudentCatalog() {
  const { data } = await api.get('/public/student-catalog')
  return data
}

export async function registerPublicStudent(payload) {
  const { data } = await api.post('/public/students', payload)
  return data
}
