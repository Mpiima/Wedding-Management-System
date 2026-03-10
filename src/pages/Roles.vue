<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Roles Management</h1>
        <p class="text-sm text-gray-500">Add roles and assign permissions</p>
      </div>
      <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600">+ Add role</button>
    </div>
    <TableComponent title="Roles" :columns="columns" :data="roles" row-key="id" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import TableComponent from '@/components/TableComponent.vue'

const columns = [
  { key: 'name', label: 'Role name' },
  { key: 'description', label: 'Description' }
]

const roles = ref([
  { id: 1, name: 'Best Man', description: 'Groom\'s primary attendant' },
  { id: 2, name: 'Maid of Honor', description: 'Bride\'s primary attendant' }
])
</script>
