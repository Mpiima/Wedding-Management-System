import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'vendors.php'

export const useVendorsStore = defineStore('vendors', () => {
  const vendors = ref([])
  const errorMessage = ref(null)

  async function fetchVendors() {
    errorMessage.value = null
    return await api.get(ENDPOINT).then((r) => { vendors.value = r?.data?.data ?? []; return r }).catch((err) => { errorMessage.value = err?.response?.data?.error || 'Failed'; throw err })
  }

  async function createVendor(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data).then((r) => { if (r?.data?.data) vendors.value.push(r.data.data); vendors.value.sort((a, b) => (a.name || '').localeCompare(b.name || '')); return r }).catch((err) => { errorMessage.value = err?.response?.data?.error || 'Failed'; throw err })
  }

  async function updateVendor(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data }).then((r) => { if (r?.data?.data) { const i = vendors.value.findIndex((v) => Number(v.id) === Number(id)); if (i !== -1) vendors.value[i] = r.data.data } return r }).catch((err) => { errorMessage.value = err?.response?.data?.error || 'Failed'; throw err })
  }

  async function deleteVendor(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } }).then((r) => { vendors.value = vendors.value.filter((v) => Number(v.id) !== Number(id)); return r }).catch((err) => { errorMessage.value = err?.response?.data?.error || 'Failed'; throw err })
  }

  function clearError() { errorMessage.value = null }

  return { vendors, errorMessage, fetchVendors, createVendor, updateVendor, deleteVendor, clearError }
})
