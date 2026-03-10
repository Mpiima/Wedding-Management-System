<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Financial Reports</h1>
      <p class="text-sm text-gray-500">Budget and expenditure reports</p>
    </div>
    <ChartCard title="Budget vs actual" footer="Current wedding" />
    <TableComponent title="Summary" :columns="columns" :data="data" row-key="id" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ChartCard from '@/components/ChartCard.vue'
import TableComponent from '@/components/TableComponent.vue'

const columns = [
  { key: 'category', label: 'Category' },
  { key: 'planned', label: 'Planned (UGX)' },
  { key: 'actual', label: 'Actual (UGX)' }
]

const data = ref([
  { id: 1, category: 'Venue', planned: '35,000,000', actual: '27,300,000' },
  { id: 2, category: 'Catering', planned: '28,000,000', actual: '23,800,000' }
])
</script>
