<template>
  <div class="page-shell max-w-[960px]">
    <PageHeader
      title="Marks entry"
      description="Choose class, exam schedule, and subject — then enter marks for each enrolled student (same screen for new entries and updates)."
    />

    <div v-if="loadingMeta" class="py-12 text-center text-slate-500">Loading…</div>

    <template v-else>
      <div class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft">
        <SelectInput
          v-model="filterYearId"
          label="Academic year"
          class="min-w-[180px] flex-1"
          :options="yearOpts"
          placeholder="Select year"
          required
        />
        <SelectInput
          v-model="filterPeriodId"
          label="Study period"
          class="min-w-[180px] flex-1"
          :options="periodOpts"
          placeholder="Select period"
          required
        />
        <SelectInput
          v-model="filterClassId"
          label="Class"
          class="min-w-[200px] flex-1"
          :options="classOpts"
          placeholder="Select class"
          required
        />
        <SelectInput
          v-model="filterScheduleId"
          label="Exam schedule"
          class="min-w-[240px] flex-1"
          :options="scheduleOpts"
          placeholder="Select schedule"
          required
        />
        <SelectInput
          v-model="filterSubjectId"
          label="Subject"
          class="min-w-[200px] flex-1"
          :options="subjectOpts"
          placeholder="Select subject"
          required
        />
        <div class="pb-0.5">
          <Button type="button" variant="secondary" @click="reloadMarks">Refresh</Button>
        </div>
      </div>

      <div v-if="!canShowGrid" class="rounded-2xl border border-sc-line bg-white p-10 text-center text-sm text-slate-500 shadow-soft">
        Select academic year, study period, class, exam schedule, and subject to load students.
      </div>

      <div v-else-if="loadingGrid" class="py-12 text-center text-slate-500">Loading students…</div>

      <div v-else class="overflow-hidden rounded-2xl border border-sc-line bg-white shadow-soft">
        <div class="border-b border-sc-line bg-slate-50/90 px-4 py-3">
          <p class="text-sm font-semibold text-slate-900">
            {{ headerTitle }}
          </p>
          <p class="mt-0.5 text-xs text-slate-500">
            Maximum score: <span class="font-medium text-slate-700">{{ maxScore }}</span>
            <span v-if="students.length" class="ml-2">· {{ students.length }} student(s)</span>
          </p>
        </div>

        <div v-if="!students.length" class="p-10 text-center text-sm text-slate-500">
          No students enrolled in this class for this year and period. Use Admissions → Enrollments to enroll students.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[640px] text-sm">
            <thead class="bg-slate-50 text-2xs uppercase text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">Student</th>
                <th class="px-4 py-3 text-left">Marks</th>
                <th class="min-w-[200px] px-4 py-3 text-left">Comment</th>
                <th class="px-4 py-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in students" :key="`${s.student_id}-${filterSubjectId}`" class="border-t border-sc-line">
                <td class="px-4 py-3">
                  <span class="font-medium text-slate-900">{{ studentName(s) }}</span>
                  <span class="mt-0.5 block text-2xs text-slate-500">{{ s.admission_number }}</span>
                </td>
                <td class="px-4 py-2">
                  <input
                    v-model="s.scoreInput"
                    type="number"
                    :min="0"
                    :max="Number(maxScore) || 100"
                    step="0.01"
                    class="w-24 rounded-xl border border-sc-line px-2.5 py-2 text-sm tabular-nums shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
                    placeholder="—"
                  />
                </td>
                <td class="px-4 py-2">
                  <input
                    v-model="s.commentInput"
                    type="text"
                    class="w-full min-w-[160px] rounded-xl border border-sc-line px-2.5 py-2 text-sm shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
                    placeholder="Optional"
                  />
                </td>
                <td class="px-4 py-2 text-right">
                  <Button type="button" :disabled="savingKey === rowSavingKey(s)" @click="saveRow(s)">
                    {{ savingKey === rowSavingKey(s) ? 'Saving…' : 'Save' }}
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { financeApi } from '@/services/financeApi'
import { erpApi } from '@/services/erpSetupApi'
import useNotificationStore from '@/stores/notificationStore'

const { successToast, errorToast } = useNotificationStore()

const loadingMeta = ref(true)
const loadingGrid = ref(false)
const savingKey = ref(null)
const years = ref([])
const periods = ref([])
const classes = ref([])
const schedules = ref([])
const students = ref([])
const maxScore = ref('100')
const examTypeName = ref('')
const examDate = ref('')
const subjectName = ref('')

const filterYearId = ref('')
const filterPeriodId = ref('')
const filterClassId = ref('')
const filterScheduleId = ref('')
const filterSubjectId = ref('')

const yearOpts = computed(() => [{ value: '', label: 'Select year' }, ...years.value.map((y) => ({ value: String(y.id), label: y.name }))])

const periodOpts = computed(() => {
  if (!filterYearId.value) return [{ value: '', label: 'Select period' }]
  const y = Number(filterYearId.value)
  const list = periods.value.filter((p) => Number(p.academic_year_id) === y)
  return [
    { value: '', label: list.length ? 'Select period' : 'No periods' },
    ...list.map((p) => ({ value: String(p.id), label: p.name }))
  ]
})

const classOpts = computed(() => [
  { value: '', label: 'Select class' },
  ...classes.value.map((c) => ({
    value: String(c.id),
    label: `${c.level_name} ${c.name}`
  }))
])

const scheduleOpts = computed(() => {
  if (!filterClassId.value) return [{ value: '', label: 'Select class first' }]
  const cid = Number(filterClassId.value)
  const list = schedules.value.filter((sch) => Number(sch.class_id) === cid)
  return [
    { value: '', label: list.length ? 'Select schedule' : 'No schedules for this class' },
    ...list.map((s) => ({
      value: String(s.id),
      label: `${s.exam_type_name} · ${s.exam_date} · ${formatTime(s.exam_time)}`
    }))
  ]
})

const selectedSchedule = computed(() =>
  schedules.value.find((sch) => String(sch.id) === filterScheduleId.value)
)

const subjectOpts = computed(() => {
  const subs = selectedSchedule.value?.subjects || []
  if (!filterScheduleId.value) {
    return [{ value: '', label: 'Select a schedule first' }]
  }
  if (!subs.length) {
    return [{ value: '', label: 'No subjects on this schedule' }]
  }
  return [
    { value: '', label: 'Select subject' },
    ...subs.map((x) => ({ value: String(x.id), label: x.name }))
  ]
})

const canShowGrid = computed(
  () =>
    Boolean(
      filterYearId.value &&
        filterPeriodId.value &&
        filterClassId.value &&
        filterScheduleId.value &&
        filterSubjectId.value
    )
)

const headerTitle = computed(() => {
  const parts = [examTypeName.value, examDate.value, subjectName.value].filter(Boolean)
  return parts.length ? parts.join(' — ') : 'Marks'
})

function rowSavingKey(s) {
  return `${s.student_id}-${filterSubjectId.value}`
}

function formatTime(t) {
  if (t == null || t === '') return ''
  const s = String(t)
  return s.length >= 5 ? s.slice(0, 5) : s
}

function studentName(s) {
  return `${s.first_name || ''} ${s.last_name || ''}`.trim() || '—'
}

async function loadMeta() {
  loadingMeta.value = true
  try {
    const [fin, erp] = await Promise.all([financeApi.context(), erpApi.context()])
    years.value = fin.data?.data?.academicYears || []
    periods.value = fin.data?.data?.studyPeriods || []

    const activeYear = erp.data?.data?.academicYear
    const activePeriod = erp.data?.data?.studyPeriod

    if (activeYear?.id) filterYearId.value = String(activeYear.id)
    else if (years.value.length) {
      const active = years.value.find((y) => Number(y.is_active) === 1)
      filterYearId.value = String((active || years.value[0]).id)
    }

    if (activePeriod?.id) filterPeriodId.value = String(activePeriod.id)
    if (!filterPeriodId.value && filterYearId.value) {
      const y = Number(filterYearId.value)
      const forYear = periods.value.filter((p) => Number(p.academic_year_id) === y)
      const ap = forYear.find((p) => Number(p.is_active) === 1)
      filterPeriodId.value = ap ? String(ap.id) : forYear[0] ? String(forYear[0].id) : ''
    }
    if (filterYearId.value && filterPeriodId.value) {
      const ok = periods.value.some(
        (p) => String(p.id) === filterPeriodId.value && Number(p.academic_year_id) === Number(filterYearId.value)
      )
      if (!ok) {
        const y = Number(filterYearId.value)
        const forYear = periods.value.filter((p) => Number(p.academic_year_id) === y)
        const ap = forYear.find((p) => Number(p.is_active) === 1)
        filterPeriodId.value = ap ? String(ap.id) : forYear[0] ? String(forYear[0].id) : ''
      }
    }

    const clRes = await erpApi.classes()
    classes.value = clRes.data?.data || []
  } catch (e) {
    errorToast('Load failed', e?.response?.data?.error || e?.message)
  } finally {
    loadingMeta.value = false
  }
}

async function loadSchedules() {
  if (!filterYearId.value || !filterPeriodId.value) {
    schedules.value = []
    return
  }
  try {
    const res = await erpApi.examSchedules.list({
      academicYearId: filterYearId.value,
      studyPeriodId: filterPeriodId.value
    })
    schedules.value = res.data?.data || []
  } catch (e) {
    errorToast('Schedules', e?.response?.data?.error || e?.message)
    schedules.value = []
  }
}

function mapStudentRows(list) {
  return (list || []).map((r) => ({
    student_id: r.student_id,
    first_name: r.first_name,
    last_name: r.last_name,
    admission_number: r.admission_number,
    mark_id: r.mark_id,
    scoreInput: r.score != null && r.score !== '' ? String(r.score) : '',
    commentInput: r.teacher_comment != null ? String(r.teacher_comment) : ''
  }))
}

async function loadMarksGrid() {
  if (!canShowGrid.value) {
    students.value = []
    return
  }
  loadingGrid.value = true
  try {
    const res = await erpApi.examMarks.list({
      academicYearId: filterYearId.value,
      studyPeriodId: filterPeriodId.value,
      examScheduleId: filterScheduleId.value,
      subjectId: filterSubjectId.value
    })
    const d = res.data?.data || {}
    maxScore.value = d.max_score != null ? String(d.max_score) : '100'
    examTypeName.value = d.exam_type_name || ''
    examDate.value = d.exam_date || ''
    subjectName.value = d.subject_name || ''
    students.value = mapStudentRows(d.students)
  } catch (e) {
    errorToast('Could not load marks', e?.response?.data?.error || e?.message)
    students.value = []
  } finally {
    loadingGrid.value = false
  }
}

async function reloadMarks() {
  await loadSchedules()
  if (canShowGrid.value) await loadMarksGrid()
}

async function saveRow(s) {
  savingKey.value = rowSavingKey(s)
  try {
    const body = {
      examScheduleId: Number(filterScheduleId.value),
      subjectId: Number(filterSubjectId.value),
      studentId: s.student_id,
      score: s.scoreInput === '' ? null : Number(s.scoreInput),
      teacherComment: s.commentInput || ''
    }
    if (body.score !== null && (Number.isNaN(body.score) || body.score < 0)) {
      errorToast('Validation', 'Enter a valid score.')
      return
    }
    const mx = Number(maxScore.value)
    if (body.score !== null && body.score > mx + 0.0001) {
      errorToast('Validation', `Score cannot exceed ${maxScore.value}.`)
      return
    }
    const res = await erpApi.examMarks.saveRow(body)
    if (res.data?.data?.mark_id) {
      s.mark_id = res.data.data.mark_id
    }
    successToast('Saved', '')
  } catch (e) {
    errorToast('Save failed', e?.response?.data?.error || e?.message)
  } finally {
    savingKey.value = null
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
  filterScheduleId.value = ''
  filterSubjectId.value = ''
  subjectName.value = ''
  students.value = []
  if (!filterYearId.value || !filterPeriodId.value) {
    schedules.value = []
    return
  }
  const forYear = periods.value.filter((p) => Number(p.academic_year_id) === Number(filterYearId.value))
  if (!forYear.some((p) => String(p.id) === filterPeriodId.value)) {
    schedules.value = []
    return
  }
  await loadSchedules()
})

watch(filterClassId, () => {
  filterScheduleId.value = ''
  filterSubjectId.value = ''
  subjectName.value = ''
  students.value = []
})

watch(filterScheduleId, (id) => {
  filterSubjectId.value = ''
  subjectName.value = ''
  students.value = []
  if (!id) return
  const subs = selectedSchedule.value?.subjects || []
  if (subs.length === 1) {
    filterSubjectId.value = String(subs[0].id)
  }
})

watch(filterSubjectId, async (id) => {
  if (!id) {
    students.value = []
    subjectName.value = ''
    return
  }
  await loadMarksGrid()
})

onMounted(async () => {
  await loadMeta()
  await loadSchedules()
})
</script>
