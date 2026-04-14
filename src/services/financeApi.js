import api from '@/config/api'

const p = (x) => `finance/${x}`

export const financeApi = {
  context: () => api.get(p('context.php')),
  templates: {
    list: () => api.get(p('templates.php')),
    get: (id) => api.get(`${p('templates.php')}?id=${id}`),
    create: (body) => api.post(p('templates.php'), body),
    update: (body) => api.put(p('templates.php'), body),
    delete: (id) => api.delete(`${p('templates.php')}?id=${id}`)
  },
  structures: {
    list: () => api.get(p('structures.php')),
    get: (id) => api.get(`${p('structures.php')}?id=${id}`),
    create: (body) => api.post(p('structures.php'), body),
    update: (body) => api.put(p('structures.php'), body),
    delete: (id) => api.delete(`${p('structures.php')}?id=${id}`)
  },
  invoices: {
    list: (params = {}) => {
      const q = new URLSearchParams()
      if (params.academicYearId) q.set('academicYearId', params.academicYearId)
      if (params.studyPeriodId) q.set('studyPeriodId', params.studyPeriodId)
      if (params.status) q.set('status', params.status)
      const s = q.toString()
      return api.get(`${p('invoices.php')}${s ? `?${s}` : ''}`)
    },
    get: (id) => api.get(`${p('invoices.php')}?id=${id}`),
    generate: (body) => api.post(p('invoices.php'), { action: 'generate', ...body }),
    regenerate: (invoiceId) => api.post(p('invoices.php'), { action: 'regenerate', invoiceId })
  },
  payments: {
    list: (params = {}) => {
      const q = new URLSearchParams()
      if (params.invoiceId) q.set('invoiceId', params.invoiceId)
      if (params.from) q.set('from', params.from)
      const s = q.toString()
      return api.get(`${p('payments.php')}${s ? `?${s}` : ''}`)
    },
    record: (body) => api.post(p('payments.php'), body)
  },
  dashboard: (params = {}) => {
    const q = new URLSearchParams()
    if (params.academicYearId) q.set('academicYearId', params.academicYearId)
    if (params.studyPeriodId) q.set('studyPeriodId', params.studyPeriodId)
    const s = q.toString()
    return api.get(`${p('dashboard.php')}${s ? `?${s}` : ''}`)
  },
  studentsFees: (params) => {
    const q = new URLSearchParams(params)
    return api.get(`${p('students-fees.php')}?${q}`)
  },
  enrolledStudents: (params) => {
    const q = new URLSearchParams(params)
    return api.get(`${p('enrolled-students.php')}?${q}`)
  },
  bulk: {
    generateClass: (body) => api.post(p('bulk.php'), { action: 'generate_class', ...body }),
    remindUnpaid: (body) => api.post(p('bulk.php'), { action: 'remind_unpaid', ...body })
  },
  reports: (params = {}) => {
    const q = new URLSearchParams()
    if (params.academicYearId) q.set('academicYearId', params.academicYearId)
    if (params.studyPeriodId) q.set('studyPeriodId', params.studyPeriodId)
    if (params.paymentMethod) q.set('paymentMethod', params.paymentMethod)
    const s = q.toString()
    return api.get(`${p('reports.php')}${s ? `?${s}` : ''}`)
  }
}
