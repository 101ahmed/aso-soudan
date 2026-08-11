import axios from 'axios'

const apiTimeout = Number(import.meta.env.VITE_API_TIMEOUT || 60000)

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
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
