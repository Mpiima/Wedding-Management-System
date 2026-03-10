<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Contributions Report</h1>
      <p class="text-sm text-gray-500">Contribution summary: pledge payments and direct contributions</p>
    </div>
    <div v-if="report" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard label="Total pledged" :value="formatUgx(report.total_pledged)" />
      <StatCard label="From pledge payments" :value="formatUgx(report.total_pledge_payments)" />
      <StatCard label="Direct contributions" :value="formatUgx(report.total_contributions)" />
      <StatCard label="Total received" :value="formatUgx(report.total_received)" />
    </div>
    <p v-if="reportsStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ reportsStore.errorMessage }}</p>
    <TableComponent title="Contributions (direct)" :columns="contribColumns" :data="contributionsStore.contributions" row-key="id" :pagination="true" :page-size="15" v-model:current-page="contribPage" empty="No direct contributions">
      <template #cell-member_name="{ value }">{{ value || '—' }}</template>
      <template #cell-amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-contribution_date="{ value }">{{ value ? formatDate(value) : '—' }}</template>
    </TableComponent>
    <TableComponent title="Pledges" :columns="pledgeColumns" :data="pledgesStore.pledges" row-key="id" :pagination="true" :page-size="10" v-model:current-page="pledgePage" empty="No pledges">
      <template #cell-member_name="{ value }">{{ value || '—' }}</template>
      <template #cell-amount_pledged="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-amount_paid="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-paying_date="{ value }">{{ value ? formatDate(value) : '—' }}</template>
    </TableComponent>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import StatCard from '@/components/StatCard.vue'
import TableComponent from '@/components/TableComponent.vue'
import { useReportsStore } from '@/stores/reports'
import { useContributionsStore } from '@/stores/contributions'
import { usePledgesStore } from '@/stores/pledges'

const reportsStore = useReportsStore()
const contributionsStore = useContributionsStore()
const pledgesStore = usePledgesStore()

const report = computed(() => reportsStore.report)
const contribPage = ref(1)
const pledgePage = ref(1)
const contribColumns = [ { key: 'member_name', label: 'Member' }, { key: 'amount', label: 'Amount' }, { key: 'contribution_date', label: 'Date' } ]
const pledgeColumns = [ { key: 'member_name', label: 'Member' }, { key: 'amount_pledged', label: 'Pledged' }, { key: 'amount_paid', label: 'Paid' }, { key: 'paying_date', label: 'Paying date' } ]

function formatUgx(v) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(Number(v) || 0)
}
function formatDate(d) {
  return new Date(d + 'Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(() => {
  reportsStore.fetchReport().catch(() => {})
  contributionsStore.fetchContributions().catch(() => {})
  pledgesStore.fetchPledges().catch(() => {})
})
</script>
