<template>
  <div class="page-shell">
    <PageHeader title="Canteen Transactions" description="Top-ups and purchases (mock).">
      <template #actions>
        <Button variant="secondary" @click="refresh">Refresh</Button>
      </template>
    </PageHeader>

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-500">
      <svg class="h-8 w-8 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <p class="mt-3 text-sm">Loading…</p>
    </div>

    <DataTable
      v-else
      title="Transactions"
      :columns="cols"
      :data="txRows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="10"
      v-model:current-page="page"
      :filters="typeFilter"
    >
      <template #cell-type="{ value }">
        <Badge :tone="typeTone(value)">{{ value }}</Badge>
      </template>

      <template #cell-amount="{ value }">
        {{ formatCurrency(value) }}
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import { formatCurrency } from '@/utils/formatters'
import { useCanteenStore } from '@/stores/canteen'

const canteen = useCanteenStore()

const page = ref(1)
const loading = computed(() => canteen.loading)

const cols = [
  { key: 'type', label: 'Type', sortable: true },
  { key: 'studentName', label: 'Student', sortable: true },
  { key: 'itemName', label: 'Item' },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'date', label: 'Date', sortable: true },
  { key: 'reference', label: 'Reference' }
]

const typeFilter = [
  {
    key: 'type',
    label: 'Type',
    options: [
      { value: '', label: 'All types' },
      { value: 'Top-up', label: 'Top-up' },
      { value: 'Purchase', label: 'Purchase' }
    ]
  }
]

function typeTone(t) {
  if (t === 'Top-up') return 'info'
  if (t === 'Purchase') return 'warning'
  return 'neutral'
}

const txRows = computed(() => {
  return canteen.transactions
    .slice()
    .sort((a, b) => String(b.date).localeCompare(String(a.date)))
    .map((t) => ({
      ...t,
      studentName: canteen.getStudentName(t.studentId),
      itemName: t.type === 'Purchase' ? canteen.getItemName(t.itemId) : '—'
    }))
})

function refresh() {
  return canteen.fetchAll()
}

onMounted(() => canteen.fetchAll())
</script>

