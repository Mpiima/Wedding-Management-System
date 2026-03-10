import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'

/**
 * Composable that delegates to the Pinia auth store.
 * Use this for components that need user, isAuthenticated, or logout.
 * For login, use the store directly (userLogin).
 */
export function useAuth() {
  const store = useAuthStore()
  const { token, profile, isAuthenticated } = storeToRefs(store)
  const user = profile
  return {
    user,
    profile,
    token,
    isAuthenticated,
    logout: store.logout,
    clearError: store.clearError
  }
}
