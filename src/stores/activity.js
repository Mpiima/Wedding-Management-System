import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'activity.php'

export const useActivityStore = defineStore('activity', () => {
  const items = ref([])
  const errorMessage = ref(null)

  async function fetchActivity() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        items.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load activity'
        throw err
      })
  }

  return { items, errorMessage, fetchActivity }
})
