import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'committee.php'

export const useCommitteeStore = defineStore('committee', () => {
  const list = ref([])
  const errorMessage = ref(null)

  async function fetchCommittee() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        list.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load committee'
        throw err
      })
  }

  async function assignRole(memberId, roleId) {
    errorMessage.value = null
    return await api.post(ENDPOINT, { member_id: memberId, role_id: roleId })
      .then((r) => r)
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to assign role'
        throw err
      })
  }

  async function unassignRole(memberId, roleId) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { member_id: memberId, role_id: roleId } })
      .then((r) => r)
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to unassign'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { list, errorMessage, fetchCommittee, assignRole, unassignRole, clearError }
})
