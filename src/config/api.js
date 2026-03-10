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
