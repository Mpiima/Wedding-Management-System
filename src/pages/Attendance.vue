<template>
  <div class="page-shell max-w-[1400px] pb-28">
    <PageHeader title="Attendance" description="Fast attendance checklist for class teachers and administrators." />

    <div class="mb-4 grid grid-cols-2 gap-2 md:flex md:flex-wrap">
      <Button type="button" :variant="tab === 'mark' ? 'primary' : 'secondary'" class="w-full md:w-auto" @click="tab = 'mark'">Mark attendance</Button>
      <Button type="button" :variant="tab === 'history' ? 'primary' : 'secondary'" class="w-full md:w-auto" @click="tab = 'history'">History</Button>
      <Button type="button" :variant="tab === 'profile' ? 'primary' : 'secondary'" class="w-full md:w-auto" @click="tab = 'profile'">Student profile</Button>
      <Button type="button" :variant="tab === 'insights' ? 'primary' : 'secondary'" class="w-full md:w-auto" @click="tab = 'insights'">Dashboard & alerts</Button>
    </div>

    <div v-if="loadingMeta" class="py-12 text-center text-slate-500">Loading context...</div>
    <template v-else>
      <div class="mb-4 grid grid-cols-1 gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft md:grid-cols-2">
        <SelectInput v-model="filterYearId" label="Academic year" :options="yearOptions" />
        <SelectInput v-model="filterPeriodId" label="Study period" :options="periodOptions" />
      </div>

      <section v-if="tab === 'mark'" class="space-y-4">
        <div class="grid grid-cols-1 gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft lg:grid-cols-5">
          <FormInput v-model="markDate" type="date" label="Date" />
          <SelectInput v-model="markClassId" label="Class" :options="classOptions" />
          <SelectInput v-model="markStreamId" label="Stream (optional)" :options="markStreamOptions" />
          <div class="flex items-end gap-2 lg:col-span-2">
            <Button type="button" variant="secondary" @click="setAllStatus('present')">Mark all present</Button>
            <Button type="button" variant="secondary" @click="setAllStatus('absent')">Mark all absent</Button>
            <Button type="button" variant="secondary" @click="loadSession">Reload</Button>
          </div>
        </div>

        <div v-if="loadingSession" class="py-12 text-center text-slate-500">Loading class register...</div>
        <div v-else class="overflow-hidden rounded-2xl border border-sc-line bg-white shadow-soft">
          <div class="overflow-x-auto hidden md:block">
            <table class="w-full min-w-[760px] text-sm">
              <thead class="bg-slate-50 text-2xs uppercase text-slate-500">
                <tr>
                  <th class="px-4 py-3 text-left">Student name</th>
                  <th class="px-4 py-3 text-left">Admission #</th>
                  <th class="px-4 py-3 text-left">Status</th>
                  <th class="px-4 py-3 text-left">Remarks</th>
                  <th class="px-4 py-3 text-left">Audit</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in rows" :key="row.student_id" class="border-t border-sc-line hover:bg-slate-50/70" :class="statusTint(row.status)">
                  <td class="px-4 py-3 font-medium">{{ row.student_name }}</td>
                  <td class="px-4 py-3 tabular-nums text-slate-600">{{ row.admission_number || '-' }}</td>
                  <td class="px-4 py-3">
                    <div class="inline-flex gap-1 rounded-lg border border-sc-line bg-white p-1">
                      <button type="button" class="rounded-md px-2 py-1 text-2xs font-semibold" :class="statusBtnClass(row.status, 'present')" @click="setStatus(row, 'present')">Present</button>
                      <button type="button" class="rounded-md px-2 py-1 text-2xs font-semibold" :class="statusBtnClass(row.status, 'absent')" @click="setStatus(row, 'absent')">Absent</button>
                      <button type="button" class="rounded-md px-2 py-1 text-2xs font-semibold" :class="statusBtnClass(row.status, 'late')" @click="setStatus(row, 'late')">Late</button>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <input
                      v-model="row.remarks"
                      type="text"
                      class="w-full rounded-lg border border-sc-line px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none"
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
          <div class="space-y-3 p-3 md:hidden">
            <article v-for="row in rows" :key="`m-${row.student_id}`" class="rounded-xl border border-sc-line p-3" :class="statusTint(row.status)">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="text-sm font-semibold text-slate-900">{{ row.student_name }}</p>
                  <p class="text-2xs text-slate-500">Adm: {{ row.admission_number || '-' }}</p>
                </div>
                <span class="rounded-full px-2 py-1 text-2xs font-semibold" :class="statusBadgeClass(row.status)">{{ row.status }}</span>
              </div>
              <div class="mt-2 grid grid-cols-3 gap-1">
                <button type="button" class="rounded-md px-2 py-1 text-2xs font-semibold" :class="statusBtnClass(row.status, 'present')" @click="setStatus(row, 'present')">Present</button>
                <button type="button" class="rounded-md px-2 py-1 text-2xs font-semibold" :class="statusBtnClass(row.status, 'absent')" @click="setStatus(row, 'absent')">Absent</button>
                <button type="button" class="rounded-md px-2 py-1 text-2xs font-semibold" :class="statusBtnClass(row.status, 'late')" @click="setStatus(row, 'late')">Late</button>
              </div>
              <input
                v-model="row.remarks"
                type="text"
                class="mt-2 w-full rounded-lg border border-sc-line px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none"
                placeholder="Optional remark"
                @input="dirty = true"
              />
              <p class="mt-1 text-2xs text-slate-500">{{ row.updated_at ? prettyDateTime(row.updated_at) : 'Not saved' }}</p>
            </article>
          </div>
          <p v-if="!rows.length" class="py-10 text-center text-slate-500">No enrolled students for selected filters.</p>
        </div>
      </section>

      <section v-else-if="tab === 'history'" class="space-y-4">
        <div class="grid grid-cols-1 gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft lg:grid-cols-5">
          <SelectInput v-model="histClassId" label="Class" :options="classOptions" />
          <SelectInput v-model="histStreamId" label="Stream (optional)" :options="historyStreamOptions" />
          <FormInput v-model="histFrom" type="date" label="From date" />
          <FormInput v-model="histTo" type="date" label="To date" />
          <div class="flex items-end"><Button type="button" @click="loadHistory">Load history</Button></div>
        </div>
        <div class="overflow-hidden rounded-2xl border border-sc-line bg-white shadow-soft">
          <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
              <thead class="bg-slate-50 text-2xs uppercase text-slate-500">
                <tr>
                  <th class="px-4 py-3 text-left">Date</th>
                  <th class="px-4 py-3 text-right">Present</th>
                  <th class="px-4 py-3 text-right">Absent</th>
                  <th class="px-4 py-3 text-right">Late</th>
                  <th class="px-4 py-3 text-right">Total enrolled</th>
                  <th class="px-4 py-3 text-right">Attendance rate</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in historyRows" :key="r.date" class="border-t border-sc-line">
                  <td class="px-4 py-3 font-medium">{{ r.date }}</td>
                  <td class="px-4 py-3 text-right text-emerald-700">{{ r.present_count }}</td>
                  <td class="px-4 py-3 text-right text-rose-700">{{ r.absent_count }}</td>
                  <td class="px-4 py-3 text-right text-amber-700">{{ r.late_count }}</td>
                  <td class="px-4 py-3 text-right">{{ r.total_enrolled }}</td>
                  <td class="px-4 py-3 text-right font-semibold">{{ r.attendance_rate_pct }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="rounded-2xl border border-sc-line bg-white p-4 shadow-soft">
          <div class="mb-3 flex items-center justify-between gap-2">
            <Button type="button" variant="secondary" @click="shiftCalendarMonth(-1)">Prev</Button>
            <p class="text-sm font-semibold text-slate-800">{{ calendarTitle }}</p>
            <Button type="button" variant="secondary" @click="shiftCalendarMonth(1)">Next</Button>
          </div>
          <div class="mb-2 grid grid-cols-7 gap-1 text-center text-2xs font-semibold uppercase tracking-wide text-slate-500">
            <span v-for="d in weekDays" :key="d">{{ d }}</span>
          </div>
          <div class="grid grid-cols-7 gap-1">
            <button
              v-for="cell in calendarCells"
              :key="cell.key"
              type="button"
              class="aspect-square rounded-lg border text-xs font-medium transition"
              :class="calendarCellClass(cell)"
              @click="pickCalendarDate(cell)"
            >
              <span>{{ cell.day }}</span>
            </button>
          </div>
          <div class="mt-3 flex flex-wrap gap-3 text-2xs text-slate-600">
            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500" /> good (>=90%)</span>
            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-500" /> fair (75-89%)</span>
            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-rose-500" /> low (&lt;75%)</span>
          </div>
        </div>
      </section>

      <section v-else-if="tab === 'profile'" class="space-y-4">
        <div class="grid grid-cols-1 gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft lg:grid-cols-4">
          <SelectInput v-model="profileStudentId" label="Student" :options="studentOptions" />
          <FormInput v-model="profileFrom" type="date" label="From date (optional)" />
          <FormInput v-model="profileTo" type="date" label="To date (optional)" />
          <div class="flex items-end"><Button type="button" @click="loadProfile">Load profile</Button></div>
        </div>
        <div v-if="studentProfile" class="rounded-2xl border border-sc-line bg-white p-6 shadow-soft">
          <h3 class="text-lg font-semibold text-slate-900">{{ studentTitle }}</h3>
          <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-lg border border-sc-line p-3"><p class="text-xs text-slate-500">Present</p><p class="text-xl font-bold text-emerald-700">{{ studentProfile.counts.present }}</p></div>
            <div class="rounded-lg border border-sc-line p-3"><p class="text-xs text-slate-500">Absent</p><p class="text-xl font-bold text-rose-700">{{ studentProfile.counts.absent }}</p></div>
            <div class="rounded-lg border border-sc-line p-3"><p class="text-xs text-slate-500">Late</p><p class="text-xl font-bold text-amber-700">{{ studentProfile.counts.late }}</p></div>
            <div class="rounded-lg border border-sc-line p-3"><p class="text-xs text-slate-500">Attendance %</p><p class="text-xl font-bold text-slate-900">{{ studentProfile.attendance_percentage ?? 0 }}%</p></div>
          </div>
          <div class="mt-4 h-3 rounded-full bg-slate-200">
            <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${Math.min(100, studentProfile.attendance_percentage || 0)}%` }" />
          </div>
        </div>
      </section>

      <section v-else class="space-y-4">
        <div class="grid grid-cols-1 gap-3 rounded-2xl border border-sc-line bg-white p-4 shadow-soft md:grid-cols-3">
          <FormInput v-model="dashDate" type="date" label="Date" />
          <div class="flex items-end"><Button type="button" @click="loadDashboard">Refresh dashboard</Button></div>
          <div class="flex items-end"><Button type="button" variant="secondary" @click="loadInsights">Refresh alerts</Button></div>
        </div>
        <div v-if="dashboard" class="grid grid-cols-2 gap-3 md:grid-cols-4">
          <div class="rounded-xl border border-sc-line bg-white p-4"><p class="text-xs text-slate-500">Total records</p><p class="text-2xl font-bold">{{ dashboard.total_records }}</p></div>
          <div class="rounded-xl border border-sc-line bg-white p-4"><p class="text-xs text-slate-500">Present</p><p class="text-2xl font-bold text-emerald-700">{{ dashboard.present }}</p></div>
          <div class="rounded-xl border border-sc-line bg-white p-4"><p class="text-xs text-slate-500">Absent</p><p class="text-2xl font-bold text-rose-700">{{ dashboard.absent }}</p></div>
          <div class="rounded-xl border border-sc-line bg-white p-4"><p class="text-xs text-slate-500">Rate</p><p class="text-2xl font-bold">{{ dashboard.attendance_rate_pct ?? 0 }}%</p></div>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div class="rounded-xl border border-sc-line bg-white p-4">
            <p class="text-sm font-semibold">Frequent absenteeism</p>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
              <li v-for="x in insights.frequent_absences" :key="`f-${x.student_id}`">{{ x.first_name }} {{ x.last_name }} - {{ x.absent_days }} days absent</li>
              <li v-if="!insights.frequent_absences.length" class="text-slate-400">No alerts.</li>
            </ul>
          </div>
          <div class="rounded-xl border border-sc-line bg-white p-4">
            <p class="text-sm font-semibold">Consecutive absences / trends</p>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
              <li v-for="x in insights.consecutive_absences" :key="`c-${x.student_id}`">Student #{{ x.student_id }} - {{ x.consecutive_absent_days }} consecutive days</li>
              <li v-for="x in insights.patterns" :key="`p-${x.student_id}`">Student #{{ x.student_id }} - {{ x.pattern }}</li>
              <li v-if="!insights.consecutive_absences.length && !insights.patterns.length" class="text-slate-400">No alerts.</li>
            </ul>
          </div>
        </div>
      </section>
    </template>

    <div v-if="tab === 'mark' && rows.length" class="fixed bottom-0 left-0 right-0 z-20 border-t border-sc-line bg-white/95 px-4 py-3 backdrop-blur">
      <div class="mx-auto flex max-w-[1400px] items-center justify-between">
        <p class="text-sm" :class="dirty ? 'text-amber-700' : 'text-slate-500'">{{ dirty ? 'Unsaved changes' : 'Saved' }}</p>
        <Button type="button" :disabled="saving || !dirty" @click="saveSession">{{ saving ? 'Saving...' : 'Save attendance' }}</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import useNotificationStore from '@/stores/notificationStore'
import { erpApi } from '@/services/erpSetupApi'
import { financeApi } from '@/services/financeApi'

const { successToast, errorToast } = useNotificationStore()

const tab = ref('mark')
const loadingMeta = ref(true)
const filterYearId = ref('')
const filterPeriodId = ref('')
const markDate = ref(today())
const markClassId = ref('')
const markStreamId = ref('')
const rows = ref([])
const loadingSession = ref(false)
const dirty = ref(false)
const saving = ref(false)
const historyRows = ref([])
const histClassId = ref('')
const histStreamId = ref('')
const histFrom = ref(daysAgo(30))
const histTo = ref(today())
const calendarMonth = ref(startOfMonth(today()))
const profileStudentId = ref('')
const profileFrom = ref('')
const profileTo = ref('')
const studentProfile = ref(null)
const dashDate = ref(today())
const dashboard = ref(null)
const insights = reactive({ frequent_absences: [], consecutive_absences: [], patterns: [] })
const classList = ref([])
const streamList = ref([])
const enrolledStudents = ref([])
const contextYears = ref([])
const periods = ref([])
const classStreamIdsMark = ref([])
const classStreamIdsHistory = ref([])

const yearOptions = computed(() => [{ value: '', label: 'Select year' }, ...contextYears.value.map((y) => ({ value: String(y.id), label: y.name }))])
const periodOptions = computed(() => {
  if (!filterYearId.value) return [{ value: '', label: 'Select period' }]
  const list = periods.value.filter((p) => Number(p.academic_year_id) === Number(filterYearId.value))
  return [{ value: '', label: list.length ? 'Select period' : 'No periods for this year' }, ...list.map((p) => ({ value: String(p.id), label: p.name }))]
})
const classOptions = computed(() => [{ value: '', label: 'Select class' }, ...classList.value.map((c) => ({ value: String(c.id), label: `${c.level_name || ''} ${c.name}`.trim() }))])
const markStreamOptions = computed(() => [{ value: '', label: 'All streams' }, ...streamList.value.filter((s) => classStreamIdsMark.value.includes(Number(s.id))).map((s) => ({ value: String(s.id), label: s.name }))])
const historyStreamOptions = computed(() => [{ value: '', label: 'All streams' }, ...streamList.value.filter((s) => classStreamIdsHistory.value.includes(Number(s.id))).map((s) => ({ value: String(s.id), label: s.name }))])
const studentOptions = computed(() => [{ value: '', label: 'Select student' }, ...enrolledStudents.value.map((s) => ({ value: String(s.id), label: `${s.first_name} ${s.last_name} (${s.admission_number || '-'})` }))])
const studentTitle = computed(() => {
  const s = studentProfile.value?.student
  return s ? `${s.first_name} ${s.last_name} (${s.admission_number || '-'})` : ''
})
const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const historyByDate = computed(() => {
  const map = {}
  historyRows.value.forEach((r) => {
    map[r.date] = r
  })
  return map
})
const calendarTitle = computed(() => {
  const [y, m] = calendarMonth.value.split('-').map(Number)
  const dt = new Date(y, m - 1, 1)
  return dt.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
})
const calendarCells = computed(() => {
  const [y, m] = calendarMonth.value.split('-').map(Number)
  const daysInMonth = new Date(y, m, 0).getDate()
  const first = new Date(y, m - 1, 1)
  const jsDay = first.getDay()
  const mondayOffset = (jsDay + 6) % 7
  const cells = []
  for (let i = 0; i < mondayOffset; i += 1) {
    cells.push({ key: `e-${i}`, empty: true, day: '', date: '' })
  }
  for (let d = 1; d <= daysInMonth; d += 1) {
    const date = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    const row = historyByDate.value[date]
    cells.push({
      key: `d-${date}`,
      empty: false,
      day: d,
      date,
      rate: row?.attendance_rate_pct ?? null
    })
  }
  return cells
})

function today() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
function daysAgo(n) {
  const d = new Date()
  d.setDate(d.getDate() - n)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
function startOfMonth(dateStr) {
  const [y, m] = dateStr.split('-')
  return `${y}-${m}`
}
function prettyDateTime(value) {
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleString()
}
function statusBadgeClass(status) {
  if (status === 'present') return 'bg-emerald-100 text-emerald-700'
  if (status === 'absent') return 'bg-rose-100 text-rose-700'
  if (status === 'late') return 'bg-amber-100 text-amber-700'
  return 'bg-slate-100 text-slate-700'
}
function statusTint(status) {
  if (status === 'present') return 'bg-emerald-50/40'
  if (status === 'absent') return 'bg-rose-50/40'
  if (status === 'late') return 'bg-amber-50/40'
  return ''
}
function statusBtnClass(current, value) {
  if (current !== value) return 'text-slate-500 hover:bg-slate-100'
  if (value === 'present') return 'bg-emerald-600 text-white'
  if (value === 'absent') return 'bg-rose-600 text-white'
  return 'bg-amber-500 text-white'
}
function setStatus(row, status) {
  row.status = status
  dirty.value = true
}
function setAllStatus(status) {
  rows.value.forEach((r) => { r.status = status })
  dirty.value = true
}
function shiftCalendarMonth(delta) {
  const [y, m] = calendarMonth.value.split('-').map(Number)
  const next = new Date(y, m - 1 + delta, 1)
  calendarMonth.value = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`
}
function pickCalendarDate(cell) {
  if (cell.empty || !cell.date) return
  histFrom.value = cell.date
  histTo.value = cell.date
}
function calendarCellClass(cell) {
  if (cell.empty) return 'border-transparent bg-transparent'
  if (cell.rate == null) return 'border-sc-line bg-slate-50 text-slate-400'
  if (cell.rate >= 90) return 'border-emerald-200 bg-emerald-100/70 text-emerald-800 hover:bg-emerald-200/70'
  if (cell.rate >= 75) return 'border-amber-200 bg-amber-100/70 text-amber-800 hover:bg-amber-200/70'
  return 'border-rose-200 bg-rose-100/70 text-rose-800 hover:bg-rose-200/70'
}

async function loadMeta() {
  loadingMeta.value = true
  try {
    const [finRes, erpCtxRes, classesRes, streamsRes] = await Promise.all([
      financeApi.context(),
      erpApi.context(),
      erpApi.classes(),
      erpApi.streams()
    ])
    const finCtx = finRes?.data?.data || finRes?.data || {}
    const erpCtx = erpCtxRes?.data?.data || erpCtxRes?.data || {}
    contextYears.value = finCtx.academicYears || []
    periods.value = finCtx.studyPeriods || []
    classList.value = classesRes?.data?.data || []
    streamList.value = streamsRes?.data?.data || []
    const activeYear = erpCtx.academicYear || contextYears.value.find((y) => Number(y.is_active) === 1) || contextYears.value[0]
    if (activeYear) {
      filterYearId.value = String(activeYear.id)
      const forYear = periods.value.filter((p) => Number(p.academic_year_id) === Number(activeYear.id))
      const activePeriod = erpCtx.studyPeriod || forYear.find((p) => Number(p.is_active) === 1) || forYear[0]
      if (activePeriod) filterPeriodId.value = String(activePeriod.id)
    }
    await loadStudents()
  } catch (e) {
    errorToast('Error', e?.message || 'Failed to load attendance context')
  } finally {
    loadingMeta.value = false
  }
}

async function loadClassStreamsForMark() {
  if (!markClassId.value) {
    classStreamIdsMark.value = []
    return
  }
  const r = await erpApi.classStreamIds(markClassId.value)
  classStreamIdsMark.value = r?.data?.data || []
}
async function loadClassStreamsForHistory() {
  if (!histClassId.value) {
    classStreamIdsHistory.value = []
    return
  }
  const r = await erpApi.classStreamIds(histClassId.value)
  classStreamIdsHistory.value = r?.data?.data || []
}

async function loadStudents() {
  if (!filterYearId.value || !filterPeriodId.value) {
    enrolledStudents.value = []
    return
  }
  const res = await financeApi.enrolledStudents({ academicYearId: filterYearId.value, studyPeriodId: filterPeriodId.value })
  enrolledStudents.value = res?.data?.data || []
}

async function loadSession() {
  if (!filterYearId.value || !filterPeriodId.value || !markClassId.value) return
  loadingSession.value = true
  try {
    const params = {
      academicYearId: filterYearId.value,
      studyPeriodId: filterPeriodId.value,
      classId: markClassId.value,
      date: markDate.value
    }
    if (markStreamId.value) params.streamId = markStreamId.value
    const res = await erpApi.attendance.session(params)
    const students = res?.data?.data?.students || []
    rows.value = students.map((s) => ({
      student_id: s.student_id,
      student_name: `${s.first_name} ${s.last_name}`.trim(),
      admission_number: s.admission_number,
      status: s.status || 'present',
      remarks: s.remarks || '',
      updated_at: s.updated_at,
      last_saved_by: s.last_saved_by
    }))
    dirty.value = false
  } catch (e) {
    errorToast('Error', e?.message || 'Failed to load attendance session')
  } finally {
    loadingSession.value = false
  }
}

async function saveSession() {
  if (!rows.value.length || !dirty.value) return
  saving.value = true
  try {
    const body = {
      action: 'save',
      academicYearId: Number(filterYearId.value),
      studyPeriodId: Number(filterPeriodId.value),
      classId: Number(markClassId.value),
      date: markDate.value,
      records: rows.value.map((r) => ({ studentId: r.student_id, status: r.status, remarks: r.remarks || '' }))
    }
    if (markStreamId.value) body.streamId = Number(markStreamId.value)
    await erpApi.attendance.save(body)
    successToast('Saved', 'Attendance saved successfully')
    await loadSession()
  } catch (e) {
    errorToast('Error', e?.message || 'Failed to save attendance')
  } finally {
    saving.value = false
  }
}

async function loadHistory() {
  if (!filterYearId.value || !filterPeriodId.value || !histClassId.value) return
  const params = {
    academicYearId: filterYearId.value,
    studyPeriodId: filterPeriodId.value,
    classId: histClassId.value,
    dateFrom: histFrom.value,
    dateTo: histTo.value
  }
  if (histStreamId.value) params.streamId = histStreamId.value
  const res = await erpApi.attendance.history(params)
  historyRows.value = res?.data?.data?.rows || []
  if (historyRows.value.length) {
    calendarMonth.value = startOfMonth(historyRows.value[0].date)
  }
}

async function loadProfile() {
  if (!filterYearId.value || !filterPeriodId.value || !profileStudentId.value) return
  const params = {
    academicYearId: filterYearId.value,
    studyPeriodId: filterPeriodId.value,
    studentId: profileStudentId.value
  }
  if (profileFrom.value) params.dateFrom = profileFrom.value
  if (profileTo.value) params.dateTo = profileTo.value
  const res = await erpApi.attendance.studentProfile(params)
  studentProfile.value = res?.data?.data || null
}

async function loadDashboard() {
  if (!filterYearId.value || !filterPeriodId.value) return
  const res = await erpApi.attendance.dashboard({ academicYearId: filterYearId.value, studyPeriodId: filterPeriodId.value, date: dashDate.value })
  dashboard.value = res?.data?.data || null
}

async function loadInsights() {
  if (!filterYearId.value || !filterPeriodId.value) return
  const res = await erpApi.attendance.insights({ academicYearId: filterYearId.value, studyPeriodId: filterPeriodId.value, lookbackDays: 28 })
  const data = res?.data?.data || {}
  insights.frequent_absences = data.frequent_absences || []
  insights.consecutive_absences = data.consecutive_absences || []
  insights.patterns = data.patterns || []
}

watch(markClassId, async () => {
  markStreamId.value = ''
  await loadClassStreamsForMark()
  await loadSession()
})
watch(markStreamId, loadSession)
watch(markDate, loadSession)
watch([filterYearId, filterPeriodId], async () => {
  await loadStudents()
  if (markClassId.value) await loadSession()
  if (tab.value === 'insights') {
    await loadDashboard()
    await loadInsights()
  }
})
watch(histClassId, async () => {
  histStreamId.value = ''
  await loadClassStreamsForHistory()
})
watch(tab, async (v) => {
  if (v === 'insights') {
    await loadDashboard()
    await loadInsights()
  }
})

onMounted(loadMeta)
</script>
