<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Budget Management</h1>
        <p class="text-sm text-gray-500">Planned vs actual spending by category</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
      >
        + Add category
      </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <ProgressCard label="Venue" :value="venueProgress" />
      <ProgressCard label="Catering" :value="85" />
      <ProgressCard label="Photography" :value="60" />
      <ProgressCard label="Decor" :value="40" />
    </div>

    <TableComponent
      title="Budget categories"
      :columns="columns"
      :data="categories"
      row-key="id"
      :pagination="true"
      :page-size="5"
      v-model:current-page="currentPage"
    >
      <template #cell-planned="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-actual="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-status="{ value }">
        <span
          class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
          :class="value === 'On track' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
        >
          {{ value }}
        </span>
      </template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium">Edit</button>
      </template>
    </TableComponent>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ProgressCard from '@/components/ProgressCard.vue'
import TableComponent from '@/components/TableComponent.vue'

const currentPage = ref(1)
const venueProgress = ref(78)

const columns = [
  { key: 'category', label: 'Category' },
  { key: 'item', label: 'Item' },
  { key: 'planned', label: 'Planned' },
  { key: 'actual', label: 'Actual' },
  { key: 'status', label: 'Status' }
]

const categories = ref([
  { id: 1, category: 'Venue', item: 'Hall & Garden', planned: 35000000, actual: 27300000, status: 'On track' },
  { id: 2, category: 'Catering', item: 'Dinner & Drinks', planned: 28000000, actual: 23800000, status: 'On track' },
  { id: 3, category: 'Photography', item: 'Photo + Video', planned: 12000000, actual: 7200000, status: 'On track' },
  { id: 4, category: 'Decor', item: 'Florals & Setup', planned: 8000000, actual: 3200000, status: 'On track' }
])

function formatUgx(val) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(val || 0)
}
</script>
