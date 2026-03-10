import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'wedding-program.php'

export const useWeddingProgramStore = defineStore('weddingProgram', () => {
  const program = ref([])
  const errorMessage = ref(null)

  async function fetchProgram() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        program.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load wedding program'
        throw err
      })
  }

  async function createItem(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data)
      .then((r) => {
        if (r?.data?.data) program.value.push(r.data.data)
        program.value.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to create item'
        throw err
      })
  }

  async function updateItem(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data })
      .then((r) => {
        if (r?.data?.data) {
          const i = program.value.findIndex((p) => Number(p.id) === Number(id))
          if (i !== -1) program.value[i] = r.data.data
        }
        program.value.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to update item'
        throw err
      })
  }

  async function deleteItem(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } })
      .then((r) => {
        program.value = program.value.filter((p) => Number(p.id) !== Number(id))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to delete item'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { program, errorMessage, fetchProgram, createItem, updateItem, deleteItem, clearError }
})
