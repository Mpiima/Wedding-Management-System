import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'budget-items.php'

export const useBudgetItemsStore = defineStore('budgetItems', () => {
  const items = ref([])
  const errorMessage = ref(null)

  async function fetchItems(categoryId = null) {
    errorMessage.value = null
    const params = categoryId != null ? { params: { category_id: categoryId } } : {}
    return await api
      .get(ENDPOINT, params)
      .then((response) => {
        if (response && response.data && Array.isArray(response.data.data)) {
          items.value = response.data.data
        } else {
          items.value = []
        }
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to load items'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  async function createItem(data) {
    errorMessage.value = null
    const payload = { ...data }
    if (payload.cost == null && payload.quantity != null && payload.unit_amount != null) {
      payload.cost = Number(payload.quantity) * Number(payload.unit_amount)
    }
    return await api
      .post(ENDPOINT, payload)
      .then((response) => {
        if (response && response.data && response.data.data) {
          items.value.push(response.data.data)
        }
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to create item'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  async function updateItem(id, data) {
    errorMessage.value = null
    const payload = { id, ...data }
    if (payload.quantity != null && payload.unit_amount != null && payload.cost == null) {
      payload.cost = Number(payload.quantity) * Number(payload.unit_amount)
    }
    return await api
      .put(ENDPOINT, payload)
      .then((response) => {
        if (response && response.data && response.data.data) {
          const idx = items.value.findIndex((i) => Number(i.id) === Number(id))
          if (idx !== -1) items.value[idx] = response.data.data
        }
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to update item'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  async function deleteItem(id) {
    errorMessage.value = null
    return await api
      .delete(ENDPOINT, { data: { id } })
      .then((response) => {
        items.value = items.value.filter((i) => Number(i.id) !== Number(id))
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to delete item'
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
    items,
    errorMessage,
    fetchItems,
    createItem,
    updateItem,
    deleteItem,
    clearError
  }
})
