import api from '@/config/api'

function qs(params) {
  if (!params || typeof params !== 'object') return ''
  const q = new URLSearchParams()
  Object.entries(params).forEach(([k, v]) => {
    if (v === undefined || v === null || v === '') return
    q.set(k, String(v))
  })
  const s = q.toString()
  return s ? `?${s}` : ''
}

/** Enrolled students for a year + study period (defaults to active year/period). */
export const studentsApi = {
  directory: (params) => api.get(`students/directory.php${qs(params)}`),
  /** Student + enrollment + meta for portal (session school + studentId query or portal_student_id session). */
  portalContext: (params) => api.get(`students/portal-context.php${qs(params)}`),
  profile: (studentId) => api.get(`students/profile.php?id=${encodeURIComponent(String(studentId))}`),
  updateProfile: (studentId, fields) =>
    api.put('students/profile.php', { studentId: Number(studentId), ...fields }),
  uploadPhoto: (studentId, file) => {
    const fd = new FormData()
    fd.append('studentId', String(studentId))
    fd.append('file', file)
    return api.postForm('students/photo.php', fd)
  },
  /** GET: { hasPortalLogin, username } */
  portalAccessStatus: (studentId) =>
    api.get(`students/portal-access.php?studentId=${encodeURIComponent(String(studentId))}`),
  /** POST: optional password (auto-generated if omitted). Returns username + password once. */
  generatePortalLogin: (studentId, password) => {
    const payload = { studentId: Number(studentId) }
    if (password != null && String(password).trim() !== '') {
      payload.password = String(password).trim()
    }
    return api.post('students/portal-access.php', payload)
  }
}
