<template>
  <div class="page-shell">
    <PageHeader title="Tenants" description="Manage schools (multi-tenant SaaS).">
      <template #actions>
        <Button v-if="isSuperAdmin" @click="openCreate" variant="secondary">Create tenant</Button>
        <Button variant="secondary" @click="reload">Refresh</Button>
      </template>
    </PageHeader>

    <DataTable
      title="Schools"
      :columns="cols"
      :data="rows"
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
          <Button variant="ghost" class="!px-2 !py-1 text-xs" @click="openView(row)">View</Button>
          <Button
            v-if="isSuperAdmin"
            variant="secondary"
            class="!px-2 !py-1 text-xs"
            :disabled="row.status === 'Trial'"
            @click="openEdit(row)"
          >
            Edit
          </Button>
          <Button
            variant="secondary"
            class="!px-2 !py-1 text-xs"
            :disabled="!isSuperAdmin || row.status === 'Suspended'"
            @click="setStatus(row, 'Suspended')"
          >
            Suspend
          </Button>
          <Button
            variant="secondary"
            class="!px-2 !py-1 text-xs"
            :disabled="!isSuperAdmin || row.status === 'Active'"
            @click="setStatus(row, 'Active')"
          >
            Activate
          </Button>
          <Button
            v-if="isSuperAdmin"
            variant="secondary"
            class="!px-2 !py-1 text-xs !border-rose-300 !bg-rose-600 !text-white hover:!bg-rose-700 hover:!border-rose-400"
            :disabled="row.status === 'Trial'"
            @click="remove(row)"
          >
            Delete
          </Button>
        </div>
      </template>
    </DataTable>

    <Modal v-model="viewOpen" title="Tenant details">
      <div class="space-y-3 text-sm text-slate-700">
        <div><span class="font-medium text-slate-900">Name:</span> {{ target?.name }}</div>
        <div><span class="font-medium text-slate-900">Email:</span> {{ target?.email }}</div>
        <div><span class="font-medium text-slate-900">Phone:</span> {{ target?.phone || '—' }}</div>
        <div><span class="font-medium text-slate-900">Address:</span> {{ target?.address || '—' }}</div>
        <div class="flex items-center gap-2">
          <span class="font-medium text-slate-900">Status:</span>
          <Badge :tone="statusTone(target?.status)">{{ target?.status }}</Badge>
        </div>
        <div><span class="font-medium text-slate-900">Created:</span> {{ target?.created_at }}</div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="viewOpen = false">Close</Button>
        </div>
      </template>
    </Modal>

    <Modal v-model="createOpen" title="Create tenant">
      <div class="space-y-4">
        <FormInput v-model="form.name" label="School name" placeholder="e.g., Springfield Academy" required />
        <FormInput v-model="form.email" label="Contact email" placeholder="admin@school.com" required />
        <FormInput v-model="form.phone" label="Phone" placeholder="+256..." />
        <FormInput v-model="form.address" label="Address" placeholder="Campus / district" />
        <SelectInput
          v-model="form.status"
          label="Status"
          :options="statusOptions"
          placeholder="Select"
        />
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="createOpen = false">Cancel</Button>
          <Button @click="createTenant">Create</Button>
        </div>
      </template>
    </Modal>

    <Modal v-model="editOpen" title="Edit tenant">
      <div class="space-y-4">
        <FormInput v-model="editForm.name" label="School name" placeholder="e.g., Springfield Academy" required />
        <FormInput v-model="editForm.email" label="Contact email" placeholder="admin@school.com" required />
        <FormInput v-model="editForm.phone" label="Phone" placeholder="+256..." />
        <FormInput v-model="editForm.address" label="Address" placeholder="Campus / district" />
        <SelectInput v-model="editForm.status" label="Status" :options="statusOptions" placeholder="Select" />
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="editOpen = false">Cancel</Button>
          <Button @click="saveEdit" :disabled="!editTarget">Save</Button>
        </div>
      </template>
    </Modal>

    <Modal v-model="createdOpen" title="Admin created">
      <p class="text-sm text-slate-600">
        Tenant admin user was created successfully. Use the generated password below.
      </p>
      <div class="mt-4 space-y-2 text-sm text-slate-700">
        <div><span class="font-medium text-slate-900">Email:</span> {{ createdAdmin?.email }}</div>
        <div><span class="font-medium text-slate-900">Generated password:</span> <span class="font-mono">{{ createdAdmin?.generated_password || '—' }}</span></div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="createdOpen = false">Close</Button>
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
import { useTenantStore } from '@/stores/tenantStore'
import { useAuthStore } from '@/stores/auth'

const tenantStore = useTenantStore()
const authStore = useAuthStore()

const isSuperAdmin = computed(() => Number(authStore.profile?.tenant_id) === 0)

const cols = [
  { key: 'name', label: 'School Name', sortable: true },
  { key: 'email', label: 'Contact Email', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'phone', label: 'Phone' },
  { key: 'created_at', label: 'Created', sortable: true }
]

const rows = computed(() => tenantStore.tenants)

function statusTone(s) {
  if (s === 'Active') return 'success'
  if (s === 'Suspended') return 'danger'
  if (s === 'Trial') return 'warning'
  return 'neutral'
}

const target = ref(null)
const viewOpen = ref(false)
const createOpen = ref(false)
const createdOpen = ref(false)
const createdAdmin = ref(null)
const editOpen = ref(false)
const editTarget = ref(null)

const statusOptions = [
  { value: 'Trial', label: 'Trial' },
  { value: 'Active', label: 'Active' },
  { value: 'Suspended', label: 'Suspended' }
]

const form = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
  status: 'Trial'
})

const editForm = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
  status: 'Trial'
})

async function reload() {
  await tenantStore.fetchTenants()
}

function openView(row) {
  target.value = row
  viewOpen.value = true
}

function openCreate() {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.address = ''
  form.status = 'Trial'
  createOpen.value = true
}

async function setStatus(row, status) {
  await tenantStore.updateTenant(row.id, { status })
}

async function createTenant() {
  const payload = {
    name: form.name,
    email: form.email,
    phone: form.phone || null,
    address: form.address || null,
    status: form.status
  }
  const data = await tenantStore.createTenant(payload)
  createdAdmin.value = data?.admin_user || null
  createdOpen.value = true
  createOpen.value = false
}

function openEdit(row) {
  editTarget.value = row
  editForm.name = row?.name || ''
  editForm.email = row?.email || ''
  editForm.phone = row?.phone || ''
  editForm.address = row?.address || ''
  editForm.status = row?.status || 'Trial'
  editOpen.value = true
}

async function saveEdit() {
  if (!editTarget.value) return
  const payload = {
    name: editForm.name,
    email: editForm.email,
    phone: editForm.phone || null,
    address: editForm.address || null,
    status: editForm.status
  }
  await tenantStore.updateTenant(editTarget.value.id, payload)
  editOpen.value = false
  await reload()
}

async function remove(row) {
  if (!window.confirm(`Delete tenant "${row?.name || 'this tenant'}"? This cannot be undone.`)) return
  await tenantStore.deleteTenant(row.id)
  await reload()
}

onMounted(reload)
</script>

