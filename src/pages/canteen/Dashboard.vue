<template>
  <div class="page-shell">
    <PageHeader title="Canteen Dashboard" description="Wallet balances and recent canteen transactions (mock)." />

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-500">
      <svg class="h-8 w-8 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <p class="mt-3 text-sm">Loading…</p>
    </div>

    <template v-else>
      <!-- Stats -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          v-for="s in summaryCards"
          :key="s.id"
          :label="s.label"
          :value="s.value"
          :subtext="s.subtext"
          :trend="s.trend || ''"
          :animate="typeof s.value === 'number'"
          :formatter="statFormatter"
          :icon="BanknotesIcon"
        />
      </div>

      <!-- Wallet balance per student -->
      <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          v-for="w in wallets"
          :key="w.studentId"
          :label="w.studentName"
          :value="w.walletBalance"
          subtext="Available balance"
          :animate="typeof w.walletBalance === 'number'"
          :formatter="statFormatter"
          :icon="BanknotesIcon"
        />
      </div>

      <!-- Transactions -->
      <div class="mt-8">
        <DataTable
          title="Recent transactions"
          :columns="txColumns"
          :data="recentTxRows"
          row-key="id"
          searchable
          sortable
          :pagination="true"
          :page-size="6"
          v-model:current-page="txPage"
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { BanknotesIcon } from '@heroicons/vue/24/outline'
import PageHeader from '@/components/common/PageHeader.vue'
import StatCard from '@/components/ui/StatCard.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import { formatCurrency } from '@/utils/formatters'
import { useCanteenStore } from '@/stores/canteen'

const canteen = useCanteenStore()

const loading = computed(() => canteen.loading)
const wallets = computed(() => canteen.wallets)

const txPage = ref(1)

const txColumns = [
  { key: 'type', label: 'Type', sortable: true },
  { key: 'studentName', label: 'Student', sortable: true },
  { key: 'itemName', label: 'Item' },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'date', label: 'Date', sortable: true },
  { key: 'reference', label: 'Reference' }
]

function statFormatter(n) {
  return formatCurrency(n)
}

function typeTone(t) {
  if (t === 'Top-up') return 'info'
  if (t === 'Purchase') return 'success'
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

const recentTxRows = computed(() => txRows.value.slice(0, 12))

const summaryCards = computed(() => {
  const tx = canteen.transactions
  const totalTopUps = tx.filter((t) => t.type === 'Top-up').reduce((sum, t) => sum + Number(t.amount || 0), 0)
  const totalPurchases = tx.filter((t) => t.type === 'Purchase').reduce((sum, t) => sum + Number(t.amount || 0), 0)
  const studentCount = canteen.wallets.length

  return [
    { id: 'topups', label: 'Total top-ups', value: totalTopUps, subtext: 'All time' },
    { id: 'purchases', label: 'Total purchases', value: totalPurchases, subtext: 'All time' },
    { id: 'students', label: 'Students with wallets', value: studentCount, subtext: 'Active accounts' }
  ]
})

onMounted(async () => {
  await canteen.fetchAll()
})
</script>

