<template>
  <div class="page-shell max-w-[1400px] pb-28">
    <PageHeader
      title="School General Attendance"
      description="Gate attendance for all enrolled students. Capture daily check-in and check-out times quickly."
    />

    <div v-if="loadingMeta" class="py-12 text-center text-slate-500">Loading context...</div>
    <template v-else>
      <div class="mb-4 grid grid-cols-1 gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft md:grid-cols-3">
        <SelectInput v-model="filterYearId" label="Academic year" :options="yearOptions" />
        <SelectInput v-model="filterPeriodId" label="Study period" :options="periodOptions" />
        <FormInput v-model="attendanceDate" type="date" label="Date" />
      </div>

      <div class="mb-4 grid grid-cols-1 gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft lg:grid-cols-6">
        <FormInput v-model="bulkCheckInTime" type="time" label="Bulk check-in time" />
        <FormInput v-model="bulkCheckOutTime" type="time" label="Bulk check-out time" />
        <div class="flex items-end gap-2 lg:col-span-4">
          <Button type="button" variant="secondary" @click="bulkSetCheckIn">Bulk check-in</Button>
          <Button type="button" variant="secondary" @click="bulkSetCheckOut">Bulk check-out</Button>
          <Button type="button" variant="secondary" @click="clearCheckOut">Clear all check-out</Button>
          <Button type="button" variant="secondary" :disabled="loadingRows" @click="loadRows">Reload</Button>
        </div>
      </div>

      <div v-if="loadingRows" class="py-12 text-center text-slate-500">Loading students...</div>
      <div v-else class="overflow-hidden rounded-2xl border border-sc-line bg-white shadow-soft">
        <div class="border-b border-sc-line bg-slate-50 px-4 py-3 text-sm text-slate-600">
          Students: <span class="font-semibold text-slate-900">{{ rows.length }}</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[960px] text-sm">
            <thead class="bg-slate-50 text-2xs uppercase text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">Student</th>
                <th class="px-4 py-3 text-left">Admission #</th>
                <th class="px-4 py-3 text-left">Class</th>
                <th class="px-4 py-3 text-left">Check-in</th>
                <th class="px-4 py-3 text-left">Check-out</th>
                <th class="px-4 py-3 text-left">Remarks</th>
                <th class="px-4 py-3 text-left">Audit</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.student_id" class="border-t border-sc-line hover:bg-slate-50/70">
                <td class="px-4 py-3 font-medium text-slate-900">{{ row.student_name }}</td>
                <td class="px-4 py-3 tabular-nums text-slate-600">{{ row.admission_number || '-' }}</td>
                <td class="px-4 py-3 text-slate-700">{{ row.class_label }}</td>
                <td class="px-4 py-3">
                  <input
                    v-model="row.check_in_time"
                    type="time"
                    class="w-full rounded-lg border border-sc-line px-2 py-1.5 focus:border-brand-500 focus:outline-none"
                    @input="dirty = true"
                  />
                </td>
                <td class="px-4 py-3">
                  <input
                    v-model="row.check_out_time"
                    type="time"
                    class="w-full rounded-lg border border-sc-line px-2 py-1.5 focus:border-brand-500 focus:outline-none"
                    @input="dirty = true"
                  />
                </td>
                <td class="px-4 py-3">
                  <input
                    v-model="row.remarks"
                    type="text"
                    class="w-full rounded-lg border border-sc-line px-2 py-1.5 focus:border-brand-500 focus:outline-none"
                    placeholder="Optional"
                    @input="dirty = true"
                  />
                </td>
                <td class="px-4 py-3 text-2xs text-slate-500">
                  <span v-if="row.updated_at">{{ prettyDateTime(row.updated_at) }}</span>
                  <span v-else>Not saved</span>
                  <span v-if="row.last_saved_by" class="block text-slate-600">{{ row.last_saved_by }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!rows.length" class="py-10 text-center text-sm text-slate-500">
          No enrolled students for selected year and study period.
        </p>
      </div>
    </template>

    <div v-if="rows.length" class="fixed bottom-0 left-0 right-0 z-20 border-t border-sc-line bg-white/95 px-4 py-3 backdrop-blur">
      <div class="mx-auto flex max-w-[1400px] items-center justify-between gap-2">
        <p class="text-sm" :class="dirty ? 'text-amber-700' : 'text-slate-500'">{{ dirty ? 'Unsaved changes' : 'Saved' }}</p>
        <Button type="button" :disabled="saving || !dirty" @click="saveRows">{{ saving ? 'Saving...' : 'Save gate attendance' }}</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { erpApi } from '@/services/erpSetupApi'
import { financeApi } from '@/services/financeApi'
import useNotificationStore from '@/stores/notificationStore'

const { successToast, errorToast } = useNotificationStore()

const loadingMeta = ref(true)
const loadingRows = ref(false)
const saving = ref(false)
const dirty = ref(false)

const filterYearId = ref('')
const filterPeriodId = ref('')
const attendanceDate = ref(today())
const bulkCheckInTime = ref(nowHHMM())
const bulkCheckOutTime = ref('')

const years = ref([])
const periods = ref([])
const rows = ref([])

const yearOptions = computed(() => [{ value: '', label: 'Select year' }, ...years.value.map((y) => ({ value: String(y.id), label: y.name }))])
const periodOptions = computed(() => {
  if (!filterYearId.value) return [{ value: '', label: 'Select period' }]
  const list = periods.value.filter((p) => Number(p.academic_year_id) === Number(filterYearId.value))
  return [{ value: '', label: list.length ? 'Select period' : 'No periods for this year' }, ...list.map((p) => ({ value: String(p.id), label: p.name }))]
})

function today() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
function nowHHMM() {
  const d = new Date()
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}
function prettyDateTime(value) {
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleString()
}

function normalizeToHHMM(value) {
  if (!value) return ''
  return String(value).slice(0, 5)
}

async function loadMeta() {
  loadingMeta.value = true
  try {
    const [finRes, erpCtxRes] = await Promise.all([financeApi.context(), erpApi.context()])
    const fin = finRes?.data?.data || {}
    const erpCtx = erpCtxRes?.data?.data || {}
    years.value = fin.academicYears || []
    periods.value = fin.studyPeriods || []

    const activeYear = erpCtx.academicYear || years.value.find((y) => Number(y.is_active) === 1) || years.value[0]
    if (activeYear) {
      filterYearId.value = String(activeYear.id)
      const forYear = periods.value.filter((p) => Number(p.academic_year_id) === Number(activeYear.id))
      const activePeriod = erpCtx.studyPeriod || forYear.find((p) => Number(p.is_active) === 1) || forYear[0]
      if (activePeriod) filterPeriodId.value = String(activePeriod.id)
    }
  } catch (e) {
    errorToast('Error', e?.message || 'Failed to load attendance context')
  } finally {
    loadingMeta.value = false
  }
}

async function loadRows() {
  if (!filterYearId.value || !filterPeriodId.value) {
    rows.value = []
    return
  }
  loadingRows.value = true
  try {
    const res = await erpApi.schoolAttendance.list({
      academicYearId: filterYearId.value,
      studyPeriodId: filterPeriodId.value,
      date: attendanceDate.value
    })
    const list = res?.data?.data?.students || []
    rows.value = list.map((r) => ({
      student_id: r.student_id,
      student_name: `${r.first_name} ${r.last_name}`.trim(),
      admission_number: r.admission_number || '',
      class_label: `${r.level_name || ''} ${r.class_name || ''}`.trim(),
      check_in_time: normalizeToHHMM(r.check_in_time),
      check_out_time: normalizeToHHMM(r.check_out_time),
      remarks: r.remarks || '',
      updated_at: r.updated_at || null,
      last_saved_by: r.last_saved_by || null
    }))
    dirty.value = false
  } catch (e) {
    errorToast('Error', e?.message || 'Failed to load school attendance')
    rows.value = []
  } finally {
    loadingRows.value = false
  }
}

function bulkSetCheckIn() {
  if (!bulkCheckInTime.value) return
  rows.value.forEach((r) => { r.check_in_time = bulkCheckInTime.value })
  dirty.value = true
}

function bulkSetCheckOut() {
  if (!bulkCheckOutTime.value) return
  rows.value.forEach((r) => { r.check_out_time = bulkCheckOutTime.value })
  dirty.value = true
}

function clearCheckOut() {
  rows.value.forEach((r) => { r.check_out_time = '' })
  dirty.value = true
}

async function saveRows() {
  if (!dirty.value || !rows.value.length) return
  saving.value = true
  try {
    await erpApi.schoolAttendance.save({
      academicYearId: Number(filterYearId.value),
      studyPeriodId: Number(filterPeriodId.value),
      date: attendanceDate.value,
      records: rows.value.map((r) => ({
        studentId: r.student_id,
        checkInTime: r.check_in_time || null,
        checkOutTime: r.check_out_time || null,
        remarks: r.remarks || ''
      }))
    })
    successToast('Saved', 'School general attendance saved.')
    await loadRows()
  } catch (e) {
    errorToast('Error', e?.message || 'Failed to save school attendance')
  } finally {
    saving.value = false
  }
}

watch(filterYearId, (v) => {
  if (!v) {
    filterPeriodId.value = ''
    return
  }
  const list = periods.value.filter((p) => Number(p.academic_year_id) === Number(v))
  if (!list.some((p) => String(p.id) === filterPeriodId.value)) {
    const active = list.find((p) => Number(p.is_active) === 1)
    filterPeriodId.value = active ? String(active.id) : list[0] ? String(list[0].id) : ''
  }
})

watch([filterYearId, filterPeriodId, attendanceDate], () => {
  if (filterYearId.value && filterPeriodId.value) loadRows()
})

onMounted(async () => {
  await loadMeta()
  await loadRows()
})
</script>

