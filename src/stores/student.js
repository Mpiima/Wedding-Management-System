import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getStudents } from '@/services/mockData'

/**
 * Placeholder store for student entities. Wired to mock JSON.
 */
export const useStudentStore = defineStore('student', () => {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)

  const count = computed(() => items.value.length)

  async function fetchAll() {
    loading.value = true
    error.value = null
    try {
      items.value = await getStudents()
    } catch (e) {
      error.value = e?.message || 'Failed to load records'
      items.value = []
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    loading,
    error,
    count,
    fetchAll
  }
})
