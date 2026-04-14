import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getCanteenData } from '@/services/mockData'

const STORAGE_KEY = 'sc360_canteen_v1'

function safeParse(json) {
  try {
    return JSON.parse(json)
  } catch {
    return null
  }
}

export const useCanteenStore = defineStore('canteen', () => {
  const items = ref([])
  const wallets = ref([])
  const transactions = ref([])
  const loading = ref(false)
  const error = ref(null)

  const itemsById = computed(() => new Map(items.value.map((i) => [i.id, i])))
  const walletsByStudentId = computed(() => new Map(wallets.value.map((w) => [w.studentId, w])))

  function getStudentName(studentId) {
    return walletsByStudentId.value.get(studentId)?.studentName || studentId
  }

  function getItemName(itemId) {
    return itemsById.value.get(itemId)?.name || itemId
  }

  function persist() {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        items: items.value,
        wallets: wallets.value,
        transactions: transactions.value
      })
    )
  }

  async function fetchAll() {
    loading.value = true
    error.value = null

    try {
      const stored = safeParse(localStorage.getItem(STORAGE_KEY))
      if (stored?.items && stored?.wallets && stored?.transactions) {
        items.value = Array.isArray(stored.items) ? stored.items : []
        wallets.value = Array.isArray(stored.wallets) ? stored.wallets : []
        transactions.value = Array.isArray(stored.transactions) ? stored.transactions : []
        return
      }

      const data = await getCanteenData()
      items.value = Array.isArray(data?.items) ? data.items : []
      wallets.value = Array.isArray(data?.wallets) ? data.wallets : []
      transactions.value = Array.isArray(data?.transactions) ? data.transactions : []
      persist()
    } catch (e) {
      error.value = e?.message || 'Failed to load canteen data'
    } finally {
      loading.value = false
    }
  }

  function updateItem(id, patch) {
    items.value = items.value.map((i) => (i.id === id ? { ...i, ...patch } : i))
    persist()
  }

  function addItem(item) {
    items.value = [
      ...items.value,
      {
        ...item,
        id: item.id ? String(item.id) : String(Date.now())
      }
    ]
    persist()
  }

  return {
    items,
    wallets,
    transactions,
    loading,
    error,
    itemsById,
    walletsByStudentId,
    getStudentName,
    getItemName,
    fetchAll,
    updateItem,
    addItem
  }
})

