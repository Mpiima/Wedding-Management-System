import axios from 'axios'

const baseURL = (import.meta.env.VITE_APP_BASE_URL || 'http://localhost/templates/wmis/api').toString().trim()

const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: false
})

// Attach token to every request if present
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  // Let browser set Content-Type (with boundary) for FormData so uploads work
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type']
  }
  return config
})

// Optional: handle 401 globally (e.g. clear token and redirect to login)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('userProfile')
      // Router push to login can be done in the app when using the store
    }
    return Promise.reject(error)
  }
)

export default api
export { baseURL }

/** Build URL for wedding profile photo with cache-bust param. Use uploads .htaccess for no-cache. */
export function weddingPhotoUrl(path, cacheKey) {
  if (!path) return ''
  const base = (baseURL || '').replace(/\/$/, '')
  const full = base ? `${base}/${path.replace(/^\//, '')}` : path
  return cacheKey ? `${full}?t=${cacheKey}` : full
}
