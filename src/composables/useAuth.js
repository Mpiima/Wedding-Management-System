import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'

export function useAuth() {
  const store = useAuthStore()
  const { token, profile, isAuthenticated } = storeToRefs(store)
  return {
    user: profile,
    profile,
    token,
    isAuthenticated,
    logout: store.logout,
    clearError: store.clearError
  }
}
