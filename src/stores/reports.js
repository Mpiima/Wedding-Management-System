import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'reports.php'

export const useReportsStore = defineStore('reports', () => {
  const report = ref(null)
  const errorMessage = ref(null)

  async function fetchReport() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        report.value = r?.data?.data ?? null
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load report'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { report, errorMessage, fetchReport, clearError }
})
