import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'meeting-minutes.php'

export const useMeetingMinutesStore = defineStore('meetingMinutes', () => {
  const minutes = ref([])
  const errorMessage = ref(null)

  async function fetchMinutes() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        minutes.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load meeting minutes'
        throw err
      })
  }

  async function createMinutes(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data)
      .then((r) => {
        if (r?.data?.data) minutes.value.unshift(r.data.data)
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to create minutes'
        throw err
      })
  }

  async function updateMinutes(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data })
      .then((r) => {
        if (r?.data?.data) {
          const i = minutes.value.findIndex((m) => Number(m.id) === Number(id))
          if (i !== -1) minutes.value[i] = r.data.data
        }
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to update minutes'
        throw err
      })
  }

  async function deleteMinutes(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } })
      .then((r) => {
        minutes.value = minutes.value.filter((m) => Number(m.id) !== Number(id))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to delete minutes'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { minutes, errorMessage, fetchMinutes, createMinutes, updateMinutes, deleteMinutes, clearError }
})
