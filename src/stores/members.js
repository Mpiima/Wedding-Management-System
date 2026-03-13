import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'members.php'

export const useMembersStore = defineStore('members', () => {
  const members = ref([])
  const errorMessage = ref(null)

  async function fetchMembers() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        members.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load members'
        throw err
      })
  }

  async function createMember(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data)
      .then((r) => {
        if (r?.data?.data) members.value.push(r.data.data)
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to create'
        throw err
      })
  }

  async function updateMember(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data })
      .then((r) => {
        if (r?.data?.data) {
          const i = members.value.findIndex((m) => Number(m.id) === Number(id))
          if (i !== -1) members.value[i] = r.data.data
        }
        return r
      })
      .catch((err) => {
        const data = err?.response?.data
        errorMessage.value = data?.detail || data?.error || 'Failed to update'
        throw err
      })
  }

  async function deleteMember(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } })
      .then((r) => {
        members.value = members.value.filter((m) => Number(m.id) !== Number(id))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to delete'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { members, errorMessage, fetchMembers, createMember, updateMember, deleteMember, clearError }
})
