<template>
  <div class="portal-bio">
    <div v-if="portal?.loading" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">
      Loading your profile…
    </div>

    <div
      v-else-if="portal?.error && !portal?.student"
      class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"
    >
      {{ portal.error }}
    </div>

    <template v-else-if="student">
      <div class="flex min-h-[32rem] flex-col gap-4 lg:flex-row lg:gap-0 lg:rounded-xl lg:border lg:border-slate-200/90 lg:bg-white lg:shadow-sm">
        <!-- Left: MY PROFILE -->
        <aside class="flex w-full flex-col border-b border-slate-200 bg-slate-50/80 lg:w-[min(100%,17.5rem)] lg:border-b-0 lg:border-r lg:bg-[#f4f8fb]">
          <p class="px-4 pt-4 text-[11px] font-semibold uppercase tracking-[0.2em] text-[#5a8fb0]">My profile</p>

          <div class="flex flex-col items-center px-4 pb-4 pt-2">
            <div
              class="flex h-[7.5rem] w-[6.5rem] items-center justify-center overflow-hidden rounded border border-slate-200/80 bg-white shadow-sm"
            >
              <img v-if="photoUrl" :src="photoUrl" alt="" class="h-full w-full object-cover" />
              <span v-else class="text-2xl font-bold text-slate-400">{{ initials }}</span>
            </div>
            <h1 class="mt-3 text-center text-base font-bold uppercase leading-tight text-[#0b5f84]">
              {{ displayNameUpper }}
            </h1>
            <p class="mt-1 text-center text-[11px] font-medium text-slate-700">
              Student no.: <span class="font-mono">{{ student.admission_number || '—' }}</span>
            </p>
            <div
              class="mt-3 inline-flex items-center gap-1.5 rounded border border-rose-300 bg-rose-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-rose-700"
            >
              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
              {{ registrationStatus }}
            </div>
          </div>

          <nav class="flex flex-1 flex-col gap-0.5 px-2 pb-2">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              class="flex w-full items-center gap-2 rounded-md px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide transition"
              :class="
                activeTab === tab.id
                  ? 'bg-[#0b5f84] text-white shadow-sm'
                  : 'text-slate-700 hover:bg-white/70'
              "
              @click="activeTab = tab.id"
            >
              <component :is="tab.icon" class="h-4 w-4 shrink-0 opacity-90" />
              {{ tab.label }}
            </button>
          </nav>

          <div class="mt-auto flex items-center justify-center gap-3 border-t border-slate-200/80 py-2.5 text-slate-400">
            <button type="button" class="rounded p-1 hover:bg-white/80 hover:text-slate-600" title="Profile help" aria-label="Help">
              <PencilSquareIcon class="h-4 w-4" />
            </button>
            <router-link
              v-if="!showAdminNav"
              :to="portalTo('/portal/password')"
              class="rounded p-1 text-slate-400 hover:bg-white/80 hover:text-slate-600"
              title="Change password"
              aria-label="Change password"
            >
              <Cog6ToothIcon class="h-4 w-4" />
            </router-link>
            <button type="button" class="rounded p-1 hover:bg-white/80 hover:text-slate-600" title="More" aria-label="More">
              <EllipsisHorizontalIcon class="h-4 w-4" />
            </button>
          </div>
        </aside>

        <!-- Right: section content -->
        <div class="min-w-0 flex-1 bg-white lg:rounded-r-xl">
          <!-- Personal details -->
          <section v-show="activeTab === 'personal'" class="portal-bio-section">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
              <div class="flex items-center gap-2">
                <AcademicCapIcon class="h-5 w-5 text-[#0b5f84]" />
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900">Personal details</h2>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded bg-[#0b5f84] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-white shadow-sm hover:bg-[#0a5273]"
                  @click="printBio"
                >
                  <PrinterIcon class="h-4 w-4" />
                  Print bio data
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded border-2 border-dashed border-rose-500 bg-white px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-600 hover:bg-rose-50"
                  @click="reloadProfile"
                >
                  <ArrowPathIcon class="h-4 w-4" />
                  Reload
                </button>
              </div>
            </div>

            <div class="border-b border-slate-100 px-4 py-2 sm:px-5">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded border-2 border-dashed border-rose-500 bg-white px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-600 hover:bg-rose-50"
                @click="activeTab = 'guardian'"
              >
                <PencilSquareIcon class="h-4 w-4" />
                Edit contacts
              </button>
              <p class="mt-1.5 text-[10px] text-slate-500">Updates may require your school office — use Guardian details to review contacts.</p>
            </div>

            <div class="overflow-x-auto">
              <table class="portal-bio-table min-w-full border-collapse text-left text-sm">
                <tbody>
                  <tr v-for="(row, idx) in personalGridRows" :key="idx" class="portal-bio-row">
                    <td class="portal-bio-cell">
                      <span class="font-bold uppercase text-slate-900">{{ row.leftLabel }}</span>
                      <span class="text-slate-800">: {{ row.leftVal }}</span>
                    </td>
                    <td class="portal-bio-cell">
                      <span class="font-bold uppercase text-slate-900">{{ row.rightLabel }}</span>
                      <span class="text-slate-800">: {{ row.rightVal }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="student.bio_notes" class="border-t border-slate-200 px-4 py-4 sm:px-5">
              <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Additional notes</p>
              <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ student.bio_notes }}</p>
            </div>
          </section>

          <!-- Academic -->
          <section v-show="activeTab === 'academic'" class="portal-bio-section">
            <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3 sm:px-5">
              <BookOpenIcon class="h-5 w-5 text-[#0b5f84]" />
              <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900">Academic details</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="portal-bio-table min-w-full border-collapse text-left text-sm">
                <tbody>
                  <tr v-for="(row, idx) in academicGridRows" :key="idx" class="portal-bio-row">
                    <td class="portal-bio-cell">
                      <span class="font-bold uppercase text-slate-900">{{ row.leftLabel }}</span>
                      <span class="text-slate-800">: {{ row.leftVal }}</span>
                    </td>
                    <td class="portal-bio-cell">
                      <span class="font-bold uppercase text-slate-900">{{ row.rightLabel }}</span>
                      <span class="text-slate-800">: {{ row.rightVal }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Guardian -->
          <section v-show="activeTab === 'guardian'" class="portal-bio-section">
            <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3 sm:px-5">
              <UserGroupIcon class="h-5 w-5 text-[#0b5f84]" />
              <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900">Guardian details</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="portal-bio-table min-w-full border-collapse text-left text-sm">
                <tbody>
                  <tr v-for="(row, idx) in guardianGridRows" :key="idx" class="portal-bio-row">
                    <td class="portal-bio-cell">
                      <span class="font-bold uppercase text-slate-900">{{ row.leftLabel }}</span>
                      <span class="text-slate-800">: {{ row.leftVal }}</span>
                    </td>
                    <td class="portal-bio-cell">
                      <span class="font-bold uppercase text-slate-900">{{ row.rightLabel }}</span>
                      <span class="text-slate-800">: {{ row.rightVal }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Next of kin -->
          <section v-show="activeTab === 'nextOfKin'" class="portal-bio-section p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900">Next of kin</h2>
            <p class="mt-3 text-sm text-slate-600">
              Next-of-kin records are not stored separately yet. Your guardian contact on file is used for emergencies.
            </p>
            <button
              type="button"
              class="mt-4 text-sm font-semibold text-[#0b5f84] underline hover:no-underline"
              @click="activeTab = 'guardian'"
            >
              View guardian details
            </button>
          </section>

          <!-- Password -->
          <section v-show="activeTab === 'password' && !showAdminNav" class="portal-bio-section p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900">Change password</h2>
            <p class="mt-3 text-sm text-slate-600">
              Update the password you use with your portal username. You will need your current password.
            </p>
            <router-link
              :to="portalTo('/portal/password')"
              class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#0b5f84] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#0a5273]"
            >
              Open change password
            </router-link>
          </section>
        </div>
      </div>

      <div class="mt-6 rounded-xl border border-slate-200/80 bg-slate-50/50 px-4 py-3 text-center text-xs text-slate-500">
        School notices will appear here when your school publishes them.
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, inject, ref, watch } from 'vue'
import { usePortalLink } from '@/composables/usePortalLink'
import { usePortalAdminNav } from '@/composables/usePortalAdminNav'
import {
  AcademicCapIcon,
  ArrowPathIcon,
  BookOpenIcon,
  Cog6ToothIcon,
  EllipsisHorizontalIcon,
  IdentificationIcon,
  PencilSquareIcon,
  PrinterIcon,
  UserGroupIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

const portal = inject('portalContext', null)
const { portalTo } = usePortalLink()
const { showAdminNav } = usePortalAdminNav()

const activeTab = ref('personal')

watch(showAdminNav, (v) => {
  if (v && activeTab.value === 'password') activeTab.value = 'personal'
})

const student = computed(() => portal?.student ?? null)
const enrollment = computed(() => portal?.enrollment ?? null)
const meta = computed(() => portal?.meta ?? {})

const displayName = computed(() => portal?.displayName || '')
const displayNameUpper = computed(() => displayName.value.toUpperCase())
const photoUrl = computed(() => portal?.photoUrl || '')

const registrationStatus = computed(() => (enrollment.value ? 'Registered' : 'Not registered'))

const initials = computed(() => {
  const n = displayName.value.trim().split(/\s+/).filter(Boolean)
  if (!n.length) return '?'
  if (n.length === 1) return n[0].slice(0, 2).toUpperCase()
  return (n[0][0] + n[n.length - 1][0]).toUpperCase()
})

const dobDisplay = computed(() => {
  const d = student.value?.dob
  if (!d) return '—'
  try {
    return new Date(d).toLocaleDateString()
  } catch {
    return String(d).slice(0, 10)
  }
})

const enrolledDisplay = computed(() => {
  const e = enrollment.value?.enrolled_at
  if (!e) return '—'
  try {
    return new Date(e).toLocaleDateString()
  } catch {
    return String(e).slice(0, 10)
  }
})

const allTabs = [
  { id: 'personal', label: 'Personal details', icon: UserIcon },
  { id: 'academic', label: 'Academic details', icon: BookOpenIcon },
  { id: 'guardian', label: 'Guardian details', icon: UserGroupIcon },
  { id: 'nextOfKin', label: 'Next of kin', icon: IdentificationIcon },
  { id: 'password', label: 'Change password', icon: Cog6ToothIcon }
]

const tabs = computed(() =>
  showAdminNav.value ? allTabs.filter((t) => t.id !== 'password') : allTabs
)

const dash = (v) => (v != null && String(v).trim() !== '' ? String(v).trim() : '—')

const personalGridRows = computed(() => {
  const s = student.value
  if (!s) return []
  return [
    {
      leftLabel: 'Surname',
      leftVal: dash(s.last_name),
      rightLabel: 'Religion',
      rightVal: '—'
    },
    {
      leftLabel: 'Other names',
      leftVal: dash(s.first_name),
      rightLabel: 'District',
      rightVal: '—'
    },
    {
      leftLabel: 'Email',
      leftVal: dash(s.guardian_email),
      rightLabel: 'Nationality',
      rightVal: '—'
    },
    {
      leftLabel: 'Tel. phone',
      leftVal: dash(s.guardian_phone),
      rightLabel: 'National ID no.',
      rightVal: '—'
    },
    {
      leftLabel: 'Sex',
      leftVal: dash(s.gender),
      rightLabel: 'Passport',
      rightVal: '—'
    },
    {
      leftLabel: 'Date of birth',
      leftVal: dobDisplay.value,
      rightLabel: '—',
      rightVal: '—'
    }
  ]
})

const academicGridRows = computed(() => {
  const e = enrollment.value
  const m = meta.value
  return [
    {
      leftLabel: 'Academic year',
      leftVal: dash(m.academicYearName),
      rightLabel: 'Study period / term',
      rightVal: dash(m.studyPeriodName)
    },
    {
      leftLabel: 'Class',
      leftVal: e ? dash(e.class_name) : '—',
      rightLabel: 'Stream',
      rightVal: e ? dash(e.stream_name) : '—'
    },
    {
      leftLabel: 'Date enrolled',
      leftVal: enrolledDisplay.value,
      rightLabel: 'Status',
      rightVal: enrollment.value ? 'Enrolled' : '—'
    }
  ]
})

const guardianGridRows = computed(() => {
  const s = student.value
  if (!s) return []
  return [
    {
      leftLabel: 'Guardian name',
      leftVal: dash(s.guardian_name),
      rightLabel: 'Tel. phone',
      rightVal: dash(s.guardian_phone)
    },
    {
      leftLabel: 'Email',
      leftVal: dash(s.guardian_email),
      rightLabel: '—',
      rightVal: '—'
    }
  ]
})

function printBio() {
  window.print()
}

function reloadProfile() {
  portal?.load?.()
}
</script>

<style scoped>
@media print {
  .portal-bio aside,
  .portal-bio button,
  .portal-bio .mt-6 {
    display: none !important;
  }
  .portal-bio .min-h-\[32rem\] {
    border: none !important;
    box-shadow: none !important;
  }
}

.portal-bio-table {
  font-size: 13px;
}

.portal-bio-row:nth-child(odd) {
  background-color: #ffffff;
}

.portal-bio-row:nth-child(even) {
  background-color: #f3f4f6;
}

.portal-bio-cell {
  width: 50%;
  vertical-align: top;
  padding: 0.65rem 1rem 0.65rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
}

@media (max-width: 640px) {
  .portal-bio-row {
    display: block;
  }
  .portal-bio-cell {
    display: block;
    width: 100%;
    border-bottom: none;
  }
  .portal-bio-row .portal-bio-cell:last-child {
    border-bottom: 1px solid #e5e7eb;
    padding-top: 0.35rem;
  }
}
</style>
