<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Service Providers</h1>
        <p class="text-sm text-gray-500">Vendor list, categories, and contact info</p>
      </div>
      <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600">+ Add vendor</button>
    </div>
    <TableComponent title="Vendors" :columns="columns" :data="vendors" row-key="id">
      <template #cell-status="{ value }">
        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="value === 'Booked' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ value }}</span>
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
  { key: 'name', label: 'Vendor' },
  { key: 'category', label: 'Category' },
  { key: 'contact', label: 'Contact' },
  { key: 'status', label: 'Status' }
]

const vendors = ref([
  { id: 1, name: 'Crystal Garden Venue', category: 'Venue', contact: 'Amelia', status: 'Booked' },
  { id: 2, name: 'Sunrise Photography', category: 'Photography', contact: 'Leo', status: 'Considering' }
])
</script>
