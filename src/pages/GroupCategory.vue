<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-wmis-text">Group Category</h1>
        <p class="text-sm text-gray-500">Organise guests and members into groups (e.g. Family, Friends, Colleagues)</p>
      </div>
      <button type="button" class="btn-primary" @click="showModal = true">+ Add category</button>
    </div>
    <TableComponent
      title="Categories"
      :columns="columns"
      :data="categories"
      row-key="id"
    >
      <template #cell-count="{ value }">
        <span class="font-medium text-wmis-text">{{ value }} guests</span>
      </template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium mr-2">Edit</button>
        <button type="button" class="text-gray-500 hover:text-gray-600 text-xs font-medium">Remove</button>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" title="Add group category">
      <form class="space-y-4">
        <FormInput v-model="form.name" label="Category name" placeholder="e.g. Family, Friends" />
        <FormInput v-model="form.description" label="Description" placeholder="Optional" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-rose-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-rose-50" @click="showModal = false">Cancel</button>
          <button type="button" class="btn-primary" @click="showModal = false">Save</button>
        </div>
      </template>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'

const showModal = ref(false)
const form = ref({ name: '', description: '' })

const columns = [
  { key: 'name', label: 'Category' },
  { key: 'description', label: 'Description' },
  { key: 'count', label: 'Count' }
]

const categories = ref([
  { id: 1, name: 'Family', description: 'Relatives and close family', count: 45 },
  { id: 2, name: 'Friends', description: 'Friends and peers', count: 62 },
  { id: 3, name: 'Colleagues', description: 'Work and professional', count: 18 }
])
</script>
