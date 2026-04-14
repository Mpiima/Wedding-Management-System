<template>
  <div class="page-shell max-w-[1400px]">
    <PageHeader
      title="Student fees"
      description="Per-student totals, paid amounts, and balance — with quick actions to pay or open the invoice."
    />

    <div class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft">
      <div>
        <label class="mb-1 block text-2xs font-semibold uppercase text-slate-500">Year *</label>
        <select v-model="yearId" class="h-10 min-w-[160px] rounded-xl border border-sc-line px-3 text-sm" @change="reload">
          <option value="">Select</option>
          <option v-for="y in years" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-2xs font-semibold uppercase text-slate-500">Period *</label>
        <select v-model="periodId" class="h-10 min-w-[160px] rounded-xl border border-sc-line px-3 text-sm" @change="reload">
          <option value="">Select</option>
          <option v-for="p in periodsFiltered" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-2xs font-semibold uppercase text-slate-500">Class</label>
        <select v-model="classId" class="h-10 min-w-[160px] rounded-xl border border-sc-line px-3 text-sm" @change="reload">
          <option value="">All classes</option>
          <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.level_name }} {{ c.name }}</option>
        </select>
      </div>
      <Button type="button" variant="secondary" :disabled="loading" @click="reload">Refresh</Button>
    </div>

    <div v-if="loading" class="py-16 text-center text-sm text-slate-500">Loading…</div>
    <div v-else-if="!yearId || !periodId" class="rounded-xl border border-dashed border-sc-line bg-slate-50/80 py-12 text-center text-sm text-slate-500">
      Choose academic year and study period to load balances.
    </div>
    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="row in students"
        :key="row.id"
        class="flex flex-col rounded-2xl border border-sc-line bg-white p-4 shadow-soft transition hover:shadow-card"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="font-semibold text-slate-900">{{ row.first_name }} {{ row.last_name }}</p>
            <p class="text-xs text-slate-500">{{ row.admission_number }}</p>
          </div>
          <Badge :tone="statusTone(row.status)">{{ statusLabel(row.status) }}</Badge>
        </div>
        <dl class="mt-3 space-y-1 text-sm">
          <div class="flex justify-between">
            <dt class="text-slate-500">Total fees</dt>
            <dd class="font-medium tabular-nums">{{ formatCurrency(row.total_amount || 0) }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-slate-500">Paid</dt>
            <dd class="tabular-nums text-emerald-800">{{ formatCurrency(row.amount_paid || 0) }}</dd>
          </div>
          <div class="flex justify-between border-t border-sc-line pt-2">
            <dt class="font-medium text-slate-700">Balance</dt>
            <dd class="font-bold tabular-nums text-amber-900">{{ formatCurrency(row.balance_due || 0) }}</dd>
          </div>
          <div v-if="Number(row.credit_balance) > 0" class="flex justify-between text-xs text-sky-800">
            <dt>Credit wallet</dt>
            <dd>{{ formatCurrency(row.credit_balance) }}</dd>
          </div>
        </dl>
        <div class="mt-4 flex flex-wrap gap-2">
          <Button
            v-if="row.invoice_id"
            type="button"
            variant="secondary"
            class="flex-1 min-w-[100px]"
            @click="$router.push({ name: 'Invoices', query: { open: String(row.invoice_id) } })"
          >
            View invoice
          </Button>
          <Button
            v-if="row.invoice_id && row.status !== 'paid'"
            type="button"
            class="flex-1 min-w-[100px]"
            @click="openPay(row)"
          >
            Pay
          </Button>
          <span v-if="!row.invoice_id" class="text-xs text-slate-400">No invoice for this period</span>
        </div>
      </div>
    </div>

    <Modal v-model="payOpen" title="Record payment">
      <p v-if="payRow" class="mb-2 text-sm text-slate-600">
        {{ payRow.first_name }} {{ payRow.last_name }} — balance {{ formatCurrency(payRow.balance_due) }}
      </p>
      <FormInput v-model.number="payForm.amount" label="Amount" type="number" step="0.01" />
      <SelectInput v-model="payForm.method" label="Method" :options="methodOptions" />
      <FormInput v-model="payForm.reference" label="Reference / transaction ID" />
      <FormInput v-model="payForm.paidAt" label="Date" type="date" />
      <template #footer>
        <Button variant="secondary" type="button" @click="payOpen = false">Cancel</Button>
        <Button type="button" :disabled="paySaving" @click="submitPay">{{ paySaving ? 'Saving…' : 'Record' }}</Button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { formatCurrency } from '@/utils/formatters'
import { financeApi } from '@/services/financeApi'
import useNotificationStore from '@/stores/notificationStore'

const route = useRoute()
const { successToast, errorToast } = useNotificationStore()

const loading = ref(false)
const years = ref([])
const periods = ref([])
const classes = ref([])
const yearId = ref('')
const periodId = ref('')
const classId = ref('')
const students = ref([])

const payOpen = ref(false)
const payRow = ref(null)
const paySaving = ref(false)
const payForm = ref({
  amount: '',
  method: 'cash',
  reference: '',
  paidAt: new Date().toISOString().slice(0, 10)
})

const methodOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'mobile_money', label: 'Mobile money' },
  { value: 'bank', label: 'Bank' },
  { value: 'card', label: 'Card' },
  { value: 'other', label: 'Other' }
]

const periodsFiltered = computed(() => {
  if (!yearId.value) return periods.value
  const y = Number(yearId.value)
  return periods.value.filter((p) => Number(p.academic_year_id) === y)
})

function statusTone(s) {
  if (s === 'paid') return 'success'
  if (s === 'partial') return 'warning'
  if (s === 'unpaid') return 'danger'
  return 'neutral'
}

function statusLabel(s) {
  if (!s) return '—'
  return s.charAt(0).toUpperCase() + s.slice(1)
}

async function loadContext() {
  const { data } = await financeApi.context()
  years.value = data?.data?.academicYears || []
  periods.value = data?.data?.studyPeriods || []
  classes.value = data?.data?.classes || []
}

async function reload() {
  if (!yearId.value || !periodId.value) {
    students.value = []
    return
  }
  loading.value = true
  try {
    const params = {
      academicYearId: yearId.value,
      studyPeriodId: periodId.value
    }
    if (classId.value) params.classId = classId.value
    const { data } = await financeApi.studentsFees(params)
    students.value = data?.data || []
  } catch (e) {
    errorToast('Load failed', e?.response?.data?.error || e?.message)
  } finally {
    loading.value = false
  }
}

function openPay(row) {
  payRow.value = row
  payForm.value = {
    amount: row.balance_due != null && row.balance_due !== '' ? String(row.balance_due) : '',
    method: 'cash',
    reference: '',
    paidAt: new Date().toISOString().slice(0, 10)
  }
  payOpen.value = true
}

async function submitPay() {
  if (!payRow.value?.invoice_id) return
  paySaving.value = true
  try {
    await financeApi.payments.record({
      invoiceId: payRow.value.invoice_id,
      amount: Number(payForm.value.amount),
      method: payForm.value.method,
      reference: payForm.value.reference || undefined,
      paidAt: payForm.value.paidAt
    })
    successToast('Payment', 'Recorded.')
    payOpen.value = false
    await reload()
  } catch (e) {
    errorToast('Payment', e?.response?.data?.error || e?.message)
  } finally {
    paySaving.value = false
  }
}

watch([yearId, periodId], () => {
  reload()
})

onMounted(async () => {
  await loadContext()
  if (route.query.year) yearId.value = String(route.query.year)
  if (route.query.period) periodId.value = String(route.query.period)
  await reload()
})
</script>
