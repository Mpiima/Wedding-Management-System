<template>
  <div class="page-shell max-w-[1680px]">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <PageHeader
        title="Enrollments"
        description="Students enrolled by period — fee status, balances, and quick actions."
      />
      <Button type="button" variant="secondary" :disabled="loading" @click="load">Refresh</Button>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
      <div
        v-for="s in statCards"
        :key="s.key"
        class="rounded-2xl border border-sc-line bg-white px-4 py-3 shadow-soft"
      >
        <p class="text-2xs font-semibold uppercase tracking-wide text-slate-500">{{ s.label }}</p>
        <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ stats[s.key] ?? 0 }}</p>
      </div>
    </div>

    <div
      class="mt-8 flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/40 p-5 shadow-[0_1px_3px_rgba(15,23,42,0.06)] sm:p-6"
    >
      <div class="min-w-[10rem] flex-1">
        <label class="mb-1.5 block text-2xs font-semibold uppercase tracking-wide text-slate-500">Academic year</label>
        <select
          v-model="filters.academicYearId"
          class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm font-medium text-slate-800 shadow-sm transition focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
          @change="onFilterChange"
        >
          <option value="">All years</option>
          <option v-for="y in yearOpts" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
        </select>
      </div>
      <div class="min-w-[10rem] flex-1">
        <label class="mb-1.5 block text-2xs font-semibold uppercase tracking-wide text-slate-500">Study period</label>
        <select
          v-model="filters.studyPeriodId"
          class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm font-medium text-slate-800 shadow-sm transition focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
          @change="onFilterChange"
        >
          <option value="">All periods</option>
          <option v-for="p in periodOpts" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
        </select>
      </div>
      <div class="min-w-[10rem] flex-1">
        <label class="mb-1.5 block text-2xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
        <select
          v-model="filters.classId"
          class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm font-medium text-slate-800 shadow-sm"
          @change="load"
        >
          <option value="">All classes</option>
          <option v-for="c in classOpts" :key="c.id" :value="String(c.id)">{{ c.level_name ? c.level_name + ' · ' : '' }}{{ c.name }}</option>
        </select>
      </div>
      <div class="min-w-[8rem]">
        <label class="mb-1.5 block text-2xs font-semibold uppercase tracking-wide text-slate-500">Fee status</label>
        <select
          v-model="filters.feeStatus"
          class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm font-medium text-slate-800 shadow-sm"
          @change="load"
        >
          <option value="all">All</option>
          <option value="paid">Paid</option>
          <option value="partial">Partial</option>
          <option value="unpaid">Unpaid</option>
        </select>
      </div>
      <div class="min-w-[8rem]">
        <label class="mb-1.5 block text-2xs font-semibold uppercase tracking-wide text-slate-500">Enrollment status</label>
        <select
          v-model="filters.enrollmentStatus"
          class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm font-medium text-slate-800 shadow-sm"
          @change="load"
        >
          <option value="all">All</option>
          <option value="active">Active</option>
          <option value="partial">Partial</option>
          <option value="pending">Pending</option>
        </select>
      </div>
      <label
        class="flex min-h-[2.75rem] cursor-pointer items-center gap-2.5 rounded-xl border border-transparent px-1 text-sm font-medium text-slate-700 hover:bg-white/60"
      >
        <input v-model="filters.onboardedOnly" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500/30" @change="load" />
        Fully onboarded only
      </label>
    </div>

    <DataTable
      v-model:current-page="tablePage"
      title="Enrolled students"
      class="mt-6"
      sticky-header
      comfortable
      :columns="columns"
      :data="displayRows"
      row-key="enrollment_id"
      searchable
      search-placeholder="Search name, admission #, phone…"
      :search-keys="['student_name', 'admission_number', 'guardian_phone']"
      :pagination="true"
      :page-size="15"
      sortable
      empty-text="No enrollments for these filters."
    >
      <template #cell-student_name="{ row }">
        <div class="flex max-w-[min(100%,20rem)] items-start gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-50 to-brand-100/80 text-brand-600 ring-1 ring-brand-200/60"
          >
            <UserIcon class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-semibold text-slate-900">{{ row.student_name }}</span>
              <span
                v-if="row._onboardedHighlight"
                class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-semibold uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200/70"
                title="Fully onboarded for this period"
              >
                Onboarded
              </span>
            </div>
            <p class="mt-0.5 font-mono text-xs text-slate-500">{{ row.admission_number }}</p>
            <p v-if="row.guardian_phone" class="mt-0.5 text-xs text-slate-400">{{ row.guardian_phone }}</p>
          </div>
        </div>
      </template>

      <template #cell-fee_status_label="{ row }">
        <span
          class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-2xs font-semibold uppercase tracking-wide ring-1"
          :class="feeBadge(row.fee_status_label)"
        >
          <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-70" aria-hidden="true" />
          {{ row.fee_status_label }}
        </span>
      </template>
      <template #cell-enrollment_ops_status="{ row }">
        <span
          class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-2xs font-semibold uppercase tracking-wide ring-1"
          :class="opsBadge(row.enrollment_ops_status)"
        >
          <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-70" aria-hidden="true" />
          {{ opsLabel(row.enrollment_ops_status) }}
        </span>
      </template>
      <template #cell-balance_due="{ row }">
        <span class="text-sm font-semibold tabular-nums tracking-tight" :class="balanceClass(row)">
          {{ formatMoney(row.balance_due) }}
        </span>
      </template>

      <template #actions="{ row }">
        <RowActionsMenu v-slot="{ close }">
          <router-link
            to="/students"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
            @click="close"
          >
            Student directory
          </router-link>
          <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-emerald-700 transition hover:bg-emerald-50/80 disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="!row.invoice_id"
            @click="openPay(row); close()"
          >
            Record payment
          </button>
          <router-link
            v-if="row.invoice_id"
            to="/finance/invoices"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
            @click="close"
          >
            View invoices
          </router-link>
          <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
            @click="openTransfer(row); close()"
          >
            Transfer class / stream
          </button>
          <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-rose-700 transition hover:bg-rose-50/80"
            @click="confirmWithdraw(row, close)"
          >
            Withdraw
          </button>
        </RowActionsMenu>
      </template>

      <template #empty>
        <div class="mx-auto max-w-md px-4 py-6">
          <div
            class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200/60 text-slate-400"
          >
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
              />
            </svg>
          </div>
          <p class="text-center text-lg font-semibold text-slate-800">No enrollments in this view</p>
          <p class="mt-2 text-center text-sm text-slate-500">Change year, period, or class filters — or enroll applicants from the Applicants page.</p>
        </div>
      </template>
    </DataTable>

    <Modal v-model="showPay" title="Record payment">
      <p v-if="payRow" class="mb-3 text-sm text-slate-600">
        Invoice balance: <strong>{{ formatMoney(payRow.balance_due) }}</strong>
      </p>
      <FormInput v-model.number="payForm.amount" type="number" min="0" step="0.01" label="Amount" />
      <SelectInput v-model="payForm.method" label="Method" :options="payMethods" />
      <FormInput v-model="payForm.reference" label="Reference" />
      <template #footer>
        <Button type="button" variant="secondary" @click="showPay = false">Cancel</Button>
        <Button type="button" :disabled="paySubmitting" @click="submitPay">{{ paySubmitting ? 'Saving…' : 'Record' }}</Button>
      </template>
    </Modal>

    <Modal v-model="showTransfer" title="Transfer class / stream">
      <SelectInput v-model="transferForm.classId" label="Class" :options="transferClassOpts" placeholder="Class" />
      <SelectInput v-model="transferForm.streamId" label="Stream" :options="transferStreamOpts" placeholder="No stream" />
      <template #footer>
        <Button type="button" variant="secondary" @click="showTransfer = false">Cancel</Button>
        <Button type="button" :disabled="transferSubmitting" @click="submitTransfer">{{ transferSubmitting ? 'Saving…' : 'Update' }}</Button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import RowActionsMenu from '@/components/ui/RowActionsMenu.vue'
import { UserIcon } from '@heroicons/vue/24/outline'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { admissionsApi } from '@/services/admissionsApi'
import { financeApi } from '@/services/financeApi'
import useNotificationStore from '@/stores/notificationStore'

const { successToast, errorToast } = useNotificationStore()

const loading = ref(true)
const rawRows = ref([])
const stats = ref({})
const ctx = ref(null)

const filters = reactive({
  academicYearId: '',
  studyPeriodId: '',
  classId: '',
  feeStatus: 'all',
  enrollmentStatus: 'all',
  onboardedOnly: false
})

const tablePage = ref(1)

const columns = [
  { key: 'student_name', label: 'Student', sortable: true, noDefaultWeight: true },
  { key: 'class_name', label: 'Class' },
  { key: 'stream_name', label: 'Stream' },
  { key: 'gender', label: 'Gender' },
  { key: 'fee_status_label', label: 'Fee', sortable: true },
  { key: 'balance_due', label: 'Balance', sortable: true },
  { key: 'enrolled_at', label: 'Enrolled', sortable: true },
  { key: 'enrollment_ops_status', label: 'Status', sortable: true }
]

const yearOpts = computed(() => ctx.value?.academicYears || [])
const periodOpts = computed(() => {
  const y = filters.academicYearId
  if (!y) return ctx.value?.studyPeriods || []
  return (ctx.value?.studyPeriods || []).filter((p) => String(p.academic_year_id) === String(y))
})
const classOpts = computed(() => ctx.value?.classes || [])

const displayRows = computed(() => rawRows.value)

const statCards = computed(() => [
  { key: 'enrollments_total', label: 'Total enrollments' },
  { key: 'fully_onboarded', label: 'Fully onboarded' },
  { key: 'outstanding_invoices', label: 'Outstanding' },
  { key: 'accepted', label: 'Accepted applicants' },
  { key: 'enrolled', label: 'Enrolled applicants' }
])

function formatMoney(n) {
  const x = Number(n)
  if (Number.isNaN(x)) return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'GHS', minimumFractionDigits: 2 }).format(x)
}

function feeBadge(label) {
  if (label === 'Paid') return 'bg-emerald-50 text-emerald-800 ring-emerald-200/70'
  if (label === 'Partial') return 'bg-amber-50 text-amber-900 ring-amber-200/70'
  return 'bg-rose-50 text-rose-800 ring-rose-200/70'
}

function opsBadge(s) {
  if (s === 'active') return 'bg-emerald-50 text-emerald-800 ring-emerald-200/70'
  if (s === 'partial') return 'bg-amber-50 text-amber-900 ring-amber-200/70'
  return 'bg-rose-50 text-rose-800 ring-rose-200/70'
}

function balanceClass(row) {
  const bal = Number(row.balance_due ?? 0)
  if (bal < 0.01) return 'text-emerald-700'
  if (row.fee_status_label === 'Partial') return 'text-amber-700'
  return 'text-rose-700'
}

function opsLabel(s) {
  const m = { active: 'Active', partial: 'Partial', pending: 'Pending' }
  return m[s] || s
}

async function loadContext() {
  const { data } = await admissionsApi.context()
  ctx.value = data?.data || null
  const y = ctx.value?.academicYear?.id
  const p = ctx.value?.studyPeriod?.id
  if (!filters.academicYearId && y) filters.academicYearId = String(y)
  if (!filters.studyPeriodId && p) filters.studyPeriodId = String(p)
}

function onFilterChange() {
  const y = filters.academicYearId
  const periods = (ctx.value?.studyPeriods || []).filter((p) => !y || String(p.academic_year_id) === String(y))
  const ok = periods.some((p) => String(p.id) === String(filters.studyPeriodId))
  if (!ok) filters.studyPeriodId = ''
  load()
}

async function load() {
  loading.value = true
  try {
    const [listRes, statsRes] = await Promise.all([
      admissionsApi.enrollmentsList({
        academicYearId: filters.academicYearId || undefined,
        studyPeriodId: filters.studyPeriodId || undefined,
        classId: filters.classId || undefined,
        feeStatus: filters.feeStatus !== 'all' ? filters.feeStatus : undefined,
        enrollmentStatus: filters.enrollmentStatus !== 'all' ? filters.enrollmentStatus : undefined,
        onboardedOnly: filters.onboardedOnly ? 1 : undefined
      }),
      admissionsApi.stats()
    ])
    rawRows.value = (listRes.data?.data || []).map((r) => {
      const bal = Number(r.balance_due ?? 0)
      const fee = r.fee_status_label
      const cleared = bal < 0.01
      const isPaidFee = fee === 'Paid' || cleared
      const defaulter = !isPaidFee && bal > 0.01
      const hasAdmission = String(r.admission_number || '').trim() !== ''
      const _onboardedHighlight = r.enrollment_ops_status === 'active' && cleared && hasAdmission
      let rowClass = ''
      if (_onboardedHighlight) {
        rowClass = 'bg-emerald-50/40 border-l-[3px] border-l-emerald-400/85'
      } else if (defaulter) {
        rowClass = 'bg-rose-50/50 border-l-[3px] border-l-rose-400/75'
      }
      return {
        ...r,
        class_name: r.class_name || '—',
        stream_name: r.stream_name || '—',
        enrolled_at: r.enrolled_at ? String(r.enrolled_at).slice(0, 10) : '—',
        rowClass,
        _onboardedHighlight
      }
    })
    stats.value = statsRes.data?.data || {}
  } catch (e) {
    errorToast('Load failed', e?.response?.data?.error || e?.message)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadContext()
  await load()
})

const showPay = ref(false)
const payRow = ref(null)
const paySubmitting = ref(false)
const payForm = reactive({ amount: '', method: 'cash', reference: '' })
const payMethods = [
  { value: 'cash', label: 'Cash' },
  { value: 'mobile_money', label: 'Mobile money' },
  { value: 'bank', label: 'Bank' },
  { value: 'card', label: 'Card' },
  { value: 'other', label: 'Other' }
]

function openPay(row) {
  payRow.value = row
  payForm.amount = row.balance_due > 0 ? String(row.balance_due) : ''
  payForm.method = 'cash'
  payForm.reference = ''
  showPay.value = true
}

async function submitPay() {
  if (!payRow.value?.invoice_id) return
  paySubmitting.value = true
  try {
    await financeApi.payments.record({
      invoiceId: payRow.value.invoice_id,
      amount: Number(payForm.amount),
      method: payForm.method,
      reference: payForm.reference,
      paidAt: new Date().toISOString().slice(0, 10)
    })
    successToast('Payment', 'Recorded.')
    showPay.value = false
    await load()
  } catch (e) {
    errorToast('Payment', e?.response?.data?.error || e?.message)
  } finally {
    paySubmitting.value = false
  }
}

const showTransfer = ref(false)
const transferRow = ref(null)
const transferSubmitting = ref(false)
const transferForm = reactive({ classId: '', streamId: '' })

const transferClassOpts = computed(() =>
  (ctx.value?.classes || []).map((c) => ({
    value: String(c.id),
    label: `${c.level_name ? c.level_name + ' · ' : ''}${c.name}`
  }))
)

const transferStreamOpts = computed(() => [
  { value: '', label: 'No stream' },
  ...(ctx.value?.streams || []).map((s) => ({ value: String(s.id), label: s.name }))
])

function openTransfer(row) {
  transferRow.value = row
  transferForm.classId = String(row.class_id)
  transferForm.streamId = row.stream_id ? String(row.stream_id) : ''
  showTransfer.value = true
}

async function submitTransfer() {
  if (!transferRow.value || !transferForm.classId) return
  transferSubmitting.value = true
  try {
    await admissionsApi.enrollmentPatch({
      enrollmentId: transferRow.value.enrollment_id,
      classId: Number(transferForm.classId),
      streamId: transferForm.streamId ? Number(transferForm.streamId) : undefined
    })
    successToast('Updated', 'Class / stream saved.')
    showTransfer.value = false
    await load()
  } catch (e) {
    errorToast('Transfer', e?.response?.data?.error || e?.message)
  } finally {
    transferSubmitting.value = false
  }
}

async function confirmWithdraw(row, close) {
  if (!confirm(`Withdraw ${row.student_name} from this enrollment?`)) return
  try {
    await admissionsApi.enrollmentWithdraw({ enrollmentId: row.enrollment_id })
    successToast('Withdrawn', 'Enrollment removed.')
    close?.()
    await load()
  } catch (e) {
    errorToast('Withdraw', e?.response?.data?.error || e?.message)
  }
}

watch(showTransfer, async (v) => {
  if (v && !ctx.value) await loadContext()
})
</script>
