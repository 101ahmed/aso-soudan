import api from '@/services/api'

function financePath(code, suffix = '') {
  return `/admin/departments/${code}/finance${suffix}`
}

export function formatEuro(amount) {
  const n = Number(amount)
  if (!Number.isFinite(n)) return '—'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 2,
  }).format(n)
}

export async function fetchFinanceOverview(code, params = {}) {
  const { data } = await api.get(financePath(code, '/overview'), { params })
  return data
}

export async function saveFinanceBudget(code, payload) {
  const { data } = await api.put(financePath(code, '/budget'), payload)
  return data
}

export async function fetchFinanceRevenues(code, params = {}) {
  const { data } = await api.get(financePath(code, '/revenues'), { params })
  return data
}

export async function createFinanceRevenue(code, payload) {
  const { data } = await api.post(financePath(code, '/revenues'), payload)
  return data.data || data
}

export async function updateFinanceRevenue(code, id, payload) {
  const { data } = await api.put(financePath(code, `/revenues/${id}`), payload)
  return data.data || data
}

export async function deleteFinanceRevenue(code, id) {
  await api.delete(financePath(code, `/revenues/${id}`))
}

export async function fetchFinanceExpenses(code, params = {}) {
  const { data } = await api.get(financePath(code, '/expenses'), { params })
  return data
}

export async function createFinanceExpense(code, payload) {
  const { data } = await api.post(financePath(code, '/expenses'), payload)
  return data.data || data
}

export async function updateFinanceExpense(code, id, payload) {
  const { data } = await api.put(financePath(code, `/expenses/${id}`), payload)
  return data.data || data
}

export async function deleteFinanceExpense(code, id) {
  await api.delete(financePath(code, `/expenses/${id}`))
}

export const REVENUE_SOURCES = [
  'membership',
  'donation',
  'individual',
  'project_support',
  'activity',
  'other',
]

export async function fetchFinanceDocuments(code) {
  const { data } = await api.get(financePath(code, '/documents'))
  return data.data || data || []
}

export async function updateFinanceDocument(code, kind, formData) {
  const { data } = await api.post(financePath(code, `/documents/${kind}`), formData)
  return data.data || data
}

export async function publishFinanceDocument(code, kind) {
  const { data } = await api.post(financePath(code, `/documents/${kind}/publish`))
  return data.data || data
}

export async function unpublishFinanceDocument(code, kind) {
  const { data } = await api.post(financePath(code, `/documents/${kind}/unpublish`))
  return data.data || data
}

export async function fetchPublicFinanceDocuments() {
  const { data } = await api.get('/public/finance/documents')
  return data.data || data || []
}

export async function fetchPublicFinanceDocument(kind) {
  const { data } = await api.get(`/public/finance/documents/${kind}`)
  return data.data || data
}

export const EXPENSE_CATEGORIES = [
  'education',
  'social',
  'sports',
  'relief',
  'admin',
  'events',
  'aid',
]
