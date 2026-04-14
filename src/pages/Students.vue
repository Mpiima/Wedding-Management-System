<template>
  <div class="page-shell max-w-[1680px]">
    <PageHeader title="Students" :description="headerDescription">
      <template #actions>
        <router-link
          to="/admissions/enrollments"
          class="inline-flex h-10 items-center rounded-xl border border-slate-200/90 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
        >
          Enrollments
        </router-link>
        <Button type="button" variant="secondary" :disabled="loading" @click="load">Refresh</Button>
      </template>
    </PageHeader>

    <div
      v-if="metaMessage"
      class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
    >
      {{ metaMessage }}
    </div>

    <div
      class="mt-6 flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200/80 bg-gradient-to-b from-white to-slate-50/40 p-5 shadow-sm sm:p-6"
    >
      <div class="min-w-[10rem] flex-1">
        <label class="mb-1.5 block text-2xs font-semibold uppercase tracking-wide text-slate-500">Academic year</label>
        <select
          v-model="filters.academicYearId"
          class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm font-medium text-slate-800 shadow-sm"
          @change="onYearChange"
        >
          <option v-for="y in yearOpts" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
        </select>
      </div>
      <div class="min-w-[10rem] flex-1">
        <label class="mb-1.5 block text-2xs font-semibold uppercase tracking-wide text-slate-500">Study period</label>
        <select
          v-model="filters.studyPeriodId"
          class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm font-medium text-slate-800 shadow-sm"
          @change="load"
        >
          <option v-for="p in periodOpts" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
        </select>
      </div>
      <div class="min-w-[10rem] flex-1">
        <label class="mb-1.5 block text-2xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
        <select v-model="filters.classId" class="h-11 w-full rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm" @change="load">
          <option value="">All classes</option>
          <option v-for="c in classOpts" :key="c.id" :value="String(c.id)">
            {{ c.level_name ? c.level_name + ' · ' : '' }}{{ c.name }}
          </option>
        </select>
      </div>
    </div>

    <DataTable
      v-model:current-page="page"
      v-model:selected-ids="selectedIds"
      class="mt-6"
      title="Enrolled students"
      :columns="columns"
      :data="rows"
      row-key="id"
      searchable
      search-placeholder="Search name, admission #, class…"
      :search-keys="['name', 'admissionNumber', 'classDisplay']"
      :sortable="true"
      :pagination="true"
      :page-size="12"
      :selectable="true"
      empty-text="No students enrolled for this year and period."
    >
      <template #cell-name="{ row }">
        <div class="flex max-w-[min(100%,20rem)] items-center gap-3">
          <img
            v-if="row.photoUrl"
            :key="row.photoVersion"
            :src="row.photoUrl"
            alt=""
            class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-slate-200/90"
          />
          <div
            v-else
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-50 to-brand-100/80 text-brand-600 ring-1 ring-brand-200/50"
          >
            <span class="text-xs font-bold">{{ initials(row.name) }}</span>
          </div>
          <span class="font-semibold text-slate-900">{{ row.name }}</span>
        </div>
      </template>

      <template #cell-status="{ value }">
        <Badge :tone="statusTone(value)">{{ value || 'Enrolled' }}</Badge>
      </template>

      <template #bulk-actions="{ clear }">
        <Button variant="secondary" class="!text-xs" @click="bulkCopyPhones">Copy guardian phones</Button>
        <Button variant="ghost" class="!text-xs" @click="clear">Clear</Button>
      </template>

      <template #actions="{ row }">
        <div class="flex flex-wrap items-center justify-end gap-1">
          <Button variant="ghost" class="!px-2 !py-1 text-xs" @click="openProfile(row)">Profile</Button>
          <router-link
            :to="{ name: 'PortalDashboard', query: { studentId: String(row.id) } }"
            class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-brand-700 ring-1 ring-brand-200/80 transition hover:bg-brand-50"
          >
            Portal
          </router-link>
        </div>
      </template>

      <template #empty>
        <div class="mx-auto max-w-md px-4 py-8 text-center">
          <p class="text-base font-semibold text-slate-800">No enrolled students</p>
          <p class="mt-2 text-sm text-slate-500">
            Enroll applicants from Admissions, or pick another year or period above.
          </p>
          <router-link
            to="/admissions/applicants"
            class="mt-6 inline-flex items-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
          >
            Go to Admissions
          </router-link>
        </div>
      </template>
    </DataTable>

    <Modal v-model="showProfile" :title="profileModalTitle" max-width-class="max-w-2xl">
      <div v-if="profileLoading" class="py-12 text-center text-sm text-slate-500">Loading profile…</div>
      <div v-else class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
          <div class="flex shrink-0 flex-col items-center gap-2">
            <div
              class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-50 text-slate-400"
            >
              <img
                v-if="modalPhotoUrl"
                :key="photoCacheKey"
                :src="modalPhotoUrl"
                alt=""
                class="h-full w-full object-cover"
              />
              <span v-else class="text-2xl font-bold text-slate-500">{{ profileInitials }}</span>
            </div>
            <label
              class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-2xs font-semibold uppercase tracking-wide text-slate-700 shadow-sm hover:bg-slate-50"
            >
              <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="onPhotoFile" />
              {{ photoUploading ? 'Uploading…' : 'Upload photo' }}
            </label>
          </div>
          <p class="flex-1 text-sm text-slate-600">
            Admission <span class="font-mono font-semibold text-slate-900">{{ profileAdmission }}</span
            >. Guardian contacts, medical notes, and other details are edited below. The table above stays compact.
          </p>
        </div>

        <details open class="rounded-xl border border-slate-200/90 bg-slate-50/50">
          <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-slate-900">Identity</summary>
          <div class="space-y-4 border-t border-slate-200/80 px-4 pb-4 pt-3">
            <div class="grid gap-4 sm:grid-cols-2">
              <FormInput v-model="profileForm.first_name" label="First name" />
              <FormInput v-model="profileForm.last_name" label="Last name" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <SelectInput
                v-model="profileForm.gender"
                label="Gender"
                :options="genderOptions"
                placeholder="Select"
              />
              <FormInput v-model="profileForm.dob" type="date" label="Date of birth" />
            </div>
          </div>
        </details>

        <details open class="rounded-xl border border-slate-200/90 bg-slate-50/50">
          <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-slate-900">Guardian / contact</summary>
          <div class="space-y-4 border-t border-slate-200/80 px-4 pb-4 pt-3">
            <FormInput v-model="profileForm.guardian_name" label="Guardian name" />
            <div class="grid gap-4 sm:grid-cols-2">
              <FormInput v-model="profileForm.guardian_phone" label="Phone" type="tel" />
              <FormInput v-model="profileForm.guardian_email" label="Email" type="email" />
            </div>
          </div>
        </details>

        <details open class="rounded-xl border border-slate-200/90 bg-slate-50/50">
          <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-slate-900">Student portal login</summary>
          <div class="space-y-4 border-t border-slate-200/80 px-4 pb-4 pt-3">
            <p class="text-sm text-slate-600">
              Students sign in with a portal username starting with <span class="font-mono font-semibold">ADM</span> (from admission
              number) and the password you set here.
            </p>
            <p v-if="portalHasLogin && portalLoginUsername" class="text-sm text-slate-700">
              Current username: <span class="font-mono font-semibold text-slate-900">{{ portalLoginUsername }}</span>
            </p>
            <FormInput
              v-model="portalCustomPassword"
              type="password"
              label="Optional: set a custom password"
              hint="Leave blank to auto-generate a secure password (shown once)."
            />
            <Button type="button" variant="secondary" :disabled="portalGenLoading" @click="generatePortalLogin">
              {{ portalGenLoading ? 'Saving…' : portalHasLogin ? 'Regenerate password' : 'Generate portal password' }}
            </Button>
            <div
              v-if="portalCredentialResult"
              class="rounded-xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-950"
            >
              <p class="font-semibold">Credentials (copy now — password is not shown again)</p>
              <p class="mt-2 font-mono text-xs">
                Username: <span class="select-all font-semibold">{{ portalCredentialResult.username }}</span>
              </p>
              <p class="mt-1 font-mono text-xs">
                Password: <span class="select-all font-semibold">{{ portalCredentialResult.password }}</span>
              </p>
              <div class="mt-3 flex flex-wrap gap-2">
                <Button type="button" class="!text-xs" @click="copyCred('user')">Copy username</Button>
                <Button type="button" class="!text-xs" @click="copyCred('pass')">Copy password</Button>
              </div>
            </div>
          </div>
        </details>

        <details open class="rounded-xl border border-slate-200/90 bg-slate-50/50">
          <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-slate-900">Notes &amp; bio</summary>
          <div class="border-t border-slate-200/80 px-4 pb-4 pt-3">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Additional notes</label>
            <textarea
              v-model="profileForm.bio_notes"
              rows="5"
              placeholder="Allergies, learning support, club memberships, etc."
              class="block w-full rounded-xl border border-sc-line bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
            />
          </div>
        </details>
      </div>
      <template #footer>
        <div class="flex flex-wrap justify-end gap-2">
          <Button variant="secondary" :disabled="profileSaving" @click="closeProfile">Close</Button>
          <Button :disabled="profileSaving || profileLoading" @click="saveProfile">
            {{ profileSaving ? 'Saving…' : 'Save changes' }}
          </Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { studentsApi } from '@/services/studentsApi'
import { admissionsApi } from '@/services/admissionsApi'
import { publicUploadUrl } from '@/utils/formatters'
import useNotificationStore from '@/stores/notificationStore'

const { successToast, errorToast } = useNotificationStore()

const loading = ref(true)
const raw = ref([])
const ctx = ref(null)
const meta = ref({})
const metaMessage = ref('')

const page = ref(1)
const selectedIds = ref([])

const filters = reactive({
  academicYearId: '',
  studyPeriodId: '',
  classId: ''
})

const columns = [
  { key: 'name', label: 'Student', sortable: true, noDefaultWeight: true },
  { key: 'admissionNumber', label: 'Admission #', sortable: true },
  { key: 'classDisplay', label: 'Class', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
]

const genderOptions = [
  { value: '', label: '—' },
  { value: 'Female', label: 'Female' },
  { value: 'Male', label: 'Male' },
  { value: 'Other', label: 'Other' }
]

const showProfile = ref(false)
const profileId = ref(null)
const profileLoading = ref(false)
const profileSaving = ref(false)
const photoUploading = ref(false)
const profileAdmission = ref('')
const profilePhotoPath = ref('')
const photoCacheKey = ref(0)

const portalHasLogin = ref(false)
const portalLoginUsername = ref('')
const portalCustomPassword = ref('')
const portalGenLoading = ref(false)
const portalCredentialResult = ref(null)

const profileForm = reactive({
  first_name: '',
  last_name: '',
  gender: '',
  dob: '',
  guardian_name: '',
  guardian_phone: '',
  guardian_email: '',
  bio_notes: ''
})

const profileModalTitle = computed(() => {
  const adm = profileAdmission.value || '—'
  return `Student profile · ${adm}`
})

const modalPhotoUrl = computed(() => {
  const p = profilePhotoPath.value
  if (!p) return ''
  const u = publicUploadUrl(p)
  if (!u) return ''
  return `${u}${u.includes('?') ? '&' : '?'}v=${photoCacheKey.value}`
})

const profileInitials = computed(() => {
  const a = profileForm.first_name || ''
  const b = profileForm.last_name || ''
  const n = `${a} ${b}`.trim()
  return initials(n || 'Student')
})

const yearOpts = computed(() => ctx.value?.academicYears || [])
const periodOpts = computed(() => {
  const y = filters.academicYearId
  if (!y) return ctx.value?.studyPeriods || []
  return (ctx.value?.studyPeriods || []).filter((p) => String(p.academic_year_id) === String(y))
})
const classOpts = computed(() => ctx.value?.classes || [])

const headerDescription = computed(() => {
  const y = meta.value?.academicYearName
  const p = meta.value?.studyPeriodName
  if (y && p) return `Directory for ${y} · ${p}. Open a profile to edit bio data or upload a photo.`
  return 'Students with an enrollment in the selected academic period.'
})

const rows = computed(() => raw.value.map(mapApiRow))

function formatDob(d) {
  if (d == null || d === '') return ''
  const s = String(d).slice(0, 10)
  return /^\d{4}-\d{2}-\d{2}$/.test(s) ? s : ''
}

function mapApiRow(r) {
  const name = `${r.first_name || ''} ${r.last_name || ''}`.trim() || '—'
  const cls = r.class_name && r.class_name !== '—' ? r.class_name : ''
  const st = r.stream_name && r.stream_name !== '—' ? r.stream_name : ''
  const classDisplay = [cls, st].filter(Boolean).join(' · ') || '—'
  const photoPath = r.photo_path || ''
  return {
    id: r.student_id,
    enrollment_id: r.enrollment_id,
    name,
    admissionNumber: r.admission_number || '—',
    classDisplay,
    guardian_phone: r.guardian_phone || '',
    status: 'Enrolled',
    class_id: r.class_id != null ? String(r.class_id) : '',
    photo_path: photoPath,
    photoUrl: photoPath ? publicUploadUrl(photoPath) : '',
    photoVersion: photoPath ? `${photoPath}:${r.student_id}` : '0'
  }
}

function initials(name) {
  const p = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  if (!p.length) return '?'
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

function statusTone() {
  return 'success'
}

async function loadContext() {
  const { data } = await admissionsApi.context()
  ctx.value = data?.data || null
  const y = ctx.value?.academicYear?.id
  const p = ctx.value?.studyPeriod?.id
  if (!filters.academicYearId && y) filters.academicYearId = String(y)
  if (!filters.studyPeriodId && p) filters.studyPeriodId = String(p)
}

function onYearChange() {
  const y = filters.academicYearId
  const periods = (ctx.value?.studyPeriods || []).filter((p) => !y || String(p.academic_year_id) === String(y))
  const ok = periods.some((p) => String(p.id) === String(filters.studyPeriodId))
  if (!ok) {
    const def = periods.find((p) => Number(p.is_active)) || periods[0]
    filters.studyPeriodId = def ? String(def.id) : ''
  }
  load()
}

async function load() {
  loading.value = true
  metaMessage.value = ''
  try {
    await loadContext()
    const params = {}
    if (filters.academicYearId) params.academicYearId = filters.academicYearId
    if (filters.studyPeriodId) params.studyPeriodId = filters.studyPeriodId
    if (filters.classId) params.classId = filters.classId

    const res = await studentsApi.directory(params)
    const m = res.data?.meta || {}
    meta.value = m
    raw.value = res.data?.data || []
    if (m.message) {
      metaMessage.value = m.message
    }
  } catch (e) {
    errorToast('Students', e?.response?.data?.error || e?.message || 'Failed to load')
    raw.value = []
  } finally {
    loading.value = false
  }
}

function applyProfileToForm(d) {
  profileForm.first_name = d.first_name != null ? String(d.first_name) : ''
  profileForm.last_name = d.last_name != null ? String(d.last_name) : ''
  profileForm.gender = d.gender != null ? String(d.gender) : ''
  profileForm.dob = formatDob(d.dob)
  profileForm.guardian_name = d.guardian_name != null ? String(d.guardian_name) : ''
  profileForm.guardian_phone = d.guardian_phone != null ? String(d.guardian_phone) : ''
  profileForm.guardian_email = d.guardian_email != null ? String(d.guardian_email) : ''
  profileForm.bio_notes = d.bio_notes != null ? String(d.bio_notes) : ''
  profileAdmission.value = d.admission_number != null ? String(d.admission_number) : ''
  profilePhotoPath.value = d.photo_path != null ? String(d.photo_path) : ''
  photoCacheKey.value += 1
}

async function openProfile(row) {
  profileId.value = row.id
  showProfile.value = true
  profileLoading.value = true
  portalCredentialResult.value = null
  portalCustomPassword.value = ''
  try {
    const { data } = await studentsApi.profile(row.id)
    const d = data?.data
    if (d) applyProfileToForm(d)
    await loadPortalAccess()
  } catch (e) {
    errorToast('Profile', e?.response?.data?.error || e?.message || 'Failed to load profile')
    showProfile.value = false
  } finally {
    profileLoading.value = false
  }
}

async function loadPortalAccess() {
  if (!profileId.value) return
  try {
    const { data } = await studentsApi.portalAccessStatus(profileId.value)
    const d = data?.data
    portalHasLogin.value = !!d?.hasPortalLogin
    portalLoginUsername.value = d?.username ? String(d.username) : ''
  } catch {
    portalHasLogin.value = false
    portalLoginUsername.value = ''
  }
}

async function generatePortalLogin() {
  if (!profileId.value) return
  portalGenLoading.value = true
  portalCredentialResult.value = null
  try {
    const pwd = portalCustomPassword.value.trim()
    const { data } = await studentsApi.generatePortalLogin(profileId.value, pwd || undefined)
    const inner = data?.data
    if (inner?.username && inner?.password) {
      portalCredentialResult.value = { username: inner.username, password: inner.password }
      portalHasLogin.value = true
      portalLoginUsername.value = inner.username
      successToast('Portal login', 'Password saved. Copy the credentials below.')
    }
    portalCustomPassword.value = ''
  } catch (e) {
    errorToast('Portal login', e?.response?.data?.error || e?.message || 'Failed to save')
  } finally {
    portalGenLoading.value = false
  }
}

function copyCred(kind) {
  const c = portalCredentialResult.value
  if (!c) return
  const text = kind === 'user' ? c.username : c.password
  navigator.clipboard?.writeText(text).then(
    () => successToast('Copied', kind === 'user' ? 'Username copied.' : 'Password copied.'),
    () => successToast('Copy', text)
  )
}

function closeProfile() {
  showProfile.value = false
  portalCredentialResult.value = null
  portalCustomPassword.value = ''
}

async function saveProfile() {
  if (!profileId.value) return
  profileSaving.value = true
  try {
    await studentsApi.updateProfile(profileId.value, {
      first_name: profileForm.first_name,
      last_name: profileForm.last_name,
      gender: profileForm.gender,
      dob: profileForm.dob || '',
      guardian_name: profileForm.guardian_name,
      guardian_phone: profileForm.guardian_phone,
      guardian_email: profileForm.guardian_email,
      bio_notes: profileForm.bio_notes
    })
    successToast('Profile', 'Changes saved.')
    await load()
    showProfile.value = false
  } catch (e) {
    errorToast('Profile', e?.response?.data?.error || e?.message || 'Save failed')
  } finally {
    profileSaving.value = false
  }
}

async function onPhotoFile(ev) {
  const file = ev.target?.files?.[0]
  if (!file || !profileId.value) return
  photoUploading.value = true
  try {
    await studentsApi.uploadPhoto(profileId.value, file)
    successToast('Photo', 'Image uploaded.')
    const { data } = await studentsApi.profile(profileId.value)
    const d = data?.data
    if (d) {
      applyProfileToForm(d)
    }
    await load()
  } catch (e) {
    errorToast('Photo', e?.response?.data?.error || e?.message || 'Upload failed')
  } finally {
    photoUploading.value = false
    ev.target.value = ''
  }
}

function bulkCopyPhones() {
  const phones = rows.value
    .filter((r) => selectedIds.value.includes(r.id) && r.guardian_phone)
    .map((r) => r.guardian_phone)
  if (!phones.length) {
    successToast('Clipboard', 'No guardian phone numbers in selection.')
    return
  }
  const text = [...new Set(phones)].join(', ')
  navigator.clipboard?.writeText(text).then(
    () => successToast('Copied', `${phones.length} phone number(s).`),
    () => successToast('Phones', text)
  )
}

onMounted(load)

watch(
  () => [filters.academicYearId, ctx.value],
  () => {
    if (!filters.academicYearId || !ctx.value) return
    const y = filters.academicYearId
    const periods = (ctx.value.studyPeriods || []).filter((p) => String(p.academic_year_id) === String(y))
    if (!periods.some((p) => String(p.id) === String(filters.studyPeriodId)) && periods.length) {
      const def = periods.find((p) => Number(p.is_active)) || periods[0]
      filters.studyPeriodId = String(def.id)
    }
  },
  { deep: true }
)
</script>
