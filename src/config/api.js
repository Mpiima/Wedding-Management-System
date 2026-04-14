/**
 * API base URL for future backend integration (avatars, uploads, etc.).
 */
export const baseURL = (import.meta.env.VITE_APP_BASE_URL || '').toString().replace(/\/$/, '')

function buildUrl(path = '') {
  const cleanedPath = String(path || '').replace(/^\/+/, '')
  if (!baseURL) return `/${cleanedPath}`
  return `${baseURL}/${cleanedPath}`
}

async function request(method, path, payload) {
  const options = {
    method,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    }
  }

  const token = localStorage.getItem('token') || localStorage.getItem('sc360_token')
  if (token) {
    options.headers.Authorization = `Bearer ${token}`
  }

  if (payload !== undefined) {
    options.body = JSON.stringify(payload)
  }

  const response = await fetch(buildUrl(path), options)
  let data = null
  try {
    data = await response.json()
  } catch {
    data = null
  }

  if (!response.ok) {
    const err = new Error('Request failed')
    err.response = {
      status: response.status,
      data: data || { message: response.statusText || 'Request failed' }
    }
    throw err
  }

  return { data }
}

async function requestForm(method, path, formData) {
  const options = {
    method,
    credentials: 'include',
    body: formData
  }
  const token = localStorage.getItem('token') || localStorage.getItem('sc360_token')
  if (token) {
    options.headers = { Authorization: `Bearer ${token}` }
  }
  const response = await fetch(buildUrl(path), options)
  let data = null
  try {
    data = await response.json()
  } catch {
    data = null
  }
  if (!response.ok) {
    const err = new Error('Request failed')
    err.response = { status: response.status, data: data || { message: response.statusText } }
    throw err
  }
  return { data }
}

const api = {
  get(path) {
    return request('GET', path)
  },
  post(path, payload) {
    return request('POST', path, payload)
  },
  put(path, payload) {
    return request('PUT', path, payload)
  },
  patch(path, payload) {
    return request('PATCH', path, payload)
  },
  delete(path) {
    return request('DELETE', path)
  },
  postForm(path, formData) {
    return requestForm('POST', path, formData)
  }
}

export default api
