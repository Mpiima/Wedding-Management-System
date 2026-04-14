/**
 * Resolve a stored relative path (e.g. uploads/students/...) to a full URL using the app origin.
 */
export function publicUploadUrl(relativePath) {
  if (relativePath == null || relativePath === '') return ''
  const s = String(relativePath).trim()
  if (/^https?:\/\//i.test(s)) return s
  const apiBase = (import.meta.env.VITE_APP_BASE_URL || '').replace(/\/$/, '')
  const root = apiBase.replace(/\/api\/?$/i, '') || ''
  const path = s.replace(/^\/+/, '')
  if (!root) return `/${path}`
  return `${root}/${path}`
}

export function formatCurrency(value, currency = 'UGX') {
  if (value == null || value === '') return '—'
  const n = Number(value)
  if (Number.isNaN(n)) return String(value)
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 0 }).format(n)
  } catch {
    return `${n.toLocaleString()} ${currency}`
  }
}
