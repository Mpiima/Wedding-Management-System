<template>
  <div class="page-shell">
    <PageHeader
      title="Roles & Permissions"
      description="Configure what each role can access (real API)."
    >
      <template #actions>
        <Button variant="secondary" @click="reload">Refresh</Button>
      </template>
    </PageHeader>

    <DataTable
      title="Roles"
      :columns="roleCols"
      :data="rolesRows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="8"
    >
      <template #cell-permCount="{ value }">
        <span class="font-medium text-slate-900">{{ value }}</span>
      </template>
      <template #actions="{ row }">
        <Button variant="secondary" class="!px-2 !py-1 text-xs" @click="openPermissions(row)">
          Manage permissions
        </Button>
      </template>
    </DataTable>

    <Modal v-model="permModalOpen" :title="`Permissions for ${targetRole?.name || ''}`">
      <div class="text-sm text-slate-600">Toggle permissions for this role.</div>

      <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label
          v-for="p in permissions"
          :key="p.key"
          class="flex items-start gap-3 rounded-xl border border-sc-border bg-white p-3 text-sm text-slate-700"
        >
          <input
            type="checkbox"
            class="mt-1 h-4 w-4 accent-brand-600"
            :disabled="isAdminRole"
            v-model="selectedPermissionKeys"
            :value="p.key"
          />
          <span class="min-w-0">
            <span class="block font-medium text-slate-900">{{ p.key }}</span>
            <span class="block text-xs text-slate-500">{{ p.description }}</span>
          </span>
        </label>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="permModalOpen = false">Cancel</Button>
          <Button @click="savePermissions" :disabled="isAdminRole">Save</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import { useRoleStore } from '@/stores/roleStore'
import { useAuthStore } from '@/stores/auth'

const roleStore = useRoleStore()
const authStore = useAuthStore()

const permissions = computed(() => roleStore.permissions)
const rolesRows = computed(() =>
  roleStore.roles.map((r) => ({
    ...r,
    permCount: Array.isArray(r.permission_keys) ? r.permission_keys.length : 0
  }))
)

const roleCols = [
  { key: 'name', label: 'Role', sortable: true },
  { key: 'permCount', label: 'Permissions', sortable: false }
]

const permModalOpen = ref(false)
const targetRole = ref(null)
const selectedPermissionKeys = ref([])

const isAdminRole = computed(
  () => Array.isArray(targetRole.value?.permission_keys) && targetRole.value.permission_keys.includes('*')
)

function openPermissions(row) {
  targetRole.value = row
  if (Array.isArray(row.permission_keys) && row.permission_keys.includes('*')) {
    selectedPermissionKeys.value = permissions.value.map((p) => p.key)
  } else {
    selectedPermissionKeys.value = Array.isArray(row.permission_keys) ? [...row.permission_keys] : []
  }
  permModalOpen.value = true
}

async function reload() {
  await Promise.all([
    roleStore.fetchPermissions(),
    roleStore.fetchRoles({ tenantId: Number(authStore.profile?.tenant_id) || 0 })
  ])
}

async function savePermissions() {
  if (!targetRole.value) return
  await roleStore.updateRolePermissions(targetRole.value.id, selectedPermissionKeys.value)
  permModalOpen.value = false
  await reload()
}

onMounted(reload)
</script>

