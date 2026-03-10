<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Wedding Program</h1>
        <p class="text-sm text-gray-500">Event timeline and ceremony order</p>
      </div>
      <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white" @click="openForm()">+ Add item</button>
    </div>
    <p v-if="programStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ programStore.errorMessage }}</p>
    <div v-if="!programStore.program.length" class="card-luxury px-6 py-14 text-center">
      <p class="font-display text-sm font-medium text-gray-500">No program items yet</p>
      <button type="button" class="mt-4 rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white" @click="openForm()">+ Add item</button>
    </div>
    <TableComponent v-else title="Program" :columns="columns" :data="programStore.program" row-key="id">
      <template #cell-description="{ value }">{{ value || '—' }}</template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openForm(row)">Edit</button>
        <button type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium ml-2" @click="confirmDelete(row)">Delete</button>
      </template>
    </TableComponent>
    <ModalComponent v-model="showModal" :title="editingId ? 'Edit item' : 'Add item'">
      <form class="space-y-4" @submit.prevent="submit">
        <FormInput v-model="form.title" label="Title *" required />
        <FormInput v-model="form.description" label="Description" />
        <FormInput v-model="form.sort_order" label="Order" type="number" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm" @click="showModal = false">Cancel</button>
          <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm text-white" @click="submit">Save</button>
        </div>
      </template>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import { useWeddingProgramStore } from '@/stores/weddingProgram'

const programStore = useWeddingProgramStore()
const showModal = ref(false)
const editingId = ref(null)
const form = ref({ title: '', description: '', sort_order: 0 })
const columns = [ { key: 'sort_order', label: 'Order' }, { key: 'title', label: 'Title' }, { key: 'description', label: 'Description' } ]

function openForm(row = null) {
  editingId.value = row ? row.id : null
  form.value = { title: row ? row.title : '', description: row ? row.description || '' : '', sort_order: row != null ? (row.sort_order ?? 0) : programStore.program.length }
  showModal.value = true
}

async function submit() {
  try {
    const payload = { title: form.value.title, description: form.value.description || undefined, sort_order: Number(form.value.sort_order) || 0 }
    if (editingId.value) await programStore.updateItem(editingId.value, payload)
    else await programStore.createItem(payload)
    showModal.value = false
  } catch (_) {}
}

function confirmDelete(row) {
  if (!confirm('Remove this item?')) return
  programStore.deleteItem(row.id).catch(() => {})
}

onMounted(() => programStore.fetchProgram().catch(() => {}))
</script>
