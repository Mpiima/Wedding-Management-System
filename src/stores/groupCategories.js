import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'group-categories.php'

export const useGroupCategoriesStore = defineStore('groupCategories', () => {
  const categories = ref([])
  const errorMessage = ref(null)

  async function fetchCategories() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        categories.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load categories'
        throw err
      })
  }

  async function createCategory(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data)
      .then((r) => {
        if (r?.data?.data) categories.value.push(r.data.data)
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to create'
        throw err
      })
  }

  async function updateCategory(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data })
      .then((r) => {
        if (r?.data?.data) {
          const i = categories.value.findIndex((c) => Number(c.id) === Number(id))
          if (i !== -1) categories.value[i] = r.data.data
        }
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to update'
        throw err
      })
  }

  async function deleteCategory(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } })
      .then((r) => {
        categories.value = categories.value.filter((c) => Number(c.id) !== Number(id))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to delete'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { categories, errorMessage, fetchCategories, createCategory, updateCategory, deleteCategory, clearError }
})
