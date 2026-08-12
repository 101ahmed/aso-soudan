import axios from 'axios'

const apiTimeout = Number(import.meta.env.VITE_API_TIMEOUT || 60000)

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api',
  headers: {
    Accept: 'application/json',
  },
  withCredentials: false,
  // Render free tier cold start can exceed 10s
  timeout: Number.isFinite(apiTimeout) && apiTimeout > 0 ? apiTimeout : 60000,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('rdp_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  // Ne jamais forcer application/json sur FormData (sinon PHP ignore les fichiers)
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

    if (error.response?.status === 401) {
      localStorage.removeItem('rdp_token')
      localStorage.removeItem('rdp_user')
      if (!window.location.pathname.startsWith('/login')) {
        window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`
      }
    }
    return Promise.reject(error)
  },
)

export async function fetchHealth() {
  const { data } = await api.get('/health')
  return data
}

export default api
