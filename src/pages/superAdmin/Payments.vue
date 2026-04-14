<template>
  <div class="page-shell">
    <PageHeader title="Payments" description="Track subscription payments across all schools (mock).">
      <template #actions>
        <Button variant="secondary" @click="refresh">Refresh</Button>
      </template>
    </PageHeader>

    <DataTable
      title="All payments"
      :columns="cols"
      :data="rows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="10"
      :filters="filters"
    >
      <template #cell-amount="{ value }">
        {{ formatCurrency(value) }}
      </template>

      <template #cell-status="{ value }">
        <Badge :tone="statusTone(value)">{{ value }}</Badge>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import { formatCurrency } from '@/utils/formatters'
import { useSuperAdminStore } from '@/stores/superAdmin'

const store = useSuperAdminStore()

const cols = [
  { key: 'date', label: 'Date', sortable: true },
  { key: 'schoolName', label: 'School', sortable: true },
  { key: 'plan', label: 'Plan', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'reference', label: 'Reference' }
]

function statusTone(s) {
  if (s === 'Paid') return 'success'
  if (s === 'Failed') return 'danger'
  if (s === 'Pending') return 'warning'
  return 'neutral'
}

function monthKey(isoDate) {
  const d = new Date(String(isoDate))
  if (Number.isNaN(d.getTime())) return 'Unknown'
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

function monthLabel(key) {
  const [y, m] = key.split('-')
  const d = new Date(Number(y), Number(m) - 1, 1)
  return d.toLocaleString(undefined, { month: 'short' })
}

const rows = computed(() => {
  return store.payments.map((p) => {
    const m = monthKey(p.date)
    return {
      ...p,
      month: m,
      schoolName: store.schoolName(p.schoolId),
      plan: store.planName(p.planId)
    }
  })
})

const months = computed(() => {
  const set = new Set(rows.value.map((r) => r.month))
  return [...set].sort()
})

const filters = computed(() => [
  {
    key: 'month',
    label: 'Date',
    options: [{ value: '', label: 'All months' }, ...months.value.map((m) => ({ value: m, label: monthLabel(m) })) ]
  },
  {
    key: 'schoolId',
    label: 'School',
    options: [{ value: '', label: 'All schools' }, ...store.schools.map((s) => ({ value: s.id, label: s.name })) ]
  },
  {
    key: 'planId',
    label: 'Plan',
    options: [{ value: '', label: 'All plans' }, ...store.plans.map((p) => ({ value: p.id, label: p.name })) ]
  }
])

function refresh() {
  return store.fetchAll()
}

onMounted(refresh)
</script>

