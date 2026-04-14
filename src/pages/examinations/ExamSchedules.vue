<template>
  <div class="page-shell max-w-[1200px]">
    <PageHeader
      title="Exam schedules"
      description="Plan exams by type, class, and subjects linked to that class. Set date, time, room, supervisors, and optional requirements."
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
          <Button type="button" variant="secondary" @click="refreshAll">Refresh</Button>
          <Button type="button" :disabled="!canList" @click="openModal()">Add schedule</Button>
        </div>
      </div>

      <div v-if="loadingList" class="py-10 text-center text-slate-500">Loading schedules…</div>

      <div v-else class="overflow-hidden rounded-2xl border border-sc-line bg-white shadow-soft">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[800px] text-sm">
            <thead class="bg-slate-50 text-2xs uppercase text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Time</th>
                <th class="px-4 py-3 text-left">Exam type</th>
                <th class="px-4 py-3 text-left">Class</th>
                <th class="px-4 py-3 text-left">Subjects</th>
                <th class="px-4 py-3 text-left">Room</th>
                <th class="px-4 py-3 text-left">Supervisors</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.id" class="border-t border-sc-line">
                <td class="whitespace-nowrap px-4 py-3 font-medium">{{ row.exam_date }}</td>
                <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ formatTime(row.exam_time) }}</td>
                <td class="px-4 py-3">
                  <span class="font-medium">{{ row.exam_type_name }}</span>
                  <span class="block text-2xs text-slate-500">Max {{ row.max_score }}</span>
                </td>
                <td class="px-4 py-3">{{ row.level_name }} {{ row.class_name }}</td>
                <td class="max-w-[200px] px-4 py-3 text-slate-600">{{ row.subject_names || '—' }}</td>
                <td class="px-4 py-3 text-slate-600">{{ row.exam_room || '—' }}</td>
                <td class="max-w-[180px] px-4 py-3 text-slate-600">{{ row.supervisor_names || '—' }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right">
                  <Button variant="ghost" type="button" @click="openModal(row)">Edit</Button>
                  <Button variant="ghost" type="button" class="text-rose-700" @click="remove(row)">Delete</Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!rows.length && canList" class="py-10 text-center text-sm text-slate-400">No schedules for this year and period.</p>
        <p v-if="!canList" class="py-10 text-center text-sm text-slate-400">Select an academic year and study period.</p>
      </div>
    </template>

    <Modal v-model="modalOpen" :title="form.id ? 'Edit exam schedule' : 'New exam schedule'">
      <div class="max-h-[min(70vh,520px)] space-y-3 overflow-y-auto pr-1">
        <SelectInput v-model="form.examTypeId" label="Exam type" :options="examTypeOpts" placeholder="Select type" required />
        <SelectInput v-model="form.classId" label="Class" :options="classOpts" placeholder="Select class" required />
        <p class="text-xs font-semibold text-slate-600">Subjects (for this class)</p>
        <div
          v-if="!form.classId"
          class="rounded-xl border border-dashed border-sc-line bg-slate-50/80 px-3 py-4 text-center text-sm text-slate-500"
        >
          Select a class to load subjects.
        </div>
        <div v-else-if="loadingSubjects" class="py-4 text-center text-sm text-slate-500">Loading subjects…</div>
        <div
          v-else-if="!classSubjectList.length"
          class="rounded-xl border border-sc-line bg-amber-50/80 px-3 py-3 text-sm text-amber-900"
        >
          No subjects linked to this class. Use Academic setup to assign subjects.
        </div>
        <div v-else class="max-h-36 space-y-1 overflow-y-auto rounded-xl border border-sc-line p-2">
          <label v-for="s in classSubjectList" :key="s.id" class="flex items-center gap-2 text-sm">
            <input
              v-model="form.subjectIds"
              type="checkbox"
              :value="String(s.id)"
              class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
            />
            {{ s.name }}
          </label>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <FormInput v-model="form.examDate" label="Exam date" type="date" required />
          <FormInput v-model="form.examTime" label="Exam time" type="time" required />
        </div>
        <FormInput v-model="form.examRoom" label="Exam room (optional)" placeholder="e.g. Hall A, Lab 2" />
        <p class="text-xs font-semibold text-slate-600">Supervisors (optional)</p>
        <div class="max-h-32 space-y-1 overflow-y-auto rounded-xl border border-sc-line p-2">
          <label v-for="u in staffList" :key="u.id" class="flex items-center gap-2 text-sm">
            <input
              v-model="form.supervisorUserIds"
              type="checkbox"
              :value="String(u.id)"
              class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
            />
            {{ u.display_name }}
          </label>
          <p v-if="!staffList.length" class="py-2 text-center text-sm text-slate-400">No staff users in this school.</p>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700">Exam requirements (optional)</label>
          <textarea
            v-model="form.examRequirements"
            rows="3"
            class="block w-full rounded-xl border border-sc-line bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm transition-colors focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
            placeholder="Materials, dress code, arrival time…"
          />
        </div>
      </div>
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
const loadingSubjects = ref(false)
const saving = ref(false)
const years = ref([])
const periods = ref([])
const rows = ref([])
const examTypes = ref([])
const classes = ref([])
const staffList = ref([])
const classSubjectList = ref([])

const filterYearId = ref('')
const filterPeriodId = ref('')

const modalOpen = ref(false)
const form = reactive({
  id: null,
  examTypeId: '',
  classId: '',
  subjectIds: [],
  examDate: '',
  examTime: '',
  examRoom: '',
  supervisorUserIds: [],
  examRequirements: ''
})

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

const yearName = computed(() => years.value.find((y) => String(y.id) === filterYearId.value)?.name || '')
const periodName = computed(() => periods.value.find((p) => String(p.id) === filterPeriodId.value)?.name || '')

const examTypeOpts = computed(() => {
  const scope = yearName.value && periodName.value ? `${yearName.value} · ${periodName.value}` : ''
  return [
    { value: '', label: 'Select exam type' },
    ...examTypes.value.map((et) => ({
      value: String(et.id),
      label: scope ? `${et.name} — max ${et.max_score} (${scope})` : `${et.name} — max ${et.max_score}`
    }))
  ]
})

const classOpts = computed(() => [
  { value: '', label: 'Select class' },
  ...classes.value.map((c) => ({
    value: String(c.id),
    label: `${c.level_name} ${c.name}`
  }))
])

function formatTime(t) {
  if (t == null || t === '') return '—'
  const s = String(t)
  return s.length >= 5 ? s.slice(0, 5) : s
}

function timeForInput(t) {
  if (t == null || t === '') return ''
  const s = String(t)
  if (s.length >= 5) return s.slice(0, 5)
  return s
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

    const [clRes, staffRes] = await Promise.all([erpApi.classes(), erpApi.schoolUsers()])
    classes.value = clRes.data?.data || []
    staffList.value = staffRes.data?.data || []
  } catch (e) {
    errorToast('Load failed', e?.response?.data?.error || e?.message)
  } finally {
    loadingMeta.value = false
  }
}

async function loadExamTypes() {
  if (!canList.value) {
    examTypes.value = []
    return
  }
  try {
    const res = await erpApi.examTypes.list({
      academicYearId: filterYearId.value,
      studyPeriodId: filterPeriodId.value
    })
    examTypes.value = res.data?.data || []
  } catch (e) {
    errorToast('Exam types', e?.response?.data?.error || e?.message)
    examTypes.value = []
  }
}

async function loadList() {
  if (!canList.value) {
    rows.value = []
    return
  }
  loadingList.value = true
  try {
    const res = await erpApi.examSchedules.list({
      academicYearId: filterYearId.value,
      studyPeriodId: filterPeriodId.value
    })
    rows.value = res.data?.data || []
  } catch (e) {
    errorToast('Could not load schedules', e?.response?.data?.error || e?.message)
    rows.value = []
  } finally {
    loadingList.value = false
  }
}

async function refreshAll() {
  await loadExamTypes()
  await loadList()
}

async function loadSubjectsForClass(classId) {
  if (!classId) {
    classSubjectList.value = []
    return
  }
  loadingSubjects.value = true
  try {
    const res = await erpApi.classSubjects(classId)
    classSubjectList.value = res.data?.data || []
  } catch (e) {
    errorToast('Subjects', e?.response?.data?.error || e?.message)
    classSubjectList.value = []
  } finally {
    loadingSubjects.value = false
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
    examTypes.value = []
    return
  }
  const forYear = periods.value.filter((p) => Number(p.academic_year_id) === Number(filterYearId.value))
  if (!forYear.some((p) => String(p.id) === filterPeriodId.value)) {
    rows.value = []
    examTypes.value = []
    return
  }
  await loadExamTypes()
  await loadList()
})

watch(
  () => form.classId,
  async (cid) => {
    if (!modalOpen.value) return
    const allowed = new Set((classSubjectList.value || []).map((s) => String(s.id)))
    form.subjectIds = form.subjectIds.filter((id) => allowed.has(String(id)))
    await loadSubjectsForClass(cid)
    const nextAllowed = new Set(classSubjectList.value.map((s) => String(s.id)))
    form.subjectIds = form.subjectIds.filter((id) => nextAllowed.has(String(id)))
  }
)

function resetForm() {
  form.id = null
  form.examTypeId = ''
  form.classId = ''
  form.subjectIds = []
  form.examDate = ''
  form.examTime = ''
  form.examRoom = ''
  form.supervisorUserIds = []
  form.examRequirements = ''
  classSubjectList.value = []
}

async function openModal(row = null) {
  if (!filterYearId.value || !filterPeriodId.value) {
    errorToast('Select year and period', 'Choose an academic year and study period first.')
    return
  }
  if (!examTypes.value.length) {
    errorToast('No exam types', 'Create exam types for this year and period first.')
    return
  }
  resetForm()
  modalOpen.value = true
  if (row) {
    form.id = row.id
    form.examTypeId = String(row.exam_type_id)
    form.classId = String(row.class_id)
    form.examDate = row.exam_date || ''
    form.examTime = timeForInput(row.exam_time)
    form.examRoom = row.exam_room || ''
    form.examRequirements = row.exam_requirements || ''
    form.subjectIds = (row.subject_ids || []).map(String)
    form.supervisorUserIds = (row.supervisor_user_ids || []).map(String)
    await loadSubjectsForClass(form.classId)
    const allowed = new Set(classSubjectList.value.map((s) => String(s.id)))
    form.subjectIds = form.subjectIds.filter((id) => allowed.has(String(id)))
  }
}

async function save() {
  if (!form.examTypeId || !form.classId) {
    errorToast('Validation', 'Select exam type and class.')
    return
  }
  if (!form.subjectIds.length) {
    errorToast('Validation', 'Select at least one subject.')
    return
  }
  if (!form.examDate || !form.examTime) {
    errorToast('Validation', 'Enter exam date and time.')
    return
  }

  const body = {
    examTypeId: Number(form.examTypeId),
    classId: Number(form.classId),
    examDate: form.examDate,
    examTime: form.examTime,
    examRoom: form.examRoom.trim(),
    examRequirements: form.examRequirements.trim(),
    subjectIds: form.subjectIds.map((x) => Number(x)),
    supervisorUserIds: form.supervisorUserIds.map((x) => Number(x))
  }
  if (form.id) body.id = form.id

  saving.value = true
  try {
    if (form.id) await erpApi.examSchedules.update(body)
    else await erpApi.examSchedules.create(body)
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
  if (!confirm('Delete this exam schedule?')) return
  try {
    await erpApi.examSchedules.delete(row.id)
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
