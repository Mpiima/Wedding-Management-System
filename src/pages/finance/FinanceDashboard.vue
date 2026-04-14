<template>
  <div class="page-shell max-w-[1400px]">
    <PageHeader
      title="Finance overview"
      description="Expected fees, collections, and outstanding balances — filter by year and period."
    />

    <div class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft">
      <div>
        <label class="mb-1 block text-2xs font-semibold uppercase text-slate-500">Academic year</label>
        <select
          v-model="filterYear"
          class="h-10 min-w-[180px] rounded-xl border border-sc-line bg-white px-3 text-sm"
          @change="loadDash"
        >
          <option value="">All years</option>
          <option v-for="y in years" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-2xs font-semibold uppercase text-slate-500">Study period</label>
        <select
          v-model="filterPeriod"
          class="h-10 min-w-[180px] rounded-xl border border-sc-line bg-white px-3 text-sm"
          @change="loadDash"
        >
          <option value="">All periods</option>
          <option v-for="pr in periodsFiltered" :key="pr.id" :value="String(pr.id)">{{ pr.name }}</option>
        </select>
      </div>
      <Button type="button" variant="secondary" :disabled="loading" @click="loadAll">Refresh</Button>
    </div>

    <div v-if="loading" class="py-12 text-center text-sm text-slate-500">Loading…</div>

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-sc-line bg-white p-5 shadow-soft">
          <p class="text-2xs font-semibold uppercase text-slate-500">Expected</p>
          <p class="mt-1 text-2xl font-bold text-slate-900">{{ formatCurrency(totals.expected) }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-5 shadow-soft">
          <p class="text-2xs font-semibold uppercase text-emerald-800">Collected</p>
          <p class="mt-1 text-2xl font-bold text-emerald-900">{{ formatCurrency(totals.collected) }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200/80 bg-amber-50/50 p-5 shadow-soft">
          <p class="text-2xs font-semibold uppercase text-amber-900">Outstanding</p>
          <p class="mt-1 text-2xl font-bold text-amber-950">{{ formatCurrency(totals.outstanding) }}</p>
        </div>
      </div>

      <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-sc-line bg-white shadow-soft">
          <h3 class="border-b border-sc-line px-5 py-3 text-sm font-semibold text-slate-900">By class</h3>
          <div class="max-h-[320px] overflow-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50/90 text-2xs uppercase text-slate-500">
                <tr>
                  <th class="px-4 py-2 text-left">Class</th>
                  <th class="px-2 py-2 text-right">Expected</th>
                  <th class="px-2 py-2 text-right">Collected</th>
                  <th class="px-4 py-2 text-right">Outstanding</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in byClass" :key="c.id" class="border-t border-sc-line/80">
                  <td class="px-4 py-2 font-medium text-slate-800">{{ c.level_name }} {{ c.name }}</td>
                  <td class="px-2 py-2 text-right tabular-nums">{{ formatCurrency(c.expected) }}</td>
                  <td class="px-2 py-2 text-right tabular-nums text-emerald-800">{{ formatCurrency(c.collected) }}</td>
                  <td class="px-4 py-2 text-right tabular-nums text-amber-800">{{ formatCurrency(c.outstanding) }}</td>
                </tr>
              </tbody>
            </table>
            <p v-if="!byClass.length" class="px-4 py-8 text-center text-xs text-slate-400">No invoice data for this filter.</p>
          </div>
        </div>

        <div class="rounded-2xl border border-sc-line bg-white shadow-soft">
          <h3 class="border-b border-sc-line px-5 py-3 text-sm font-semibold text-slate-900">By payment method</h3>
          <ul class="divide-y divide-sc-line p-4">
            <li v-for="m in byMethod" :key="m.method" class="flex justify-between py-2 text-sm">
              <span class="capitalize text-slate-700">{{ methodLabel(m.method) }}</span>
              <span class="font-semibold tabular-nums text-slate-900">{{ formatCurrency(m.total) }}</span>
            </li>
          </ul>
          <p v-if="!byMethod.length" class="px-4 pb-6 text-center text-xs text-slate-400">No payments in range.</p>
        </div>
      </div>

      <div class="mt-8 rounded-2xl border border-sc-line bg-white shadow-soft">
        <h3 class="border-b border-sc-line px-5 py-3 text-sm font-semibold text-slate-900">Payments today</h3>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[640px] text-sm">
            <thead class="bg-slate-50/90 text-2xs uppercase text-slate-500">
              <tr>
                <th class="px-4 py-2 text-left">Receipt</th>
                <th class="px-2 py-2 text-left">Student</th>
                <th class="px-2 py-2 text-left">Invoice</th>
                <th class="px-2 py-2 text-right">Amount</th>
                <th class="px-4 py-2 text-left">Method</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in todayPayments" :key="t.id" class="border-t border-sc-line/80">
                <td class="px-4 py-2 font-mono text-xs text-slate-700">{{ t.receipt_number }}</td>
                <td class="px-2 py-2">{{ t.student_name }}</td>
                <td class="px-2 py-2 text-slate-600">{{ t.invoice_number }}</td>
                <td class="px-2 py-2 text-right font-medium tabular-nums">{{ formatCurrency(t.amount) }}</td>
                <td class="px-4 py-2 capitalize text-slate-600">{{ methodLabel(t.method) }}</td>
              </tr>
            </tbody>
          </table>
          <p v-if="!todayPayments.length" class="px-4 py-8 text-center text-xs text-slate-400">No payments recorded today.</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import { formatCurrency } from '@/utils/formatters'
import { financeApi } from '@/services/financeApi'
import useNotificationStore from '@/stores/notificationStore'

const { errorToast } = useNotificationStore()

const loading = ref(true)
const years = ref([])
const periods = ref([])
const filterYear = ref('')
const filterPeriod = ref('')
const totals = ref({ expected: 0, collected: 0, outstanding: 0 })
const byClass = ref([])
const byMethod = ref([])
const todayPayments = ref([])

const periodsFiltered = computed(() => {
  if (!filterYear.value) return periods.value
  const yid = Number(filterYear.value)
  return periods.value.filter((p) => Number(p.academic_year_id) === yid)
})

function methodLabel(m) {
  const map = { cash: 'Cash', mobile_money: 'Mobile money', bank: 'Bank', card: 'Card', other: 'Other' }
  return map[m] || m || '—'
}

async function loadContext() {
  const { data } = await financeApi.context()
  years.value = data?.data?.academicYears || []
  periods.value = data?.data?.studyPeriods || []
}

async function loadDash() {
  loading.value = true
  try {
    const params = {}
    if (filterYear.value) params.academicYearId = filterYear.value
    if (filterPeriod.value) params.studyPeriodId = filterPeriod.value
    const { data } = await financeApi.dashboard(params)
    const d = data?.data || {}
    totals.value = {
      expected: Number(d.totals?.expected || 0),
      collected: Number(d.totals?.collected || 0),
      outstanding: Number(d.totals?.outstanding || 0)
    }
    byClass.value = d.byClass || []
    byMethod.value = d.byMethod || []
    todayPayments.value = d.todayPayments || []
  } catch (e) {
    errorToast('Dashboard', e?.response?.data?.error || e?.message)
  } finally {
    loading.value = false
  }
}

async function loadAll() {
  await loadContext()
  await loadDash()
}

onMounted(loadAll)
</script>
