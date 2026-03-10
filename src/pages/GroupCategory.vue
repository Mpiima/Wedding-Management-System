<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-wmis-text">Group Category</h1>
        <p class="text-sm text-gray-500">Organise guests and members into groups (e.g. Family, Friends, Colleagues)</p>
      </div>
      <button type="button" class="btn-primary" @click="openModal()">+ Add category</button>
    </div>

    <p v-if="store.errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">{{ store.errorMessage }}</p>

    <TableComponent title="Categories" :columns="columns" :data="store.categories" row-key="id" empty="No categories yet">
      <template #cell-description="{ value }">{{ value || '—' }}</template>
      <template #cell-count="{ row }">{{ memberCount(row.id) }} members</template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium mr-2" @click="openModal(row)">Edit</button>
        <button type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium" @click="confirmDelete(row)">Remove</button>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" :title="editingId ? 'Edit group category' : 'Add group category'" @update:model-value="editingId = null">
      <form class="space-y-4" @submit.prevent="save">
        <FormInput v-model="form.name" label="Category name" placeholder="e.g. Family, Friends" required :error="errors.name" />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
          <textarea v-model="form.description" placeholder="Optional" rows="2" class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20" />
        </div>
        <p v-if="store.errorMessage" class="text-sm text-rose-600">{{ store.errorMessage }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showModal = false">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
        </div>
      </form>
    </ModalComponent>

    <ModalComponent v-model="showDeleteModal" title="Remove category" @update:model-value="deleteTarget = null">
      <p class="text-sm text-gray-600">Remove <strong>{{ deleteTarget?.name }}</strong>? Members in this category must be moved first.</p>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-50" @click="showDeleteModal = false">Cancel</button>
        <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="deleting" @click="doDelete">Remove</button>
      </div>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import { useGroupCategoriesStore } from '@/stores/groupCategories'
import { useMembersStore } from '@/stores/members'

const store = useGroupCategoriesStore()
const membersStore = useMembersStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const editingId = ref(null)
const deleteTarget = ref(null)
const saving = ref(false)
const deleting = ref(false)

const form = reactive({ name: '', description: '' })
const errors = reactive({ name: '' })

const columns = [
  { key: 'name', label: 'Category' },
  { key: 'description', label: 'Description' },
  { key: 'count', label: 'Members' }
]

function memberCount(categoryId) {
  return membersStore.members.filter((m) => Number(m.group_category_id) === Number(categoryId)).length
}

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
      await store.updateCategory(editingId.value, { name: form.name.trim(), description: form.description.trim() || null })
    } else {
      await store.createCategory({ name: form.name.trim(), description: form.description.trim() || null })
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
    await store.deleteCategory(deleteTarget.value.id)
    showDeleteModal.value = false
    deleteTarget.value = null
  } finally {
    deleting.value = false
  }
}

onMounted(async () => {
  await store.fetchCategories().catch(() => {})
  await membersStore.fetchMembers().catch(() => {})
})
</script>
