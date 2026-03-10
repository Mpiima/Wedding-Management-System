import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'contributions.php'

export const useContributionsStore = defineStore('contributions', () => {
  const contributions = ref([])
  const errorMessage = ref(null)

  async function fetchContributions() {
    errorMessage.value = null
    return await api.get(ENDPOINT).then((r) => {
      contributions.value = r?.data?.data ?? []
      return r
    }).catch((err) => {
      errorMessage.value = err?.response?.data?.error || 'Failed to load'
      throw err
    })
  }

  async function createContribution(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data).then((r) => {
      if (r?.data?.data) contributions.value.unshift(r.data.data)
      return r
    }).catch((err) => {
      errorMessage.value = err?.response?.data?.error || 'Failed to create'
      throw err
    })
  }

  async function updateContribution(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data }).then((r) => {
      if (r?.data?.data) {
        const i = contributions.value.findIndex((c) => Number(c.id) === Number(id))
        if (i !== -1) contributions.value[i] = r.data.data
      }
      return r
    }).catch((err) => {
      errorMessage.value = err?.response?.data?.error || 'Failed to update'
      throw err
    })
  }

  async function deleteContribution(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } }).then((r) => {
      contributions.value = contributions.value.filter((c) => Number(c.id) !== Number(id))
      return r
    }).catch((err) => {
      errorMessage.value = err?.response?.data?.error || 'Failed to delete'
      throw err
    })
  }

  function clearError() { errorMessage.value = null }

  return { contributions, errorMessage, fetchContributions, createContribution, updateContribution, deleteContribution, clearError }
})
