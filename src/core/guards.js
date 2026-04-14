import { useAuthStore } from '@/stores/auth'
import { ROUTE_PERMISSIONS } from '@/config/permissions'

const PUBLIC_PATHS = ['/', '/login', '/request-demo', '/register']

export function requireAuth(to, _from, next) {
  const authStore = useAuthStore()
  const isPublic = PUBLIC_PATHS.includes(to.path)
  if (!authStore.isAuthenticated && !isPublic) {
    return next({ path: '/login', query: { redirect: to.fullPath } })
  }
  if (authStore.isAuthenticated && to.path === '/login') {
    const redirect = typeof to.query.redirect === 'string' ? to.query.redirect : '/dashboard'
    return next(redirect)
  }
  return next()
}

export function canAccessRoute(path, authStore) {
  const perm = ROUTE_PERMISSIONS[path]
  if (perm == null) return true
  return authStore.can(perm)
}
