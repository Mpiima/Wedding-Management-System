<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Fees</h1>
      <p class="mt-1 text-sm text-slate-500">Statements and status by child (mock).</p>
    </div>
    <DataTable
      title="Fee items"
      :columns="cols"
      :data="rows"
      row-key="id"
      searchable
      :filters="filters"
      sortable
    >
      <template #cell-amount="{ value }">
        {{ formatCurrency(value) }}
      </template>
      <template #cell-status="{ value }">
        <Badge :tone="feeTone(value)">{{ value }}</Badge>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import { getParentPortalData } from '@/services/mockData'
import { formatCurrency } from '@/utils/formatters'

const rows = ref([])

const cols = [
  { key: 'childName', label: 'Child', sortable: true },
  { key: 'description', label: 'Description', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
]

const filters = computed(() => {
  const names = [...new Set(rows.value.map((r) => r.childName))]
  return [
    {
      key: 'childName',
      label: 'Child',
      options: [{ value: '', label: 'All children' }, ...names.map((n) => ({ value: n, label: n }))]
    },
    {
      key: 'status',
      label: 'Status',
      options: [
        { value: '', label: 'All statuses' },
        { value: 'Paid', label: 'Paid' },
        { value: 'Partial', label: 'Partial' },
        { value: 'Unpaid', label: 'Unpaid' }
      ]
    }
  ]
})

function feeTone(status) {
  if (status === 'Paid') return 'success'
  if (status === 'Partial') return 'warning'
  if (status === 'Unpaid') return 'danger'
  return 'neutral'
}

onMounted(async () => {
  const d = await getParentPortalData()
  rows.value = d.fees || []
})
</script>
