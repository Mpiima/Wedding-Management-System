import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'roles.php'

export const useRolesStore = defineStore('roles', () => {
  const roles = ref([])
  const errorMessage = ref(null)

  async function fetchRoles() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        roles.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load roles'
        throw err
      })
  }

  async function createRole(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data)
      .then((r) => {
        if (r?.data?.data) roles.value.push(r.data.data)
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to create'
        throw err
      })
  }

  async function updateRole(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data })
      .then((r) => {
        if (r?.data?.data) {
          const i = roles.value.findIndex((x) => Number(x.id) === Number(id))
          if (i !== -1) roles.value[i] = r.data.data
        }
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to update'
        throw err
      })
  }

  async function deleteRole(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } })
      .then((r) => {
        roles.value = roles.value.filter((x) => Number(x.id) !== Number(id))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to delete'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { roles, errorMessage, fetchRoles, createRole, updateRole, deleteRole, clearError }
})
