import axios from 'axios'

const apiTimeout = Number(import.meta.env.VITE_API_TIMEOUT || 60000)
const apiBase = import.meta.env.VITE_API_URL || '/api'

const api = axios.create({
  baseURL: apiBase,
  headers: {
    Accept: 'application/json',
  },
  withCredentials: true,
  withXSRFToken: true,
  timeout: Number.isFinite(apiTimeout) && apiTimeout > 0 ? apiTimeout : 60000,
})

let csrfPromise = null

function csrfUrl() {
  const base = String(apiBase).replace(/\/?api\/?$/, '')
  return `${base || ''}/sanctum/csrf-cookie`
}

export function ensureCsrf() {
  if (!csrfPromise) {
    csrfPromise = axios
      .get(csrfUrl(), {
        withCredentials: true,
        headers: { Accept: 'application/json' },
      })
      .catch((error) => {
        csrfPromise = null
        throw error
      })
  }
  return csrfPromise
}

api.interceptors.request.use(async (config) => {
  const method = (config.method || 'get').toLowerCase()
  if (['post', 'put', 'patch', 'delete'].includes(method)) {
    await ensureCsrf()
  }

  if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
    if (typeof config.headers?.delete === 'function') {
      config.headers.delete('Content-Type')
      config.headers.delete('content-type')
    } else if (config.headers) {
      delete config.headers['Content-Type']
      delete config.headers['content-type']
    }
  } else if (config.data && typeof config.data === 'object' && !(config.data instanceof FormData)) {
    if (typeof config.headers?.set === 'function') {
      config.headers.set('Content-Type', 'application/json')
    } else {
      config.headers['Content-Type'] = 'application/json'
    }
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.code === 'ECONNABORTED' || error.message?.includes('timeout')) {
      error.userMessage =
        'Le serveur met trop de temps à répondre (réveil Render possible). Réessayez dans quelques secondes.'
    }

    if (!error.response && (error.code === 'ERR_NETWORK' || error.message === 'Network Error')) {
      error.userMessage = 'network'
    }

    const status = error.response?.status
    const path = window.location.pathname
    const isAuthPage = path.startsWith('/login') || path.startsWith('/forgot-password') || path.startsWith('/reset-password')

    if (status === 401 && path.startsWith('/admin') && !isAuthPage) {
      localStorage.removeItem('rdp_user')
      window.location.href = `/login?redirect=${encodeURIComponent(path)}`
    }

    if (status === 419) {
      csrfPromise = null
    }

    return Promise.reject(error)
  },
)

export async function fetchHealth() {
  const { data } = await api.get('/health')
  return data
}

export default api
