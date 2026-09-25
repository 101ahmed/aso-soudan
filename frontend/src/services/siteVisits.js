import api from '@/services/api'

export async function pingSiteVisit() {
  await api.get('/public/visit')
}

export async function fetchSiteVisitSummary() {
  const { data } = await api.get('/admin/site-visits')
  return data.data || data
}
