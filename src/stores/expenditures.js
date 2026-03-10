import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'expenditures.php'

export const useExpendituresStore = defineStore('expenditures', () => {
  const expenditures = ref([])
  const errorMessage = ref(null)

  async function fetchExpenditures() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        expenditures.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load expenditures'
        throw err
      })
  }

  async function createExpenditure(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data)
      .then((r) => {
        if (r?.data?.data) expenditures.value.unshift(r.data.data)
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to create expenditure'
        throw err
      })
  }

  async function updateExpenditure(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data })
      .then((r) => {
        if (r?.data?.data) {
          const i = expenditures.value.findIndex((e) => Number(e.id) === Number(id))
          if (i !== -1) expenditures.value[i] = r.data.data
        }
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to update expenditure'
        throw err
      })
  }

  async function deleteExpenditure(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } })
      .then((r) => {
        expenditures.value = expenditures.value.filter((e) => Number(e.id) !== Number(id))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to delete expenditure'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { expenditures, errorMessage, fetchExpenditures, createExpenditure, updateExpenditure, deleteExpenditure, clearError }
})
