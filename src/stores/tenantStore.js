import { defineStore } from 'pinia'
import { ref } from 'vue'

const STORAGE_KEY = 'sc360_ui_tenants_v1'
const DEFAULT_TENANTS = [
  {
    id: 1,
    name: 'Kyambogo University',
    email: 'admin@kyu.ac.ug',
    phone: '+256700000001',
    address: 'Kampala, Uganda',
    status: 'Active',
    created_at: '2026-01-03 09:00:00'
  },
  {
    id: 2,
    name: 'Springfield Academy',
    email: 'hello@springfield.ac',
    phone: '+256700000002',
    address: 'Mbarara, Uganda',
    status: 'Trial',
    created_at: '2026-02-10 11:20:00'
  }
]

function readLocal() {
  try {
    const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    return Array.isArray(parsed) ? parsed : [...DEFAULT_TENANTS]
  } catch {
    return [...DEFAULT_TENANTS]
  }
}

function writeLocal(items) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
}

export const useTenantStore = defineStore('tenant', () => {
  const tenants = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchTenants() {
    loading.value = true
    error.value = null
    tenants.value = readLocal()
    loading.value = false
  }

  async function createTenant(payload) {
    loading.value = true
    error.value = null
    const items = readLocal()
    const nextId = items.reduce((max, t) => Math.max(max, Number(t.id) || 0), 0) + 1
    const newTenant = {
      id: nextId,
      name: String(payload?.name || '').trim() || `Tenant ${nextId}`,
      email: String(payload?.email || '').trim() || `tenant${nextId}@school.local`,
      phone: payload?.phone || null,
      address: payload?.address || null,
      status: payload?.status || 'Trial',
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
    }
    items.unshift(newTenant)
    writeLocal(items)
    tenants.value = items
    loading.value = false
    return {
      message: 'Tenant created (UI only)',
      tenant: newTenant,
      admin_user: {
        email: newTenant.email,
        generated_password: 'demo12345'
      }
    }
  }

  async function updateTenant(id, payload) {
    loading.value = true
    error.value = null
    const targetId = Number(id)
    const items = readLocal().map((t) =>
      Number(t.id) === targetId ? { ...t, ...(payload || {}) } : t
    )
    writeLocal(items)
    tenants.value = items
    loading.value = false
    return { message: 'Tenant updated (UI only)' }
  }

  async function deleteTenant(id) {
    loading.value = true
    error.value = null
    const targetId = Number(id)
    const items = readLocal().filter((t) => Number(t.id) !== targetId)
    writeLocal(items)
    tenants.value = items
    loading.value = false
    return { message: 'Tenant deleted (UI only)' }
  }

  return { tenants, loading, error, fetchTenants, createTenant, updateTenant, deleteTenant }
})

