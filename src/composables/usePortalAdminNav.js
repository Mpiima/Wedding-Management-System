import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

/**
 * Show links back to school admin when staff opened the portal (e.g. ?studentId=) or user is not a student account.
 */
export function usePortalAdminNav() {
  const route = useRoute()
  const { user } = useAuth()

  const showAdminNav = computed(() => {
    const q = route.query.studentId
    if (q != null && String(q).trim() !== '') return true
    const t = String(user.value?.type || '').trim()
    return t !== 'Student'
  })

  return { showAdminNav }
}
