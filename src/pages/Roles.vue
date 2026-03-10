<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Roles Management</h1>
        <p class="text-sm text-gray-500">Add roles and assign them to members in Committee</p>
      </div>
      <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600" @click="openModal()">+ Add role</button>
    </div>

    <p v-if="store.errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">{{ store.errorMessage }}</p>

    <TableComponent title="Roles" :columns="columns" :data="store.roles" row-key="id" empty="No roles yet">
      <template #cell-description="{ value }">{{ value || '—' }}</template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium mr-2" @click="openModal(row)">Edit</button>
        <button type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium" @click="confirmDelete(row)">Delete</button>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" :title="editingId ? 'Edit role' : 'Add role'" @update:model-value="editingId = null">
      <form class="space-y-4" @submit.prevent="save">
        <FormInput v-model="form.name" label="Role name" placeholder="e.g. Best Man, Maid of Honor" required :error="errors.name" />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
          <textarea v-model="form.description" placeholder="Optional" rows="2" class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20" />
        </div>
        <p v-if="store.errorMessage" class="text-sm text-rose-600">{{ store.errorMessage }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-50" @click="showModal = false">Cancel</button>
          <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
        </div>
      </form>
    </ModalComponent>

    <ModalComponent v-model="showDeleteModal" title="Delete role" @update:model-value="deleteTarget = null">
      <p class="text-sm text-gray-600">Delete <strong>{{ deleteTarget?.name }}</strong>? Assignments in Committee will be removed.</p>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-50" @click="showDeleteModal = false">Cancel</button>
        <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="deleting" @click="doDelete">Delete</button>
      </div>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import { useRolesStore } from '@/stores/roles'

const store = useRolesStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingId = ref(null)
const deleteTarget = ref(null)
const saving = ref(false)
const deleting = ref(false)

const form = reactive({ name: '', description: '' })
const errors = reactive({ name: '' })

const columns = [
  { key: 'name', label: 'Role name' },
  { key: 'description', label: 'Description' }
]

function openModal(row = null) {
  store.clearError()
  errors.name = ''
  if (row) {
    editingId.value = row.id
    form.name = row.name || ''
    form.description = row.description || ''
  } else {
    editingId.value = null
    form.name = ''
    form.description = ''
  }
  showModal.value = true
}

async function save() {
  errors.name = ''
  if (!form.name.trim()) { errors.name = 'Name is required'; return }
  saving.value = true
  try {
    if (editingId.value) {
      await store.updateRole(editingId.value, { name: form.name.trim(), description: form.description.trim() || null })
    } else {
      await store.createRole({ name: form.name.trim(), description: form.description.trim() || null })
    }
    showModal.value = false
  } finally {
    saving.value = false
  }
}

function confirmDelete(row) {
  deleteTarget.value = row
  showDeleteModal.value = true
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await store.deleteRole(deleteTarget.value.id)
    showDeleteModal.value = false
    deleteTarget.value = null
  } finally {
    deleting.value = false
  }
}

onMounted(() => store.fetchRoles().catch(() => {}))
</script>
