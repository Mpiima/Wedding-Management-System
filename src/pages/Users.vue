<template>
  <div class="page-shell">
    <PageHeader title="Users" description="Manage users within the current tenant (multi-tenant).">
      <template #actions>
        <Button variant="secondary" @click="reload">Refresh</Button>
        <Button @click="openCreate">Add user</Button>
      </template>
    </PageHeader>

    <DataTable
      title="Tenant users"
      :columns="cols"
      :data="usersRows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="8"
    >
      <template #cell-status="{ value }">
        <Badge :tone="statusTone(value)">{{ value }}</Badge>
      </template>
      <template #actions="{ row }">
        <div class="flex flex-wrap items-center justify-end gap-2">
          <Button variant="ghost" class="!px-2 !py-1 text-xs" @click="openEdit(row)">Edit</Button>
          <Button variant="secondary" class="!px-2 !py-1 text-xs" @click="remove(row.id)">
            Delete
          </Button>
        </div>
      </template>
    </DataTable>

    <Modal v-model="modalOpen" :title="editMode ? 'Edit user' : 'Add user'">
      <div class="space-y-4">
        <FormInput v-model="form.name" label="Name" placeholder="e.g., John Doe" required />
        <FormInput v-model="form.email" label="Email" placeholder="user@school.com" required />
        <FormInput v-model="form.phone" label="Phone" placeholder="+256..." />

        <SelectInput v-model="form.role_id" label="Role" :options="roleOptions" placeholder="Select role" required />

        <SelectInput
          v-model="form.status"
          label="Status"
          :options="statusOptions"
          placeholder="Select status"
        />

        <FormInput
          v-if="!editMode"
          v-model="form.password"
          label="Password"
          placeholder="Set initial password"
          required
        />
        <p v-if="editMode" class="text-xs text-slate-500">
          Password changes are handled via the separate change-password API endpoint.
        </p>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="modalOpen = false">Cancel</Button>
          <Button @click="save">Save</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { useUserStore } from '@/stores/userStore'
import { useRoleStore } from '@/stores/roleStore'
import { useAuthStore } from '@/stores/auth'

const userStore = useUserStore()
const roleStore = useRoleStore()
const authStore = useAuthStore()

const tenantId = computed(() => Number(authStore.profile?.tenant_id) || 0)

const cols = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'role_name', label: 'Role', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'phone', label: 'Phone' }
]

const usersRows = computed(() => userStore.users)

function statusTone(s) {
  if (s === 'Active') return 'success'
  if (s === 'Suspended') return 'danger'
  if (s === 'Trial') return 'warning'
  return 'neutral'
}

const statusOptions = [
  { value: 'Active', label: 'Active' },
  { value: 'Suspended', label: 'Suspended' }
]

const roleOptions = computed(() => roleStore.roles.map((r) => ({ value: r.id, label: r.name })))

const modalOpen = ref(false)
const editMode = ref(false)
const form = reactive({
  id: null,
  name: '',
  email: '',
  phone: '',
  role_id: '',
  status: 'Active',
  password: ''
})

async function reload() {
  await Promise.all([roleStore.fetchRoles({ tenantId: tenantId.value }), userStore.fetchUsers()])
}

function openCreate() {
  editMode.value = false
  form.id = null
  form.name = ''
  form.email = ''
  form.phone = ''
  form.role_id = roleOptions.value[0]?.value || ''
  form.status = 'Active'
  form.password = ''
  modalOpen.value = true
}

function openEdit(row) {
  editMode.value = true
  form.id = row.id
  form.name = row.name ?? ''
  form.email = row.email ?? ''
  form.phone = row.phone ?? ''
  form.role_id = row.role_id ?? row.roleId ?? ''
  form.status = row.status ?? 'Active'
  form.password = ''
  modalOpen.value = true
}

async function save() {
  const payload = {
    name: form.name,
    email: form.email,
    phone: form.phone || null,
    role_id: Number(form.role_id),
    status: form.status
  }
  if (!editMode.value) {
    payload.password = form.password
  }

  if (editMode.value) {
    await userStore.updateUser(form.id, payload)
  } else {
    await userStore.createUser(payload)
  }

  modalOpen.value = false
}

async function remove(id) {
  if (!window.confirm('Delete this user?')) return
  await userStore.deleteUser(id)
}

onMounted(reload)
</script>

