import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'vendor-contracts.php'

export const useVendorContractsStore = defineStore('vendorContracts', () => {
  const contracts = ref([])
  const errorMessage = ref(null)

  async function fetchContracts() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        contracts.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load contracts'
        throw err
      })
  }

  async function createContract(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data)
      .then((r) => {
        if (r?.data?.data) contracts.value.unshift(r.data.data)
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to create contract'
        throw err
      })
  }

  async function updateContract(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data })
      .then((r) => {
        if (r?.data?.data) {
          const i = contracts.value.findIndex((c) => Number(c.id) === Number(id))
          if (i !== -1) contracts.value[i] = r.data.data
        }
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to update contract'
        throw err
      })
  }

  async function deleteContract(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } })
      .then((r) => {
        contracts.value = contracts.value.filter((c) => Number(c.id) !== Number(id))
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to delete contract'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }

  return { contracts, errorMessage, fetchContracts, createContract, updateContract, deleteContract, clearError }
})
