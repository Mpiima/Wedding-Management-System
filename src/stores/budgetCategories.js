import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'budget-categories.php'

export const useBudgetCategoriesStore = defineStore('budgetCategories', () => {
  const categories = ref([])
  const errorMessage = ref(null)

  async function fetchCategories() {
    errorMessage.value = null
    return await api
      .get(ENDPOINT)
      .then((response) => {
        if (response && response.data && Array.isArray(response.data.data)) {
          categories.value = response.data.data
        } else {
          categories.value = []
        }
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to load categories'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  async function createCategory(data) {
    errorMessage.value = null
    return await api
      .post(ENDPOINT, data)
      .then((response) => {
        if (response && response.data && response.data.data) {
          categories.value.push(response.data.data)
        }
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to create category'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  async function updateCategory(id, data) {
    errorMessage.value = null
    return await api
      .put(ENDPOINT, { id, ...data })
      .then((response) => {
        if (response && response.data && response.data.data) {
          const idx = categories.value.findIndex((c) => Number(c.id) === Number(id))
          if (idx !== -1) categories.value[idx] = response.data.data
        }
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to update category'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  async function deleteCategory(id) {
    errorMessage.value = null
    return await api
      .delete(ENDPOINT, { data: { id } })
      .then((response) => {
        categories.value = categories.value.filter((c) => Number(c.id) !== Number(id))
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to delete category'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  function clearError() {
    errorMessage.value = null
  }

  return {
    categories,
    errorMessage,
    fetchCategories,
    createCategory,
    updateCategory,
    deleteCategory,
    clearError
  }
})
