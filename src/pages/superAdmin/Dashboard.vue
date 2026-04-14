<template>
  <div class="page-shell">
    <PageHeader
      title="Super Admin Dashboard"
      description="Manage all schools, subscriptions, and SaaS revenue (mock)."
    >
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

    <template v-else>
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          v-for="s in stats"
          :key="s.id"
          :label="s.label"
          :value="s.value"
          :subtext="s.subtext"
          :trend="s.trend || ''"
          :animate="typeof s.value === 'number'"
          :formatter="statFormatter"
          :icon="s.icon"
        />
      </div>

      <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <LineAreaChart title="Revenue over time" subtitle="Monthly recurring revenue (mock)" :labels="revenue.labels" :values="revenue.values" />
        <BarChart title="New schools growth" subtitle="Onboarding momentum (mock)" :labels="growth.labels" :values="growth.values" />
      </div>

      <div class="mt-6 rounded-2xl border border-sc-border bg-white p-6 shadow-card">
        <h3 class="text-sm font-semibold text-slate-900">Operational snapshot</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div class="rounded-2xl bg-slate-50/70 p-4 ring-1 ring-slate-100">
            <p class="text-2xs font-semibold uppercase tracking-wider text-slate-400">Suspended schools</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ suspendedCount }}</p>
          </div>
          <div class="rounded-2xl bg-slate-50/70 p-4 ring-1 ring-slate-100">
            <p class="text-2xs font-semibold uppercase tracking-wider text-slate-400">Trial schools</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ trialCount }}</p>
          </div>
          <div class="rounded-2xl bg-slate-50/70 p-4 ring-1 ring-slate-100">
            <p class="text-2xs font-semibold uppercase tracking-wider text-slate-400">Active users (mock)</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ activeUsersMock }}</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import StatCard from '@/components/ui/StatCard.vue'
import LineAreaChart from '@/components/charts/LineAreaChart.vue'
import BarChart from '@/components/charts/BarChart.vue'
import { formatCurrency } from '@/utils/formatters'
import { useSuperAdminStore } from '@/stores/superAdmin'

import { BanknotesIcon, AcademicCapIcon, UserGroupIcon, ChartBarIcon } from '@heroicons/vue/24/outline'

const store = useSuperAdminStore()

const loading = computed(() => store.loading)

function statFormatter(n) {
  if (typeof n !== 'number') return String(n)
  // Treat large numbers as currency, smaller counts as plain integers.
  if (n >= 1000) return formatCurrency(n)
  return String(Math.round(n))
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

const revenue = computed(() => {
  const map = new Map()
  for (const p of store.payments) {
    const k = monthKey(p.date)
    map.set(k, (map.get(k) || 0) + Number(p.amount || 0))
  }

  const keys = [...map.keys()].sort()
  const lastKeys = keys.slice(Math.max(0, keys.length - 6))
  const labels = lastKeys.map(monthLabel)
  const values = lastKeys.map((k) => map.get(k) || 0)

  return { labels, values }
})

const growth = computed(() => {
  const map = new Map()
  for (const s of store.schools) {
    const k = monthKey(s.createdAt)
    map.set(k, (map.get(k) || 0) + 1)
  }
  const keys = [...map.keys()].sort()
  const lastKeys = keys.slice(Math.max(0, keys.length - 6))
  const labels = lastKeys.map(monthLabel)
  const values = lastKeys.map((k) => map.get(k) || 0)
  return { labels, values }
})

const totalSchools = computed(() => store.schools.length)
const activeSubscriptions = computed(() => store.schools.filter((s) => s.status === 'Active').length)
const suspendedCount = computed(() => store.schools.filter((s) => s.status === 'Suspended').length)
const trialCount = computed(() => store.schools.filter((s) => s.status === 'Trial').length)

const totalStudents = computed(() => store.schools.reduce((sum, s) => sum + Number(s.studentsCount || 0), 0))

const monthlyRevenue = computed(() => {
  const latest = [...store.payments]
    .map((p) => new Date(String(p.date)))
    .filter((d) => !Number.isNaN(d.getTime()))
    .sort((a, b) => b.getTime() - a.getTime())[0]

  if (!latest) return 0
  const key = monthKey(latest.toISOString())
  return store.payments
    .filter((p) => monthKey(p.date) === key)
    .reduce((sum, p) => sum + Number(p.amount || 0), 0)
})

const activeUsersMock = computed(() => {
  // Mock: assume ~0.9 users per student.
  const n = totalStudents.value
  return Math.round(n * 0.9)
})

const stats = computed(() => [
  {
    id: 'schools',
    label: 'Total Schools',
    value: totalSchools.value,
    subtext: 'All tenants in SaaS',
    icon: AcademicCapIcon
  },
  {
    id: 'subs',
    label: 'Active Subscriptions',
    value: activeSubscriptions.value,
    subtext: 'Currently Active plan',
    icon: ChartBarIcon
  },
  {
    id: 'rev',
    label: 'Monthly Revenue',
    value: monthlyRevenue.value,
    subtext: 'Latest billing month',
    icon: BanknotesIcon
  },
  {
    id: 'students',
    label: 'Total Students',
    value: totalStudents.value,
    subtext: 'Across all schools',
    icon: UserGroupIcon
  }
])

function refresh() {
  return store.fetchAll()
}

onMounted(refresh)
</script>

