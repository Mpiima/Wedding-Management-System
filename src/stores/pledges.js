import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'pledges.php'

export const usePledgesStore = defineStore('pledges', () => {
  const pledges = ref([])
  const selectedPledge = ref(null) // single pledge with payments for receipt/card
  const errorMessage = ref(null)

  async function fetchPledges() {
    errorMessage.value = null
    return await api.get(ENDPOINT)
      .then((r) => {
        pledges.value = r?.data?.data ?? []
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load pledges'
        throw err
      })
  }

  async function fetchPledgeById(pledgeId) {
    errorMessage.value = null
    return await api.get(ENDPOINT, { params: { pledge_id: pledgeId } })
      .then((r) => {
        selectedPledge.value = r?.data?.data ?? null
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to load pledge'
        throw err
      })
  }

  async function createPledge(data) {
    errorMessage.value = null
    return await api.post(ENDPOINT, data)
      .then((r) => {
        if (r?.data?.data) pledges.value.unshift(r.data.data)
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to create pledge'
        throw err
      })
  }

  async function updatePledge(id, data) {
    errorMessage.value = null
    return await api.put(ENDPOINT, { id, ...data })
      .then((r) => {
        if (r?.data?.data) {
          const i = pledges.value.findIndex((p) => Number(p.id) === Number(id))
          if (i !== -1) pledges.value[i] = r.data.data
          if (selectedPledge.value && Number(selectedPledge.value.id) === Number(id)) {
            selectedPledge.value = { ...selectedPledge.value, ...r.data.data }
          }
        }
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to update pledge'
        throw err
      })
  }

  async function recordPayment(pledgeId, amount, paidAt) {
    errorMessage.value = null
    return await api.post(ENDPOINT, { action: 'pay', pledge_id: pledgeId, amount, paid_at: paidAt })
      .then((r) => {
        const pledgeAmountPaid = r?.data?.pledge_amount_paid
        const i = pledges.value.findIndex((p) => Number(p.id) === Number(pledgeId))
        if (i !== -1 && pledgeAmountPaid != null) pledges.value[i].amount_paid = pledgeAmountPaid
        if (selectedPledge.value && Number(selectedPledge.value.id) === Number(pledgeId)) {
          selectedPledge.value.amount_paid = pledgeAmountPaid
          if (r?.data?.data) selectedPledge.value.payments = [r.data.data, ...(selectedPledge.value.payments || [])]
        }
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to record payment'
        throw err
      })
  }

  async function deletePledge(id) {
    errorMessage.value = null
    return await api.delete(ENDPOINT, { data: { id } })
      .then((r) => {
        pledges.value = pledges.value.filter((p) => Number(p.id) !== Number(id))
        if (selectedPledge.value && Number(selectedPledge.value.id) === Number(id)) selectedPledge.value = null
        return r
      })
      .catch((err) => {
        errorMessage.value = err?.response?.data?.error || 'Failed to delete pledge'
        throw err
      })
  }

  function clearError() { errorMessage.value = null }
  function clearSelected() { selectedPledge.value = null }

  return {
    pledges,
    selectedPledge,
    errorMessage,
    fetchPledges,
    fetchPledgeById,
    createPledge,
    updatePledge,
    recordPayment,
    deletePledge,
    clearError,
    clearSelected
  }
})
