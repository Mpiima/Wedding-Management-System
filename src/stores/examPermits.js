import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getExamPermitsData } from '@/services/mockData'

const STORAGE_KEY = 'sc360_exam_permits_v1'

function safeParse(json) {
  try {
    return JSON.parse(json)
  } catch {
    return null
  }
}

export const useExamPermitsStore = defineStore('examPermits', () => {
  const exam = ref(null)
  const permits = ref([])
  const loading = ref(false)
  const error = ref(null)

  const permitsById = computed(() => new Map(permits.value.map((p) => [p.id, p])))

  function getPermitById(id) {
    return permitsById.value.get(String(id)) || null
  }

  function persist() {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        exam: exam.value,
        permits: permits.value
      })
    )
  }

  async function fetchAll() {
    loading.value = true
    error.value = null

    try {
      const stored = safeParse(localStorage.getItem(STORAGE_KEY))
      if (stored?.exam && Array.isArray(stored.permits)) {
        exam.value = stored.exam
        permits.value = stored.permits
        return
      }

      const data = await getExamPermitsData()
      exam.value = data?.exam || null
      permits.value = Array.isArray(data?.permits) ? data.permits : []
      persist()
    } catch (e) {
      error.value = e?.message || 'Failed to load exam permits data'
    } finally {
      loading.value = false
    }
  }

  function updatePermitStatus(permitId, permitStatus) {
    const id = String(permitId)
    permits.value = permits.value.map((p) => (p.id === id ? { ...p, permitStatus } : p))
    persist()
  }

  return {
    exam,
    permits,
    loading,
    error,
    permitsById,
    getPermitById,
    fetchAll,
    updatePermitStatus
  }
})

