<template>
  <div class="page-shell max-w-[900px] print:max-w-none">
    <PageHeader
      title="Score sheet"
      description="View all recorded marks for a student in the selected term, grouped by exam type, schedule, and subject."
    />

    <div v-if="loadingMeta" class="py-12 text-center text-slate-500">Loading…</div>

    <template v-else>
      <div class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft print:hidden">
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
          v-model="filterStudentId"
          label="Student"
          class="min-w-[240px] flex-1"
          :options="studentOpts"
          placeholder="Select student"
          required
        />
        <div class="flex flex-wrap gap-2 pb-0.5">
          <Button type="button" variant="secondary" @click="loadReport">Load</Button>
          <Button type="button" variant="secondary" @click="printReport">Print</Button>
        </div>
      </div>

      <div v-if="loadingReport" class="py-12 text-center text-slate-500 print:hidden">Loading report…</div>

      <div
        v-else-if="filterStudentId && !report?.enrolled"
        class="rounded-2xl border border-amber-200 bg-amber-50/90 p-6 text-sm text-amber-950 print:border print:bg-white"
      >
        This student is not enrolled in any class for the selected academic year and study period.
      </div>

      <div
        v-else-if="filterStudentId && report?.enrolled && !grouped.length"
        class="rounded-2xl border border-sc-line bg-white p-10 text-center text-sm text-slate-500 shadow-soft print:shadow-none"
      >
        No marks recorded yet for this student in the selected term. Use Marks entry to add scores.
      </div>

      <div
        v-else-if="grouped.length"
        id="score-sheet-print"
        class="space-y-6 rounded-2xl border border-sc-line bg-white p-6 shadow-soft print:space-y-4 print:border-0 print:p-0 print:shadow-none"
      >
        <header class="border-b border-sc-line pb-4 print:pb-3">
          <p class="text-lg font-bold text-slate-900">Examination score sheet</p>
          <p v-if="report?.student" class="mt-2 text-sm text-slate-700">
            <span class="font-semibold">{{ studentFullName }}</span>
            <span class="text-slate-500"> · {{ report.student.admission_number }}</span>
          </p>
          <p v-if="report?.student" class="mt-1 text-sm text-slate-600">
            {{ report.student.level_name }} {{ report.student.class_name }}
          </p>
          <p class="mt-2 text-xs text-slate-500">{{ termLabel }}</p>
        </header>

        <section v-for="block in grouped" :key="block.exam_type_id" class="rounded-xl border border-sc-line print:break-inside-avoid">
          <div class="border-b border-sc-line bg-slate-50 px-4 py-2.5 print:bg-white">
            <h2 class="text-sm font-bold text-slate-900">{{ block.exam_type_name }}</h2>
            <p class="text-2xs text-slate-500">Maximum score (exam type): {{ block.type_max_score }}</p>
          </div>

          <div v-for="sched in block.schedules" :key="sched.exam_schedule_id" class="border-t border-sc-line first:border-t-0">
            <div class="bg-slate-50/80 px-4 py-2 text-xs font-semibold text-slate-700 print:bg-white">
              Schedule · {{ sched.exam_date }}
              <span v-if="formatTime(sched.exam_time)" class="font-normal text-slate-500"> · {{ formatTime(sched.exam_time) }}</span>
              <span v-if="sched.exam_room" class="font-normal text-slate-500"> · {{ sched.exam_room }}</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-white text-2xs uppercase text-slate-500">
                  <tr>
                    <th class="px-4 py-2 text-left">Subject</th>
                    <th class="px-4 py-2 text-right">Score</th>
                    <th class="min-w-[160px] px-4 py-2 text-left">Comment</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="line in sched.lines" :key="line.subject_id" class="border-t border-sc-line">
                    <td class="px-4 py-2 font-medium text-slate-900">{{ line.subject_name }}</td>
                    <td class="px-4 py-2 text-right tabular-nums text-slate-800">{{ line.score ?? '—' }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ line.teacher_comment || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>

      <p v-else class="rounded-2xl border border-dashed border-sc-line bg-slate-50/80 p-8 text-center text-sm text-slate-500 print:hidden">
        Choose academic year, study period, and student, then click Load.
      </p>
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

const { errorToast } = useNotificationStore()

const loadingMeta = ref(true)
const loadingReport = ref(false)
const years = ref([])
const periods = ref([])
const enrolledStudents = ref([])
const report = ref(null)
const rawRows = ref([])

const filterYearId = ref('')
const filterPeriodId = ref('')
const filterStudentId = ref('')

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

const studentOpts = computed(() => [{ value: '', label: 'Select student' }, ...enrolledStudents.value.map((s) => ({
  value: String(s.id),
  label: `${s.last_name}, ${s.first_name} (${s.admission_number})`
}))])

const studentFullName = computed(() => {
  const st = report.value?.student
  if (!st) return ''
  return `${st.first_name || ''} ${st.last_name || ''}`.trim()
})

const termLabel = computed(() => {
  const yn = years.value.find((y) => String(y.id) === filterYearId.value)?.name || ''
  const pn = periods.value.find((p) => String(p.id) === filterPeriodId.value)?.name || ''
  return yn && pn ? `${yn} · ${pn}` : ''
})

/** Group flat rows: exam type → schedules → subject lines */
const grouped = computed(() => {
  const rows = rawRows.value || []
  const byType = new Map()
  for (const r of rows) {
    const tid = r.exam_type_id
    if (!byType.has(tid)) {
      byType.set(tid, {
        exam_type_id: tid,
        exam_type_name: r.exam_type_name,
        type_max_score: r.type_max_score,
        schedules: new Map()
      })
    }
    const t = byType.get(tid)
    const sid = r.exam_schedule_id
    if (!t.schedules.has(sid)) {
      t.schedules.set(sid, {
        exam_schedule_id: sid,
        exam_date: r.exam_date,
        exam_time: r.exam_time,
        exam_room: r.exam_room || '',
        lines: []
      })
    }
    t.schedules.get(sid).lines.push({
      subject_id: r.subject_id,
      subject_name: r.subject_name,
      score: r.score,
      teacher_comment: r.teacher_comment
    })
  }
  return Array.from(byType.values()).map((t) => ({
    exam_type_id: t.exam_type_id,
    exam_type_name: t.exam_type_name,
    type_max_score: t.type_max_score,
    schedules: Array.from(t.schedules.values())
  }))
})

function formatTime(t) {
  if (t == null || t === '') return ''
  const s = String(t)
  return s.length >= 5 ? s.slice(0, 5) : s
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
  } catch (e) {
    errorToast('Load failed', e?.response?.data?.error || e?.message)
  } finally {
    loadingMeta.value = false
  }
}

async function loadEnrolledStudents() {
  if (!filterYearId.value || !filterPeriodId.value) {
    enrolledStudents.value = []
    return
  }
  try {
    const res = await financeApi.enrolledStudents({
      academicYearId: filterYearId.value,
      studyPeriodId: filterPeriodId.value
    })
    enrolledStudents.value = res.data?.data || []
  } catch (e) {
    errorToast('Students list', e?.response?.data?.error || e?.message)
    enrolledStudents.value = []
  }
}

async function loadReport() {
  if (!filterYearId.value || !filterPeriodId.value || !filterStudentId.value) {
    report.value = null
    rawRows.value = []
    return
  }
  loadingReport.value = true
  try {
    const res = await erpApi.scoreSheet({
      studentId: filterStudentId.value,
      academicYearId: filterYearId.value,
      studyPeriodId: filterPeriodId.value
    })
    const d = res.data?.data || {}
    report.value = d
    rawRows.value = d.rows || []
  } catch (e) {
    errorToast('Report failed', e?.response?.data?.error || e?.message)
    report.value = null
    rawRows.value = []
  } finally {
    loadingReport.value = false
  }
}

function printReport() {
  window.print()
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
  filterStudentId.value = ''
  report.value = null
  rawRows.value = []
  if (!filterYearId.value || !filterPeriodId.value) {
    enrolledStudents.value = []
    return
  }
  const forYear = periods.value.filter((p) => Number(p.academic_year_id) === Number(filterYearId.value))
  if (!forYear.some((p) => String(p.id) === filterPeriodId.value)) {
    enrolledStudents.value = []
    return
  }
  await loadEnrolledStudents()
})

watch(filterStudentId, async (id) => {
  report.value = null
  rawRows.value = []
  if (id && filterYearId.value && filterPeriodId.value) {
    await loadReport()
  }
})

onMounted(async () => {
  await loadMeta()
  await loadEnrolledStudents()
})
</script>

<style scoped>
@media print {
  .page-shell {
    padding: 0;
  }
}
</style>
