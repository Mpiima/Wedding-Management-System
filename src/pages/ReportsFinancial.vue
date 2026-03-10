<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Financial Reports</h1>
      <p class="text-sm text-gray-500">Total received, expenditures, and balance</p>
    </div>

    <p v-if="reportsStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ reportsStore.errorMessage }}</p>

    <div v-if="report" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard label="Total pledged" :value="formatUgx(report.total_pledged)" />
      <StatCard label="From pledge payments" :value="formatUgx(report.total_pledge_payments)" />
      <StatCard label="From contributions" :value="formatUgx(report.total_contributions)" />
      <StatCard label="Total received" :value="formatUgx(report.total_received)" />
      <StatCard label="Total expenditures" :value="formatUgx(report.total_expenditures)" />
      <StatCard label="Balance" :value="formatUgx(report.balance)" :value-class="report.balance >= 0 ? 'text-emerald-600' : 'text-rose-600'" />
    </div>

    <div v-else class="card-luxury px-6 py-14 text-center">
      <p class="font-display text-sm font-medium text-gray-500">Load report</p>
      <button type="button" class="mt-4 rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600" @click="reportsStore.fetchReport()">Refresh</button>
    </div>

    <TableComponent v-if="report" title="Summary" :columns="summaryColumns" :data="summaryRows" row-key="key" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import StatCard from '@/components/StatCard.vue'
import TableComponent from '@/components/TableComponent.vue'
import { useReportsStore } from '@/stores/reports'

const reportsStore = useReportsStore()

const report = computed(() => reportsStore.report)

const summaryColumns = [
  { key: 'label', label: 'Item' },
  { key: 'value', label: 'Amount (UGX)' }
]

const summaryRows = computed(() => {
  if (!report.value) return []
  return [
    { key: 'pledged', label: 'Total pledged', value: formatUgx(report.value.total_pledged) },
    { key: 'pledge_payments', label: 'Received from pledges', value: formatUgx(report.value.total_pledge_payments) },
    { key: 'contributions', label: 'Direct contributions', value: formatUgx(report.value.total_contributions) },
    { key: 'received', label: 'Total received', value: formatUgx(report.value.total_received) },
    { key: 'expenditures', label: 'Total expenditures', value: formatUgx(report.value.total_expenditures) },
    { key: 'balance', label: 'Balance', value: formatUgx(report.value.balance) }
  ]
})

function formatUgx(v) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(Number(v) || 0)
}

onMounted(() => {
  reportsStore.fetchReport().catch(() => {})
})
</script>
