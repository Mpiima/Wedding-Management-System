import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'invited-guests.php'

export const useInvitedGuestsStore = defineStore('invitedGuests', () => {
  const guests = ref([])
  const errorMessage = ref(null)

  async function fetchGuests() {
    errorMessage.value = null
    return await api.get(ENDPOINT).then((r) => {
      guests.value = r?.data?.data ?? []
      return r
    }).catch((err) => {
      errorMessage.value = err?.response?.data?.error || 'Failed to load'
      throw err
    })
  }

  async function createGuest(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data).then((r) => {
      if (r?.data?.data) {
        guests.value.push(r.data.data)
        guests.value.sort((a, b) => (a.name || '').localeCompare(b.name || ''))
      }
      return r
    }).catch((err) => {
      errorMessage.value = err?.response?.data?.error || 'Failed to create'
      throw err
    })
  }

  async function updateGuest(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data }).then((r) => {
      if (r?.data?.data) {
        const i = guests.value.findIndex((g) => Number(g.id) === Number(id))
        if (i !== -1) guests.value[i] = r.data.data
      }
      return r
    }).catch((err) => {
      errorMessage.value = err?.response?.data?.error || 'Failed to update'
      throw err
    })
  }

  async function deleteGuest(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } }).then((r) => {
      guests.value = guests.value.filter((g) => Number(g.id) !== Number(id))
      return r
    }).catch((err) => {
      errorMessage.value = err?.response?.data?.error || 'Failed to delete'
      throw err
    })
  }

  function clearError() { errorMessage.value = null }

  return { guests, errorMessage, fetchGuests, createGuest, updateGuest, deleteGuest, clearError }
})
