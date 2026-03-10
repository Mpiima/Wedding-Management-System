<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Pledges</h1>
      <p class="text-sm text-gray-500">Pledge tracking and fulfillment status</p>
    </div>
    <TableComponent
      title="Pledges"
      :columns="columns"
      :data="pledges"
      row-key="id"
    >
      <template #cell-amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-status="{ value }">
        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(value)">{{ value }}</span>
      </template>
    </TableComponent>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import TableComponent from '@/components/TableComponent.vue'

const columns = [
  { key: 'name', label: 'Pledger' },
  { key: 'amount', label: 'Amount' },
  { key: 'dueDate', label: 'Due date' },
  { key: 'status', label: 'Status' }
]

const pledges = ref([
  { id: 1, name: 'Family Group', amount: 5000000, dueDate: '2026-04-01', status: 'Pending' },
  { id: 2, name: 'Friends Circle', amount: 2500000, dueDate: '2026-03-15', status: 'Fulfilled' }
])

function formatUgx(v) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(v || 0)
}
function statusClass(s) {
  return s === 'Fulfilled' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
}
</script>
