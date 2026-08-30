import api from '@/services/api'

const CODE = 'external-relations'

export async function fetchPublicPartners() {
  const { data } = await api.get('/public/external/partners')
  return data.data || data
}

export async function fetchPublicDocuments() {
  const { data } = await api.get('/public/external/documents')
  return data.data || data
}

export async function submitExternalContact(payload) {
  const { data } = await api.post('/public/external/contact-requests', payload)
  return data
}

export async function fetchPartners(code = CODE, params = {}) {
  const { data } = await api.get(`/admin/departments/${code}/partners`, { params })
  return data
}

export async function createPartner(code, payload) {
  const { data } = await api.post(`/admin/departments/${code}/partners`, payload)
  return data.data || data
}

export async function updatePartner(code, id, payload) {
  const { data } = await api.put(`/admin/departments/${code}/partners/${id}`, payload)
  return data.data || data
}

export async function deletePartner(code, id) {
  await api.delete(`/admin/departments/${code}/partners/${id}`)
}

export async function fetchExternalDocuments(code = CODE, params = {}) {
  const { data } = await api.get(`/admin/departments/${code}/external-documents`, { params })
  return data
}

export async function createExternalDocument(code, formData) {
  const { data } = await api.post(`/admin/departments/${code}/external-documents`, formData)
  return data.data || data
}

export async function updateExternalDocument(code, id, formData) {
  const { data } = await api.post(`/admin/departments/${code}/external-documents/${id}`, formData)
  return data.data || data
}

export async function deleteExternalDocument(code, id) {
  await api.delete(`/admin/departments/${code}/external-documents/${id}`)
}

export async function fetchContactRequests(code = CODE, params = {}) {
  const { data } = await api.get(`/admin/departments/${code}/contact-requests`, { params })
  return data
}

export async function createContactRequest(code, payload) {
  const { data } = await api.post(`/admin/departments/${code}/contact-requests`, payload)
  return data.data || data
}

export async function updateContactRequest(code, id, payload) {
  const { data } = await api.put(`/admin/departments/${code}/contact-requests/${id}`, payload)
  return data.data || data
}

export async function deleteContactRequest(code, id) {
  await api.delete(`/admin/departments/${code}/contact-requests/${id}`)
}
