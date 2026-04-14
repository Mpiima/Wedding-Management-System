<template>
  <div class="space-y-8">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Welcome</h1>
      <p class="mt-1 text-sm text-slate-500">Overview of your children and school updates.</p>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div
        v-for="(s, i) in summary"
        :key="i"
        class="rounded-2xl border border-sc-border bg-white p-5 shadow-card"
      >
        <p class="text-2xs font-semibold uppercase tracking-wider text-slate-400">{{ s.label }}</p>
        <p class="mt-2 text-sm font-semibold text-slate-900">{{ s.value }}</p>
      </div>
    </div>

    <!-- Wallet balance card -->
    <div class="rounded-2xl border border-sc-border bg-white p-6 shadow-card">
      <h2 class="text-sm font-semibold text-slate-900">Canteen wallet balance</h2>
      <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatCurrency(walletTotal) }}</p>
      <div class="mt-3 space-y-1 text-sm text-slate-700">
        <div v-if="walletBreakdown.length === 0">No wallet data available.</div>
        <div v-for="w in walletBreakdown" :key="w.studentId">
          <span class="font-medium text-slate-900">{{ w.studentName }}:</span> {{ formatCurrency(w.walletBalance) }}
        </div>
      </div>
    </div>

    <!-- Spending history table -->
    <DataTable
      title="Spending history"
      :columns="spendingCols"
      :data="spendingRows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="6"
      v-model:current-page="spendingPage"
      empty-text="No purchases yet"
    >
      <template #cell-amount="{ value }">
        {{ formatCurrency(value) }}
      </template>
    </DataTable>

    <div class="rounded-2xl border border-sc-border bg-white p-6 shadow-card">
      <h2 class="text-sm font-semibold text-slate-900">School notices</h2>
      <ul class="mt-3 space-y-2 text-sm text-slate-600">
        <li v-for="(n, i) in notices" :key="i">• {{ n }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { getParentPortalData } from '@/services/mockData'
import DataTable from '@/components/ui/DataTable.vue'
import { formatCurrency } from '@/utils/formatters'
import { useCanteenStore } from '@/stores/canteen'

const summary = ref([])
const notices = ref([])

const canteen = useCanteenStore()
const parentChildIds = ref([])

const spendingPage = ref(1)
const spendingCols = [
  { key: 'studentName', label: 'Student', sortable: true },
  { key: 'itemName', label: 'Item', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'date', label: 'Date', sortable: true },
  { key: 'reference', label: 'Reference' }
]

onMounted(async () => {
  const d = await getParentPortalData()
  const dash = d.dashboard || {}
  summary.value = dash.summary || []
  notices.value = dash.notices || []

  parentChildIds.value = (d.children || []).map((c) => String(c.id))
  await canteen.fetchAll()
})

const walletBreakdown = computed(() => {
  const ids = new Set(parentChildIds.value)
  return canteen.wallets.filter((w) => ids.has(w.studentId))
})

const walletTotal = computed(() => {
  return walletBreakdown.value.reduce((sum, w) => sum + Number(w.walletBalance || 0), 0)
})

const spendingRows = computed(() => {
  const ids = new Set(parentChildIds.value)
  return canteen.transactions
    .filter((t) => t.type === 'Purchase' && ids.has(t.studentId))
    .slice()
    .sort((a, b) => String(b.date).localeCompare(String(a.date)))
    .map((t) => ({
      ...t,
      studentName: canteen.getStudentName(t.studentId),
      itemName: t.type === 'Purchase' ? canteen.getItemName(t.itemId) : '—'
    }))
})
</script>
