<template>
  <div class="page-shell max-w-[1400px]">
    <PageHeader title="Invoices" description="One bill per student per term. Open a row for lines, installments, and payments." />

    <div class="mb-4 flex flex-wrap items-end gap-3">
      <SelectInput v-model="filterYear" label="Year" :options="yearOpts" class="min-w-[160px]" @update:model-value="reload" />
      <SelectInput v-model="filterPeriod" label="Period" :options="periodOpts" class="min-w-[160px]" @update:model-value="reload" />
      <SelectInput v-model="filterStatus" label="Status" :options="statusOpts" class="min-w-[140px]" @update:model-value="reload" />
      <Button type="button" variant="secondary" :disabled="loading" @click="reload">Refresh</Button>
      <Button type="button" @click="genOpen = true">Generate invoice</Button>
    </div>

    <DataTable
      title="All invoices"
      :columns="cols"
      :data="raw"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="8"
      v-model:current-page="page"
    >
      <template #cell-total_amount="{ value }">{{ formatCurrency(value) }}</template>
      <template #cell-amount_paid="{ value }">{{ formatCurrency(value) }}</template>
      <template #cell-balance_due="{ value }">{{ formatCurrency(value) }}</template>
      <template #cell-status="{ value }">
        <Badge :tone="invTone(value)">{{ statusLabel(value) }}</Badge>
      </template>
      <template #actions="{ row }">
        <Button variant="ghost" type="button" class="!text-xs" @click="openDetail(row.id)">View</Button>
      </template>
    </DataTable>

    <Modal v-model="detailOpen" :title="detail?.invoice_number || 'Invoice'">
      <div v-if="detail" class="max-h-[70vh] space-y-4 overflow-y-auto text-sm">
        <div class="grid gap-2 sm:grid-cols-2">
          <p><span class="text-slate-500">Student:</span> {{ detail.student_name }} ({{ detail.admission_number }})</p>
          <p><span class="text-slate-500">Due:</span> {{ detail.due_date || '—' }}</p>
          <p><span class="text-slate-500">Total:</span> {{ formatCurrency(detail.total_amount) }}</p>
          <p><span class="text-slate-500">Paid / Balance:</span> {{ formatCurrency(detail.amount_paid) }} / {{ formatCurrency(detail.balance_due) }}</p>
        </div>
        <div>
          <h4 class="mb-2 text-xs font-semibold uppercase text-slate-500">Line items</h4>
          <table class="w-full text-sm">
            <thead class="text-2xs uppercase text-slate-400">
              <tr>
                <th class="py-1 text-left">Item</th>
                <th class="py-1 text-right">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ln in detail.lines || []" :key="ln.id" class="border-t border-sc-line/80">
                <td class="py-1">{{ ln.label }}</td>
                <td class="py-1 text-right tabular-nums">{{ formatCurrency(ln.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="detail.installments?.length">
          <h4 class="mb-2 text-xs font-semibold uppercase text-slate-500">Installments</h4>
          <ul class="space-y-1">
            <li v-for="ins in detail.installments" :key="ins.id" class="flex justify-between text-xs">
              <span>#{{ ins.sequence_no }} due {{ ins.due_date }}</span>
              <span>{{ formatCurrency(ins.amount) }}</span>
            </li>
          </ul>
        </div>
        <div v-if="detail.payments?.length">
          <h4 class="mb-2 text-xs font-semibold uppercase text-slate-500">Payments</h4>
          <table class="w-full text-xs">
            <tr v-for="p in detail.payments" :key="p.id" class="border-t border-sc-line/80">
              <td class="py-1 font-mono">{{ p.receipt_number }}</td>
              <td class="py-1">{{ p.paid_at }}</td>
              <td class="py-1 text-right">{{ formatCurrency(p.amount) }}</td>
              <td class="py-1 capitalize">{{ p.method }}</td>
            </tr>
          </table>
        </div>
        <div class="flex flex-wrap gap-2 border-t border-sc-line pt-3">
          <Button
            v-if="Number(detail.amount_paid) < 0.01"
            type="button"
            variant="secondary"
            :disabled="regenLoading"
            @click="doRegenerate"
          >
            Regenerate from fee structure
          </Button>
          <Button type="button" @click="goPay">Record payment</Button>
        </div>
      </div>
    </Modal>

    <Modal v-model="genOpen" title="Generate invoice">
      <SelectInput v-model="genForm.academicYearId" label="Year" :options="yearOptsGen" />
      <SelectInput v-model="genForm.studyPeriodId" label="Period" :options="periodOptsGen" />
      <SelectInput v-model="genForm.classId" label="Class" :options="classOpts" @update:model-value="loadEnrolled" />
      <SelectInput v-model="genForm.studentId" label="Student" :options="studentOpts" />
      <FormInput v-model="genForm.dueDate" label="Due date" type="date" />
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="genForm.applyCredit" type="checkbox" class="rounded border-slate-300 text-brand-600" />
        Apply credit wallet (if any)
      </label>
      <template #footer>
        <Button variant="secondary" type="button" @click="genOpen = false">Cancel</Button>
        <Button type="button" :disabled="genLoading" @click="doGenerate">{{ genLoading ? '…' : 'Generate' }}</Button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import FormInput from '@/components/ui/FormInput.vue'
import { formatCurrency } from '@/utils/formatters'
import { financeApi } from '@/services/financeApi'
import useNotificationStore from '@/stores/notificationStore'

const route = useRoute()
const router = useRouter()
const { successToast, errorToast } = useNotificationStore()

const loading = ref(false)
const page = ref(1)
const raw = ref([])
const years = ref([])
const periods = ref([])
const classes = ref([])
const filterYear = ref('')
const filterPeriod = ref('')
const filterStatus = ref('')

const detailOpen = ref(false)
const detail = ref(null)
const regenLoading = ref(false)

const genOpen = ref(false)
const genLoading = ref(false)
const enrolled = ref([])
const genForm = ref({
  academicYearId: '',
  studyPeriodId: '',
  classId: '',
  studentId: '',
  dueDate: '',
  applyCredit: false
})

const cols = [
  { key: 'invoice_number', label: 'Invoice', sortable: true },
  { key: 'student_name', label: 'Student', sortable: true },
  { key: 'total_amount', label: 'Total', sortable: true },
  { key: 'amount_paid', label: 'Paid', sortable: true },
  { key: 'balance_due', label: 'Balance', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'due_date', label: 'Due', sortable: true }
]

const yearOpts = computed(() => [{ value: '', label: 'All' }, ...years.value.map((y) => ({ value: String(y.id), label: y.name }))])
const periodOpts = computed(() => {
  if (!filterYear.value) return [{ value: '', label: 'All' }, ...periods.value.map((p) => ({ value: String(p.id), label: p.name }))]
  const y = Number(filterYear.value)
  return [
    { value: '', label: 'All' },
    ...periods.value.filter((p) => Number(p.academic_year_id) === y).map((p) => ({ value: String(p.id), label: p.name }))
  ]
})
const statusOpts = [
  { value: '', label: 'All' },
  { value: 'unpaid', label: 'Unpaid' },
  { value: 'partial', label: 'Partial' },
  { value: 'paid', label: 'Paid' }
]

const yearOptsGen = computed(() => [{ value: '', label: 'Select' }, ...years.value.map((y) => ({ value: String(y.id), label: y.name }))])
const periodOptsGen = computed(() => {
  if (!genForm.value.academicYearId) return [{ value: '', label: 'Select period' }]
  const y = Number(genForm.value.academicYearId)
  return [
    { value: '', label: 'Select period' },
    ...periods.value.filter((p) => Number(p.academic_year_id) === y).map((p) => ({ value: String(p.id), label: p.name }))
  ]
})
const classOpts = computed(() => [{ value: '', label: 'Select class' }, ...classes.value.map((c) => ({ value: String(c.id), label: `${c.level_name} ${c.name}` }))])
const studentOpts = computed(() => [
  { value: '', label: 'Select student' },
  ...enrolled.value.map((s) => ({ value: String(s.id), label: `${s.first_name} ${s.last_name} (${s.admission_number})` }))
])

function invTone(s) {
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
  loading.value = true
  try {
    const params = {}
    if (filterYear.value) params.academicYearId = filterYear.value
    if (filterPeriod.value) params.studyPeriodId = filterPeriod.value
    if (filterStatus.value) params.status = filterStatus.value
    const { data } = await financeApi.invoices.list(params)
    raw.value = data?.data || []
  } catch (e) {
    errorToast('Invoices', e?.response?.data?.error || e?.message)
  } finally {
    loading.value = false
  }
}

async function openDetail(id) {
  try {
    const { data } = await financeApi.invoices.get(id)
    detail.value = data?.data
    detailOpen.value = true
  } catch (e) {
    errorToast('Load', e?.response?.data?.error || e?.message)
  }
}

async function doRegenerate() {
  if (!detail.value?.id) return
  regenLoading.value = true
  try {
    await financeApi.invoices.regenerate(detail.value.id)
    successToast('OK', 'Invoice regenerated.')
    await openDetail(detail.value.id)
    await reload()
  } catch (e) {
    errorToast('Regenerate', e?.response?.data?.error || e?.message)
  } finally {
    regenLoading.value = false
  }
}

function goPay() {
  if (!detail.value?.id) return
  detailOpen.value = false
  router.push({ name: 'Payments', query: { invoice: String(detail.value.id) } })
}

async function loadEnrolled() {
  enrolled.value = []
  genForm.value.studentId = ''
  if (!genForm.value.academicYearId || !genForm.value.studyPeriodId || !genForm.value.classId) return
  try {
    const { data } = await financeApi.enrolledStudents({
      academicYearId: genForm.value.academicYearId,
      studyPeriodId: genForm.value.studyPeriodId,
      classId: genForm.value.classId
    })
    enrolled.value = data?.data || []
  } catch {
    enrolled.value = []
  }
}

async function doGenerate() {
  genLoading.value = true
  try {
    await financeApi.invoices.generate({
      studentId: Number(genForm.value.studentId),
      academicYearId: Number(genForm.value.academicYearId),
      studyPeriodId: Number(genForm.value.studyPeriodId),
      classId: Number(genForm.value.classId),
      dueDate: genForm.value.dueDate || undefined,
      applyCredit: genForm.value.applyCredit
    })
    successToast('Created', 'Invoice generated.')
    genOpen.value = false
    await reload()
  } catch (e) {
    errorToast('Generate', e?.response?.data?.error || e?.message)
  } finally {
    genLoading.value = false
  }
}

watch(
  () => [genForm.value.academicYearId, genForm.value.studyPeriodId, genForm.value.classId],
  () => loadEnrolled()
)

onMounted(async () => {
  await loadContext()
  await reload()
  if (route.query.open) {
    await openDetail(Number(route.query.open))
    router.replace({ query: {} })
  }
})
</script>
