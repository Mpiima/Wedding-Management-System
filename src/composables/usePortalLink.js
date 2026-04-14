import { computed } from 'vue'
import { useRoute } from 'vue-router'

/**
 * Preserves `studentId` on the URL when staff open the student portal from the directory
 * so sidebar / header links stay on the same pupil context.
 */
export function usePortalLink() {
  const route = useRoute()

  const portalQuery = computed(() => {
    const id = route.query.studentId
    if (id == null || id === '') return {}
    return { studentId: String(id) }
  })

  function portalTo(path) {
    const q = portalQuery.value
    return Object.keys(q).length ? { path, query: { ...q } } : path
  }

  return { portalQuery, portalTo }
}
