<template>
  <div class="page-shell max-w-[1680px]">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <PageHeader
        title="Applicants"
        description="Applications not yet enrolled — review, accept, and enroll with optional payment."
      />
      <div class="flex flex-wrap items-center gap-2">
        <router-link
          to="/students/admissions/board"
          class="inline-flex h-10 items-center rounded-xl border border-slate-200/90 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
        >
          Kanban board
        </router-link>
        <Button type="button" class="gap-2 shadow-sm" @click="showCreate = true">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add applicant
        </Button>
        <Button type="button" variant="secondary" :disabled="loading" @click="load">Refresh</Button>
      </div>
    </div>

    <!-- Dashboard indicators -->
    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-6">
      <div
        v-for="s in statCards"
        :key="s.key"
        class="rounded-2xl border border-sc-line bg-white px-4 py-3 shadow-soft"
      >
        <p class="text-2xs font-semibold uppercase tracking-wide text-slate-500">{{ s.label }}</p>
        <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ stats[s.key] ?? 0 }}</p>
      </div>
    </div>

    <DataTable
      v-model:current-page="tablePage"
      v-model:selected-ids="selectedIds"
      title="Applicant list"
      class="mt-8"
      sticky-header
      comfortable
      :columns="columns"
      :data="tableRows"
      row-key="id"
      searchable
      search-placeholder="Search by name or phone…"
      :search-keys="['fullName', 'parent_phone', 'parent_name']"
      :filters="tableFilters"
      :pagination="true"
      :page-size="15"
      :selectable="true"
      sortable
      empty-text="No applicants match your filters."
    >
      <template #toolbar>
        <Button
          type="button"
          variant="secondary"
          :disabled="!selectedIds.length || bulkWorking"
          @click="bulkAccept"
        >
          Accept selected
        </Button>
        <Button
          type="button"
          variant="secondary"
          :disabled="!selectedIds.length || bulkWorking"
          @click="openBulkReject"
        >
          Reject selected
        </Button>
        <Button type="button" variant="ghost" :disabled="!tableRows.length" @click="exportCsv">Export CSV</Button>
      </template>

      <template #cell-fullName="{ row }">
        <div class="flex max-w-[min(100%,18rem)] items-start gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-100 to-slate-200/70 text-slate-500 ring-1 ring-slate-200/80"
          >
            <UserIcon class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="truncate font-semibold text-slate-900">{{ row.fullName }}</p>
            <p class="mt-0.5 truncate text-xs text-slate-500">{{ row.parent_phone || 'No phone' }}</p>
          </div>
        </div>
      </template>

      <template #cell-statusLabel="{ row }">
        <span
          class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-2xs font-semibold uppercase tracking-wide ring-1"
          :class="statusBadgeClass(row.pipeline_status)"
        >
          <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-70" aria-hidden="true" />
          {{ row.statusLabel }}
        </span>
      </template>

      <template #cell-docsLabel="{ row }">
        <div
          v-if="row.doc_count > 0"
          class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-2xs font-medium text-emerald-800 ring-1 ring-emerald-200/70"
          :title="`${row.doc_count} file(s) uploaded`"
        >
          <CheckCircleIcon class="h-3.5 w-3.5 shrink-0 text-emerald-600" />
          <span>{{ row.doc_count }} file{{ row.doc_count === 1 ? '' : 's' }}</span>
        </div>
        <div
          v-else
          class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-2xs font-medium text-rose-800 ring-1 ring-rose-200/60"
          title="No documents uploaded"
        >
          <ExclamationTriangleIcon class="h-3.5 w-3.5 shrink-0 text-rose-600" />
          Missing
        </div>
      </template>

      <template #actions="{ row }">
        <RowActionsMenu v-slot="{ close }">
          <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
            @click="openDetail(row); close()"
          >
            View details
          </button>
          <button
            v-if="row.pipeline_status === 'application' || row.pipeline_status === 'review' || row.pipeline_status === 'waitlist'"
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-emerald-700 transition hover:bg-emerald-50/80"
            @click="acceptOne(row); close()"
          >
            Accept applicant
          </button>
          <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-rose-700 transition hover:bg-rose-50/80"
            @click="openReject(row.id); close()"
          >
            Reject
          </button>
          <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
            @click="openNote(row); close()"
          >
            Add note
          </button>
          <button
            v-if="row.pipeline_status === 'accepted' || row.pipeline_status === 'waitlist'"
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm font-medium text-brand-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="!row.student_id"
            @click="openEnroll(row); close()"
          >
            Enroll
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
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
              />
            </svg>
          </div>
          <p class="text-center text-lg font-semibold text-slate-800">No applicants in this view</p>
          <p class="mt-2 text-center text-sm text-slate-500">Adjust filters or add a new application to get started.</p>
          <div class="mt-8 flex justify-center">
            <Button type="button" class="gap-2" @click="showCreate = true">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add applicant
            </Button>
          </div>
        </div>
      </template>
    </DataTable>

    <!-- Detail drawer -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="detailOpen && detailApplicant"
          class="fixed inset-0 z-50 flex justify-end bg-slate-900/40 backdrop-blur-[1px]"
          @click.self="detailOpen = false"
        >
          <div
            class="flex h-full w-full max-w-lg flex-col overflow-hidden border-l border-sc-border bg-white shadow-2xl"
            @click.stop
          >
            <div class="flex items-center justify-between border-b border-sc-border px-5 py-4">
              <h2 class="text-lg font-semibold text-slate-900">Applicant details</h2>
              <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="detailOpen = false">×</button>
            </div>
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4 text-sm">
              <section>
                <h3 class="text-2xs font-semibold uppercase text-slate-500">Basic</h3>
                <p class="mt-1 text-slate-800">
                  {{ detailApplicant.first_name }} {{ detailApplicant.last_name }} · {{ detailApplicant.gender || '—' }}
                </p>
                <p class="text-slate-600">DOB: {{ detailApplicant.dob || '—' }}</p>
                <p class="text-slate-600">App # {{ detailApplicant.application_number }}</p>
              </section>
              <section>
                <h3 class="text-2xs font-semibold uppercase text-slate-500">Parent / guardian</h3>
                <p class="text-slate-800">{{ detailApplicant.parent_name || '—' }}</p>
                <p class="text-slate-600">{{ detailApplicant.parent_phone }}</p>
                <p class="text-slate-600">{{ detailApplicant.parent_email || '—' }}</p>
              </section>
              <section>
                <h3 class="text-2xs font-semibold uppercase text-slate-500">Applied class</h3>
                <p class="text-slate-800">{{ detailApplicant.applying_class_name || '—' }}</p>
              </section>
              <section>
                <h3 class="text-2xs font-semibold uppercase text-slate-500">Documents</h3>
                <ul v-if="detailApplicant.documents?.length" class="mt-1 list-inside list-disc text-slate-700">
                  <li v-for="d in detailApplicant.documents" :key="d.id">{{ d.doc_type }} — {{ d.original_name }}</li>
                </ul>
                <p v-else class="text-slate-500">No documents uploaded.</p>
              </section>
              <section>
                <h3 class="text-2xs font-semibold uppercase text-slate-500">Internal notes</h3>
                <p class="whitespace-pre-wrap text-slate-700">{{ detailApplicant.internal_notes || '—' }}</p>
              </section>
              <section v-if="detailApplicant.comments?.length">
                <h3 class="text-2xs font-semibold uppercase text-slate-500">Staff notes</h3>
                <ul class="mt-1 space-y-2 text-xs text-slate-700">
                  <li v-for="c in detailApplicant.comments" :key="c.id" class="rounded-lg bg-slate-50 px-3 py-2">
                    <span class="text-slate-500">{{ formatDt(c.created_at) }} · {{ c.created_by }}</span>
                    <p class="mt-0.5 whitespace-pre-wrap">{{ c.body }}</p>
                  </li>
                </ul>
              </section>
              <section>
                <h3 class="text-2xs font-semibold uppercase text-slate-500">Status history</h3>
                <ul class="mt-2 space-y-2 border-l-2 border-slate-200 pl-3">
                  <li v-for="h in detailApplicant.status_history || []" :key="h.id" class="text-xs">
                    <span class="font-medium text-slate-800">{{ h.to_status }}</span>
                    <span class="text-slate-500"> · {{ formatDt(h.created_at) }}</span>
                    <span v-if="h.note" class="block text-slate-600">{{ h.note }}</span>
                  </li>
                </ul>
              </section>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <Modal v-model="showCreate" title="New application" @update:model-value="(v) => !v && (dupWarning = '')">
      <div class="space-y-3">
        <FormInput v-model="newForm.firstName" label="First name" />
        <FormInput v-model="newForm.lastName" label="Last name" />
        <SelectInput v-model="newForm.gender" label="Gender" :options="genderOpts" />
        <FormInput v-model="newForm.parentName" label="Parent name" />
        <FormInput v-model="newForm.parentPhone" label="Parent phone" />
        <FormInput v-model="newForm.parentEmail" label="Parent email" />
        <SelectInput v-model="newForm.applyingClassId" label="Applying class" :options="classOptions" placeholder="—" />
        <p v-if="dupWarning" class="text-xs text-amber-800">{{ dupWarning }}</p>
      </div>
      <template #footer>
        <Button type="button" variant="secondary" @click="showCreate = false">Cancel</Button>
        <Button type="button" :disabled="savingNew" @click="submitNew">{{ savingNew ? 'Saving…' : 'Create' }}</Button>
      </template>
    </Modal>

    <Modal v-model="showReject" title="Reject application">
      <FormInput v-model="rejectReason" label="Reason" />
      <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
        <input v-model="rejectNotify" type="checkbox" class="rounded border-slate-300" />
        Notify parent (if configured)
      </label>
      <template #footer>
        <Button type="button" variant="secondary" @click="showReject = false">Cancel</Button>
        <Button type="button" @click="confirmReject">Reject</Button>
      </template>
    </Modal>

    <Modal v-model="showNote" title="Add internal note">
      <textarea
        v-model="noteBody"
        rows="4"
        class="w-full rounded-xl border border-sc-line px-3 py-2 text-sm"
        placeholder="Note for staff…"
      />
      <template #footer>
        <Button type="button" variant="secondary" @click="showNote = false">Cancel</Button>
        <Button type="button" @click="saveNote">Save</Button>
      </template>
    </Modal>

    <EnrollmentPaymentModal v-model="showEnroll" :applicant="enrollTarget" @success="onEnrolled" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import RowActionsMenu from '@/components/ui/RowActionsMenu.vue'
import { UserIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import EnrollmentPaymentModal from '@/components/admissions/EnrollmentPaymentModal.vue'
import { admissionsApi } from '@/services/admissionsApi'
import useNotificationStore from '@/stores/notificationStore'

const { successToast, errorToast } = useNotificationStore()

const loading = ref(true)
const bulkWorking = ref(false)
const raw = ref([])
const stats = ref({})
const ctx = ref(null)

const tablePage = ref(1)
const selectedIds = ref([])

const tableFilters = computed(() => {
  const classOpts = [
    { value: '', label: 'All classes' },
    ...(ctx.value?.classes || []).map((c) => ({
      value: String(c.id),
      label: `${c.level_name ? c.level_name + ' · ' : ''}${c.name}`
    }))
  ]
  return [
    {
      key: 'pipeline_status',
      label: 'Status',
      options: [
        { value: '', label: 'All statuses' },
        { value: 'application', label: 'Applied' },
        { value: 'review', label: 'Review' },
        { value: 'accepted', label: 'Accepted' },
        { value: 'waitlist', label: 'Waitlist' },
        { value: 'rejected', label: 'Rejected' }
      ]
    },
    {
      key: 'applying_class_id',
      label: 'Class',
      options: classOpts
    }
  ]
})

const columns = [
  { key: 'fullName', label: 'Applicant', sortable: true, noDefaultWeight: true },
  { key: 'genderLabel', label: 'Gender', sortable: true },
  { key: 'applyingClass', label: 'Applying class' },
  { key: 'statusLabel', label: 'Status', sortable: true },
  { key: 'docsLabel', label: 'Documents', sortable: true },
  { key: 'dateApplied', label: 'Applied', sortable: true }
]

const statusLabels = {
  application: 'Applied',
  review: 'Review',
  accepted: 'Accepted',
  rejected: 'Rejected',
  waitlist: 'Waitlist',
  enrolled: 'Enrolled'
}

const tableRows = computed(() => raw.value.map(mapRow))

function mapRow(a) {
  return {
    ...a,
    fullName: `${a.first_name || ''} ${a.last_name || ''}`.trim(),
    genderLabel: a.gender || '—',
    applying_class_id: a.applying_class_id != null && a.applying_class_id !== '' ? String(a.applying_class_id) : '',
    applyingClass: a.applying_class_name || '—',
    statusLabel: statusLabels[a.pipeline_status] || a.pipeline_status,
    docsLabel: Number(a.doc_count) > 0 ? 'Complete' : 'Missing',
    dateApplied: formatDate(a.created_at)
  }
}

function statusBadgeClass(s) {
  const m = {
    application: 'bg-sky-50 text-sky-800 ring-sky-200/70',
    review: 'bg-amber-50 text-amber-900 ring-amber-200/70',
    accepted: 'bg-emerald-50 text-emerald-800 ring-emerald-200/70',
    rejected: 'bg-rose-50 text-rose-800 ring-rose-200/70',
    waitlist: 'bg-violet-50 text-violet-800 ring-violet-200/70',
    enrolled: 'bg-slate-100 text-slate-700 ring-slate-200/70'
  }
  return m[s] || 'bg-slate-100 text-slate-800 ring-slate-200/70'
}

function formatDate(s) {
  if (!s) return '—'
  try {
    return new Date(s).toLocaleDateString()
  } catch {
    return String(s)
  }
}

function formatDt(s) {
  if (!s) return ''
  try {
    return new Date(s).toLocaleString()
  } catch {
    return s
  }
}

const statCards = computed(() => [
  { key: 'total', label: 'Total applicants' },
  { key: 'application', label: 'Applied' },
  { key: 'review', label: 'In review' },
  { key: 'accepted', label: 'Accepted' },
  { key: 'enrolled', label: 'Enrolled (all time)' },
  { key: 'applicants_pipeline', label: 'In pipeline' }
])

async function load() {
  loading.value = true
  try {
    const [listRes, statsRes, ctxRes] = await Promise.all([
      admissionsApi.list({ applicantsOnly: 1, q: '' }),
      admissionsApi.stats(),
      admissionsApi.context()
    ])
    raw.value = listRes.data?.data || []
    stats.value = statsRes.data?.data || {}
    ctx.value = ctxRes.data?.data || null
  } catch (e) {
    errorToast('Load failed', e?.response?.data?.error || e?.message)
  } finally {
    loading.value = false
  }
}

onMounted(load)

const detailOpen = ref(false)
const detailApplicant = ref(null)

async function openDetail(row) {
  try {
    const { data } = await admissionsApi.get(row.id)
    detailApplicant.value = data?.data
    detailOpen.value = true
  } catch (e) {
    errorToast('Load', e?.response?.data?.error || e?.message)
  }
}

async function acceptOne(row) {
  try {
    await admissionsApi.accept(row.id)
    successToast('Accepted', 'Student record prepared where needed.')
    await load()
  } catch (e) {
    errorToast('Accept', e?.response?.data?.error || e?.message)
  }
}

const showReject = ref(false)
const rejectTargetId = ref(null)
const rejectReason = ref('')
const rejectNotify = ref(false)
const rejectBulk = ref(false)

function openReject(id) {
  rejectBulk.value = false
  rejectTargetId.value = id
  rejectReason.value = ''
  rejectNotify.value = false
  showReject.value = true
}

function openBulkReject() {
  rejectBulk.value = true
  rejectTargetId.value = null
  rejectReason.value = ''
  showReject.value = true
}

async function confirmReject() {
  try {
    if (rejectBulk.value) {
      const ids = [...selectedIds.value]
      for (const id of ids) {
        await admissionsApi.reject({ id, reason: rejectReason.value, notifyParent: rejectNotify.value })
      }
      successToast('Rejected', `${ids.length} application(s).`)
    } else if (rejectTargetId.value) {
      await admissionsApi.reject({ id: rejectTargetId.value, reason: rejectReason.value, notifyParent: rejectNotify.value })
      successToast('Rejected', 'Recorded.')
    }
    showReject.value = false
    selectedIds.value = []
    await load()
  } catch (e) {
    errorToast('Reject', e?.response?.data?.error || e?.message)
  }
}

async function bulkAccept() {
  bulkWorking.value = true
  try {
    const ids = [...selectedIds.value]
    for (const id of ids) {
      const row = raw.value.find((r) => r.id === id)
      if (!row) continue
      if (['application', 'review', 'waitlist'].includes(row.pipeline_status)) {
        await admissionsApi.accept(id)
      }
    }
    successToast('Bulk accept', 'Done.')
    selectedIds.value = []
    await load()
  } catch (e) {
    errorToast('Bulk accept', e?.response?.data?.error || e?.message)
  } finally {
    bulkWorking.value = false
  }
}

const showNote = ref(false)
const noteBody = ref('')
const noteApplicantId = ref(null)

function openNote(row) {
  noteApplicantId.value = row.id
  noteBody.value = ''
  showNote.value = true
}

async function saveNote() {
  if (!noteApplicantId.value || !noteBody.value.trim()) return
  try {
    await admissionsApi.addComment({ applicantId: noteApplicantId.value, body: noteBody.value.trim() })
    successToast('Note', 'Saved.')
    showNote.value = false
    await load()
  } catch (e) {
    errorToast('Note', e?.response?.data?.error || e?.message)
  }
}

const showEnroll = ref(false)
const enrollTarget = ref(null)

function openEnroll(row) {
  enrollTarget.value = { ...row }
  showEnroll.value = true
}

async function onEnrolled() {
  await load()
}

const showCreate = ref(false)
const savingNew = ref(false)
const dupWarning = ref('')
const newForm = reactive({
  firstName: '',
  lastName: '',
  gender: '',
  parentName: '',
  parentPhone: '',
  parentEmail: '',
  applyingClassId: ''
})

const genderOpts = [
  { value: '', label: '—' },
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' }
]

const classOptions = computed(() => [
  { value: '', label: '—' },
  ...(ctx.value?.classes || []).map((c) => ({
    value: String(c.id),
    label: `${c.level_name ? c.level_name + ' · ' : ''}${c.name}`
  }))
])

async function submitNew() {
  savingNew.value = true
  dupWarning.value = ''
  try {
    await admissionsApi.create({
      firstName: newForm.firstName,
      lastName: newForm.lastName,
      gender: newForm.gender,
      parentName: newForm.parentName,
      parentPhone: newForm.parentPhone,
      parentEmail: newForm.parentEmail,
      applyingClassId: newForm.applyingClassId || undefined
    })
    successToast('Created', 'Application saved.')
    showCreate.value = false
    await load()
  } catch (e) {
    const msg = e?.response?.data?.error || e?.message
    if (e?.response?.status === 409) dupWarning.value = msg
    else errorToast('Create', msg)
  } finally {
    savingNew.value = false
  }
}

function exportCsv() {
  const headers = ['Full name', 'Gender', 'Phone', 'Class', 'Status', 'Documents', 'Date applied']
  const lines = [headers.join(',')]
  for (const r of tableRows.value) {
    lines.push(
      [
        `"${(r.fullName || '').replace(/"/g, '""')}"`,
        r.genderLabel,
        r.parent_phone || '',
        r.applyingClass,
        r.statusLabel,
        r.doc_count > 0 ? `${r.doc_count} file(s)` : 'Missing',
        r.dateApplied
      ].join(',')
    )
  }
  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `applicants-${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

watch(showCreate, (v) => {
  if (v) {
    Object.assign(newForm, {
      firstName: '',
      lastName: '',
      gender: '',
      parentName: '',
      parentPhone: '',
      parentEmail: '',
      applyingClassId: ''
    })
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
