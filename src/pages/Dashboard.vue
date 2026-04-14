<template>
  <div class="page-shell max-w-[1600px]">
    <PageHeader :title="'Dashboard'" :description="headerDescription">
      <template #actions>
        <Button variant="secondary" :disabled="loading" @click="reload">Refresh</Button>
        <Button variant="secondary" type="button" @click="goFinance">Finance overview</Button>
        <Button type="button" @click="goAdmissions">Admissions</Button>
      </template>
    </PageHeader>

    <div
      v-if="meta && !meta.hasActivePeriod"
      class="mb-6 rounded-2xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-950"
    >
      <span class="font-semibold">Academic context not set.</span>
      Choose an active academic year and study period under Academic setup so enrolment and fee totals apply to the right term.
    </div>

    <div v-if="loadError" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
      {{ loadError }}
      <button type="button" class="ml-2 font-semibold underline" @click="reload">Retry</button>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-500">
      <svg class="h-8 w-8 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <p class="mt-3 text-sm">Loading dashboard…</p>
    </div>

    <template v-else>
      <!-- KPIs -->
      <section aria-label="Key metrics" class="mb-8">
        <h2 class="mb-3 text-2xs font-semibold uppercase tracking-wider text-slate-500">Overview</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
          <StatCard
            v-for="s in summaryCards"
            :key="s.id"
            :label="s.label"
            :value="s.value"
            :subtext="s.subtext"
            :trend="s.trend || ''"
            :animate="typeof s.value === 'number' && s.format !== 'currency'"
            :formatter="formatterFor(s)"
            :icon="iconFor(s.id)"
          />
        </div>
      </section>

      <!-- Charts -->
      <section aria-label="Charts" class="mb-8">
        <h2 class="mb-3 text-2xs font-semibold uppercase tracking-wider text-slate-500">Trends</h2>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <BarChart
            v-if="feesChart.labels.length"
            title="Fee receipts"
            :subtitle="feesChartSubtitle"
            :labels="feesChart.labels"
            :values="feesChart.values"
          />
          <div
            v-else
            class="flex min-h-[280px] flex-col items-center justify-center rounded-2xl border border-dashed border-sc-border bg-slate-50/80 p-8 text-center text-sm text-slate-600"
          >
            No payment data in the last six months.
          </div>
          <LineAreaChart
            v-if="perfChart.labels.length"
            title="Enrolment by level"
            :subtitle="enrollmentChartSubtitle"
            :labels="perfChart.labels"
            :values="perfChart.values"
          />
          <div
            v-else
            class="flex min-h-[280px] flex-col items-center justify-center rounded-2xl border border-dashed border-sc-border bg-slate-50/80 p-8 text-center text-sm text-slate-600"
          >
            No enrolments for the active term, or levels are not set up yet.
          </div>
        </div>
      </section>

      <!-- Activity -->
      <section aria-label="Recent activity">
        <h2 class="mb-3 text-2xs font-semibold uppercase tracking-wider text-slate-500">Recent activity</h2>
        <DataTable
          title="Latest payments & admissions"
          :columns="activityColumns"
          :data="activityRows"
          row-key="id"
          empty-text="No recent payments or admission updates yet."
        />
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  UserGroupIcon,
  BanknotesIcon,
  ExclamationTriangleIcon,
  AcademicCapIcon,
  UserPlusIcon
} from '@heroicons/vue/24/outline'
import PageHeader from '@/components/common/PageHeader.vue'
import StatCard from '@/components/ui/StatCard.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Button from '@/components/ui/Button.vue'
import BarChart from '@/components/charts/BarChart.vue'
import LineAreaChart from '@/components/charts/LineAreaChart.vue'
import { formatCurrency } from '@/utils/formatters'
import { dashboardApi } from '@/services/dashboardApi'
const router = useRouter()

const loading = ref(true)
const loadError = ref('')
const meta = ref(null)
const summaryCards = ref([])
const activityRows = ref([])
const feesChart = ref({ labels: [], values: [] })
const perfChart = ref({ labels: [], values: [] })

const activityColumns = [
  { key: 'time', label: 'When', sortable: true },
  { key: 'action', label: 'Type', sortable: true },
  { key: 'detail', label: 'Detail' }
]

const iconMap = {
  totalStudents: UserGroupIcon,
  enrolled: AcademicCapIcon,
  feesCollected: BanknotesIcon,
  outstandingFees: ExclamationTriangleIcon,
  admissionsPipeline: UserPlusIcon
}

function iconFor(id) {
  return iconMap[id] || UserGroupIcon
}

function formatterFor(card) {
  const fmt = card?.format || 'count'
  if (fmt === 'currency') {
    return (n) => formatCurrency(n)
  }
  if (fmt === 'percent') {
    return (n) => `${Math.round(Number(n))}%`
  }
  return (n) => {
    if (typeof n !== 'number' || Number.isNaN(n)) return String(n)
    return Math.round(n).toLocaleString()
  }
}

const headerDescription = computed(() => {
  const m = meta.value
  if (m?.periodLabel) {
    return `Snapshot for ${m.periodLabel}. Data comes from your school records (students, enrolments, invoices, payments, admissions).`
  }
  return 'Overview of students, enrolment, finance, and admissions — powered by your school data.'
})

const feesChartSubtitle = computed(() => 'Last six months · UGX received (all terms)')
const enrollmentChartSubtitle = computed(() => {
  const m = meta.value
  if (m?.periodLabel) return `Headcount by level · ${m.periodLabel}`
  return 'Headcount by level (active term)'
})

function goFinance() {
  router.push('/finance/dashboard')
}

function goAdmissions() {
  router.push('/admissions/applicants')
}

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    const { data: body } = await dashboardApi.summary()
    const d = body?.data || {}
    meta.value = d.meta || null
    summaryCards.value = Array.isArray(d.summaryCards) ? d.summaryCards : []
    activityRows.value = Array.isArray(d.recentActivity) ? d.recentActivity : []
    const charts = d.charts || {}
    feesChart.value = charts.feesByMonth || { labels: [], values: [] }
    perfChart.value = charts.enrollmentByLevel || { labels: [], values: [] }
  } catch (e) {
    const msg = e?.response?.data?.error || e?.message || 'Could not load dashboard'
    loadError.value = typeof msg === 'string' ? msg : 'Could not load dashboard'
    summaryCards.value = []
    activityRows.value = []
    feesChart.value = { labels: [], values: [] }
    perfChart.value = { labels: [], values: [] }
    meta.value = null
  } finally {
    loading.value = false
  }
}

function reload() {
  load()
}

onMounted(load)
</script>
