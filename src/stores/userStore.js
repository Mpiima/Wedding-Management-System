import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const STORAGE_KEY = 'sc360_ui_users_v1'
const DEFAULT_USERS = [
  {
    id: 1,
    tenant_id: 1,
    name: 'Main Admin',
    email: 'admin@kyu.ac.ug',
    phone: '+256700000111',
    role_id: 1,
    role_name: 'Admin',
    status: 'Active',
    created_at: '2026-01-03 10:00:00'
  },
  {
    id: 2,
    tenant_id: 1,
    name: 'Teacher Jane',
    email: 'jane@kyu.ac.ug',
    phone: '+256700000112',
    role_id: 2,
    role_name: 'Teacher',
    status: 'Active',
    created_at: '2026-01-10 09:30:00'
  }
]

function readLocal() {
  try {
    const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    return Array.isArray(parsed) ? parsed : [...DEFAULT_USERS]
  } catch {
    return [...DEFAULT_USERS]
  }
}

function writeLocal(items) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
}

export const useUserStore = defineStore('users', () => {
  const users = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchUsers({ tenantId } = {}) {
    loading.value = true
    error.value = null
    const authStore = useAuthStore()
    const activeTenant = Number(tenantId || authStore.profile?.tenant_id || 1)
    users.value = readLocal().filter((u) => Number(u.tenant_id) === activeTenant)
    loading.value = false
  }

  async function createUser(payload) {
    loading.value = true
    error.value = null
    const authStore = useAuthStore()
    const activeTenant = Number(authStore.profile?.tenant_id || 1)
    const all = readLocal()
    const nextId = all.reduce((max, u) => Math.max(max, Number(u.id) || 0), 0) + 1
    const roleMap = { 1: 'Admin', 2: 'Teacher', 3: 'Parent' }
    const newUser = {
      id: nextId,
      tenant_id: activeTenant,
      name: String(payload?.name || '').trim() || `User ${nextId}`,
      email: String(payload?.email || '').trim() || `user${nextId}@school.local`,
      phone: payload?.phone || null,
      role_id: Number(payload?.role_id || 2),
      role_name: roleMap[Number(payload?.role_id || 2)] || 'Teacher',
      status: payload?.status || 'Active',
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
    }
    all.unshift(newUser)
    writeLocal(all)
    await fetchUsers()
    loading.value = false
    return newUser
  }

  async function updateUser(id, payload) {
    loading.value = true
    error.value = null
    const targetId = Number(id)
    const roleMap = { 1: 'Admin', 2: 'Teacher', 3: 'Parent' }
    const all = readLocal().map((u) =>
      Number(u.id) === targetId
        ? {
            ...u,
            ...(payload || {}),
            role_name: payload?.role_id ? roleMap[Number(payload.role_id)] || u.role_name : u.role_name
          }
        : u
    )
    writeLocal(all)
    await fetchUsers()
    loading.value = false
    return { message: 'User updated (UI only)' }
  }

  async function deleteUser(id) {
    loading.value = true
    error.value = null
    const targetId = Number(id)
    const all = readLocal().filter((u) => Number(u.id) !== targetId)
    writeLocal(all)
    await fetchUsers()
    loading.value = false
    return { message: 'User deleted (UI only)' }
  }

  return { users, loading, error, fetchUsers, createUser, updateUser, deleteUser }
})

