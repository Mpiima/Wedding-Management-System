import api, { baseURL } from '@/config/api'

const p = (x) => `admissions/${x}`

export function uploadsPublicUrl(relativePath) {
  if (!relativePath) return ''
  const root = baseURL.replace(/\/api\/?$/i, '')
  if (!root) return `/${relativePath.replace(/^\//, '')}`
  return `${root}/${relativePath.replace(/^\//, '')}`
}

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

export const admissionsApi = {
  list: (params) => api.get(`${p('applicants.php')}${qs(params)}`),
  get: (id) => api.get(`${p('applicants.php')}?id=${id}`),
  create: (body) => api.post(p('applicants.php'), body),
  update: (body) => api.put(p('applicants.php'), body),
  delete: (id) => api.delete(`${p('applicants.php')}?id=${id}`),

  move: (body) => api.post(p('move.php'), body),
  accept: (id) => api.post(p('accept-app.php'), { id }),
  reject: (body) => api.post(p('xreject.php'), body),
  enroll: (body) => api.post(p('enroll.php'), body),
  bulkEnroll: (body) => api.post(p('bulk-enroll.php'), body),

  stats: () => api.get(p('stats.php')),
  context: () => api.get(p('context.php')),
  duplicateCheck: (firstName, lastName, parentPhone) =>
    api.get(
      `${p('duplicate.php')}?firstName=${encodeURIComponent(firstName)}&lastName=${encodeURIComponent(lastName)}&parentPhone=${encodeURIComponent(parentPhone)}`
    ),

  addComment: (body) => api.post(p('xcomments.php'), body),

  uploadDocument(applicantId, docType, file) {
    const fd = new FormData()
    fd.append('applicantId', String(applicantId))
    fd.append('docType', docType)
    fd.append('file', file)
    return api.postForm(p('upload.php'), fd)
  },

  enrollmentsList: (params) => api.get(`${p('enrollments-list.php')}${qs(params)}`),

  feePreview: (params) => api.get(`${p('fee-preview.php')}${qs(params)}`),

  enrollmentPatch: (body) => api.post(p('enrollment-patch.php'), body),

  enrollmentWithdraw: (body) => api.post(p('enrollment-withdraw.php'), body)
}
