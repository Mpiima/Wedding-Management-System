<template>
  <div class="page-shell max-w-[1100px]">
    <PageHeader
      title="Exam types"
      description="Define exam types (e.g. BOT, MOT, EOT) per academic year and study period. Set maximum scores, status, and optional report card columns."
    />

    <div v-if="loadingMeta" class="py-12 text-center text-slate-500">Loading…</div>

    <template v-else>
      <div class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft">
        <SelectInput
          v-model="filterYearId"
          label="Academic year"
          class="min-w-[200px] flex-1"
          :options="yearOpts"
          placeholder="Select year"
          required
        />
        <SelectInput
          v-model="filterPeriodId"
          label="Study period"
          class="min-w-[200px] flex-1"
          :options="periodOpts"
          placeholder="Select period"
          required
        />
        <div class="flex flex-wrap gap-2 pb-0.5">
          <Button type="button" variant="secondary" @click="loadList">Refresh</Button>
          <Button type="button" :disabled="!canList" @click="openModal()">Add exam type</Button>
        </div>
      </div>

      <div v-if="loadingList" class="py-10 text-center text-slate-500">Loading exam types…</div>

      <div v-else class="overflow-hidden rounded-2xl border border-sc-line bg-white shadow-soft">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-2xs uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left">Name</th>
              <th class="px-4 py-3 text-right">Max score</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Report card</th>
              <th class="px-4 py-3 text-right">Column #</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id" class="border-t border-sc-line">
              <td class="px-4 py-3 font-medium">{{ row.name }}</td>
              <td class="px-4 py-3 text-right tabular-nums">{{ row.max_score }}</td>
              <td class="px-4 py-3 capitalize">{{ row.status === 'active' ? 'Active' : 'Not active' }}</td>
              <td class="px-4 py-3">{{ row.show_on_report_card ? 'Yes' : 'No' }}</td>
              <td class="px-4 py-3 text-right tabular-nums">{{ row.show_on_report_card ? row.report_column_no : '—' }}</td>
              <td class="px-4 py-3 text-right">
                <Button variant="ghost" type="button" @click="openModal(row)">Edit</Button>
                <Button variant="ghost" type="button" class="text-rose-700" @click="remove(row)">Delete</Button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="!rows.length && canList" class="py-10 text-center text-sm text-slate-400">No exam types for this year and period.</p>
        <p v-if="!canList" class="py-10 text-center text-sm text-slate-400">Select an academic year and study period.</p>
      </div>
    </template>

    <Modal v-model="modalOpen" :title="form.id ? 'Edit exam type' : 'New exam type'">
      <FormInput v-model="form.name" label="Name" placeholder="e.g. BOT, MOT, EOT" required />
      <FormInput v-model="form.maxScore" label="Maximum score" type="number" step="0.01" min="0.01" required />
      <SelectInput v-model="form.status" label="Status" :options="statusOpts" />
      <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-sc-line bg-slate-50/80 px-3 py-2.5">
        <input
          v-model="form.showOnReportCard"
          type="checkbox"
          class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
        />
        <span class="text-sm font-medium text-slate-800">Show on report card</span>
      </label>
      <FormInput
        v-if="form.showOnReportCard"
        v-model="form.reportColumnNo"
        label="Report column no."
        type="number"
        min="1"
        step="1"
        placeholder="Position on the report (1, 2, 3…)"
        required
      />
      <template #footer>
        <Button variant="secondary" type="button" @click="modalOpen = false">Cancel</Button>
        <Button type="button" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save' }}</Button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { financeApi } from '@/services/financeApi'
import { erpApi } from '@/services/erpSetupApi'
import useNotificationStore from '@/stores/notificationStore'

const { successToast, errorToast } = useNotificationStore()

const loadingMeta = ref(true)
const loadingList = ref(false)
const saving = ref(false)
const years = ref([])
const periods = ref([])
const rows = ref([])

const filterYearId = ref('')
const filterPeriodId = ref('')

const modalOpen = ref(false)
const form = reactive({
  id: null,
  name: '',
  maxScore: '100',
  status: 'active',
  showOnReportCard: false,
  reportColumnNo: ''
})

const statusOpts = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Not active' }
]

const yearOpts = computed(() => [{ value: '', label: 'Select year' }, ...years.value.map((y) => ({ value: String(y.id), label: y.name }))])

const periodOpts = computed(() => {
  if (!filterYearId.value) return [{ value: '', label: 'Select period' }]
  const y = Number(filterYearId.value)
  const list = periods.value.filter((p) => Number(p.academic_year_id) === y)
  return [
    { value: '', label: list.length ? 'Select period' : 'No periods for this year' },
    ...list.map((p) => ({ value: String(p.id), label: p.name }))
  ]
})

const canList = computed(() => Boolean(filterYearId.value && filterPeriodId.value))

async function loadMeta() {
  loadingMeta.value = true
  try {
    const [fin, erp] = await Promise.all([financeApi.context(), erpApi.context()])
    years.value = fin.data?.data?.academicYears || []
    periods.value = fin.data?.data?.studyPeriods || []

    const activeYear = erp.data?.data?.academicYear
    const activePeriod = erp.data?.data?.studyPeriod

    if (activeYear?.id) {
      filterYearId.value = String(activeYear.id)
    } else if (years.value.length) {
      const active = years.value.find((y) => Number(y.is_active) === 1)
      filterYearId.value = String((active || years.value[0]).id)
    }

    if (activePeriod?.id) {
      filterPeriodId.value = String(activePeriod.id)
    }
    if (!filterPeriodId.value && filterYearId.value) {
      const y = Number(filterYearId.value)
      const forYear = periods.value.filter((p) => Number(p.academic_year_id) === y)
      const ap = forYear.find((p) => Number(p.is_active) === 1)
      filterPeriodId.value = ap ? String(ap.id) : forYear[0] ? String(forYear[0].id) : ''
    }
    if (filterYearId.value && filterPeriodId.value) {
      const ok = periods.value.some(
        (p) =>
          String(p.id) === filterPeriodId.value && Number(p.academic_year_id) === Number(filterYearId.value)
      )
      if (!ok) {
        const y = Number(filterYearId.value)
        const forYear = periods.value.filter((p) => Number(p.academic_year_id) === y)
        const ap = forYear.find((p) => Number(p.is_active) === 1)
        filterPeriodId.value = ap ? String(ap.id) : forYear[0] ? String(forYear[0].id) : ''
      }
    }
  } catch (e) {
    errorToast('Load failed', e?.response?.data?.error || e?.message)
  } finally {
    loadingMeta.value = false
  }
}

async function loadList() {
  if (!canList.value) {
    rows.value = []
    return
  }
  loadingList.value = true
  try {
    const res = await erpApi.examTypes.list({
      academicYearId: filterYearId.value,
      studyPeriodId: filterPeriodId.value
    })
    rows.value = res.data?.data || []
  } catch (e) {
    errorToast('Could not load exam types', e?.response?.data?.error || e?.message)
    rows.value = []
  } finally {
    loadingList.value = false
  }
}

watch(
  filterYearId,
  (y) => {
    if (!y) {
      filterPeriodId.value = ''
      return
    }
    const forYear = periods.value.filter((p) => Number(p.academic_year_id) === Number(y))
    const stillValid = forYear.some((p) => String(p.id) === filterPeriodId.value)
    if (!stillValid) {
      const ap = forYear.find((p) => Number(p.is_active) === 1)
      filterPeriodId.value = ap ? String(ap.id) : forYear[0] ? String(forYear[0].id) : ''
    }
  },
  { flush: 'sync' }
)

watch([filterYearId, filterPeriodId], async () => {
  if (!filterYearId.value || !filterPeriodId.value) {
    rows.value = []
    return
  }
  const forYear = periods.value.filter((p) => Number(p.academic_year_id) === Number(filterYearId.value))
  if (!forYear.some((p) => String(p.id) === filterPeriodId.value)) {
    rows.value = []
    return
  }
  await loadList()
})

function resetForm() {
  form.id = null
  form.name = ''
  form.maxScore = '100'
  form.status = 'active'
  form.showOnReportCard = false
  form.reportColumnNo = ''
}

function openModal(row = null) {
  if (!filterYearId.value || !filterPeriodId.value) {
    errorToast('Select year and period', 'Choose an academic year and study period first.')
    return
  }
  if (row) {
    form.id = row.id
    form.name = row.name
    form.maxScore = String(row.max_score ?? '')
    form.status = row.status === 'inactive' ? 'inactive' : 'active'
    form.showOnReportCard = Boolean(Number(row.show_on_report_card))
    form.reportColumnNo = row.report_column_no != null ? String(row.report_column_no) : ''
  } else {
    resetForm()
  }
  modalOpen.value = true
}

async function save() {
  const name = form.name.trim()
  if (!name) {
    errorToast('Validation', 'Name is required.')
    return
  }
  const maxScore = Number(form.maxScore)
  if (!Number.isFinite(maxScore) || maxScore <= 0) {
    errorToast('Validation', 'Enter a valid maximum score.')
    return
  }
  if (form.showOnReportCard) {
    const col = Number(form.reportColumnNo)
    if (!Number.isInteger(col) || col < 1) {
      errorToast('Validation', 'Report column no. must be a positive whole number.')
      return
    }
  }

  const body = {
    academicYearId: Number(filterYearId.value),
    studyPeriodId: Number(filterPeriodId.value),
    name,
    maxScore,
    status: form.status,
    showOnReportCard: form.showOnReportCard,
    reportColumnNo: form.showOnReportCard ? Number(form.reportColumnNo) : null
  }
  if (form.id) body.id = form.id

  saving.value = true
  try {
    if (form.id) {
      await erpApi.examTypes.update(body)
    } else {
      await erpApi.examTypes.create(body)
    }
    successToast('Saved', '')
    modalOpen.value = false
    await loadList()
  } catch (e) {
    errorToast('Save failed', e?.response?.data?.error || e?.message)
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  if (!confirm(`Delete exam type “${row.name}”?`)) return
  try {
    await erpApi.examTypes.delete(row.id)
    successToast('Deleted', '')
    await loadList()
  } catch (e) {
    errorToast('Delete failed', e?.response?.data?.error || e?.message)
  }
}

onMounted(async () => {
  await loadMeta()
})
</script>
