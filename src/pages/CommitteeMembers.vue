<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Committee Members</h1>
        <p class="text-sm text-gray-500">Member list, roles, and contact details</p>
      </div>
      <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600">+ Add member</button>
    </div>
    <TableComponent title="Members" :columns="columns" :data="members" row-key="id">
      <template #cell-role="{ value }">
        <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">{{ value }}</span>
      </template>
      <template #actions>
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium">Edit</button>
      </template>
    </TableComponent>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import TableComponent from '@/components/TableComponent.vue'

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'role', label: 'Role' },
  { key: 'phone', label: 'Phone' },
  { key: 'email', label: 'Email' }
]

const members = ref([
  { id: 1, name: 'Chris Lee', role: 'Best Man', phone: '+256 700 123 456', email: 'chris@example.com' },
  { id: 2, name: 'Maya Chen', role: 'Maid of Honor', phone: '+256 701 234 567', email: 'maya@example.com' }
])
</script>
