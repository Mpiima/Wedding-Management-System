import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'meetings.php'

export const useMeetingsStore = defineStore('meetings', () => {
  const meetings = ref([])
  const errorMessage = ref(null)

  async function fetchMeetings() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        meetings.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load meetings'
        throw err
      })
  }

  async function createMeeting(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data)
      .then((r) => {
        if (r?.data?.data) meetings.value.unshift(r.data.data)
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to create meeting'
        throw err
      })
  }

  async function updateMeeting(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data })
      .then((r) => {
        if (r?.data?.data) {
          const i = meetings.value.findIndex((m) => Number(m.id) === Number(id))
          if (i !== -1) meetings.value[i] = r.data.data
        }
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to update meeting'
        throw err
      })
  }

  async function deleteMeeting(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } })
      .then((r) => {
        meetings.value = meetings.value.filter((m) => Number(m.id) !== Number(id))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to delete meeting'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { meetings, errorMessage, fetchMeetings, createMeeting, updateMeeting, deleteMeeting, clearError }
})
