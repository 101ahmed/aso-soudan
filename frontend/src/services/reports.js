import api from '@/services/api'

export async function fetchSecretariatReportsHub(params = {}) {
  const { data } = await api.get('/admin/reports', { params })
  return data
}

export async function fetchSecretariatReport(code, params = {}) {
  const { data } = await api.get(`/admin/departments/${code}/report`, { params })
  return data
}

export async function downloadSecretariatReportPdf(code, params = {}) {
  const { data, headers } = await api.get(`/admin/departments/${code}/report/pdf`, {
    params,
    responseType: 'blob',
  })
  return { blob: data, contentType: headers['content-type'] || data.type }
}
