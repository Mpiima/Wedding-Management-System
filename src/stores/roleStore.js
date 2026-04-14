import { defineStore } from 'pinia'
import { ref } from 'vue'
import { PERMISSION_KEYS } from '@/config/permissions'

const STORAGE_KEY = 'sc360_ui_roles_v1'
const DEFAULT_ROLES = [
  { id: 1, name: 'Admin', created_at: '2026-01-01 09:00:00', permission_keys: ['*'] },
  {
    id: 2,
    name: 'Teacher',
    created_at: '2026-01-01 09:00:00',
    permission_keys: ['students.view', 'exams.view', 'attendance.view']
  },
  {
    id: 3,
    name: 'Parent',
    created_at: '2026-01-01 09:00:00',
    permission_keys: ['students.view', 'finance.view']
  }
]

function readLocal() {
  try {
    const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    return Array.isArray(parsed) ? parsed : [...DEFAULT_ROLES]
  } catch {
    return [...DEFAULT_ROLES]
  }
}

function writeLocal(items) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
}

export const useRoleStore = defineStore('roles', () => {
  const roles = ref([])
  const permissions = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchRoles({ tenantId } = {}) {
    loading.value = true
    error.value = null
    roles.value = readLocal()
    loading.value = false
  }

  async function fetchPermissions() {
    loading.value = true
    error.value = null
    permissions.value = PERMISSION_KEYS.map((x) => ({ key: x.key, description: x.label }))
    loading.value = false
  }

  async function updateRolePermissions(roleId, permissionKeys) {
    loading.value = true
    error.value = null
    const targetId = Number(roleId)
    const items = readLocal().map((r) =>
      Number(r.id) === targetId
        ? { ...r, permission_keys: Array.isArray(permissionKeys) ? permissionKeys : [] }
        : r
    )
    writeLocal(items)
    roles.value = items
    loading.value = false
    return { message: 'Permissions updated (UI only)' }
  }

  return { roles, permissions, loading, error, fetchRoles, fetchPermissions, updateRolePermissions }
})

