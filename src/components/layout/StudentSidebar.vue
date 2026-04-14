<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 flex h-full w-60 flex-col border-r border-slate-300 bg-[#f2f3f5] transition-transform duration-300 lg:static lg:translate-x-0"
    :class="mobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <div class="flex h-10 items-center justify-between border-b border-slate-300 px-3">
      <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Student portal</span>
      <button type="button" class="rounded p-1 text-slate-500 lg:hidden" @click="$emit('close')">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="border-b border-slate-300 px-3 py-3">
      <div class="rounded bg-[#163f67] p-2 text-white">
        <div
          class="mx-auto mb-2 flex h-14 w-14 items-center justify-center overflow-hidden rounded border border-white/30 bg-slate-200 text-xs font-bold text-slate-700"
        >
          <img v-if="photoUrl" :src="photoUrl" alt="" class="h-full w-full object-cover" />
          <span v-else>{{ avatarInitials }}</span>
        </div>
        <p class="truncate text-center text-[11px] font-semibold uppercase">{{ displayName }}</p>
        <p class="truncate text-center text-[10px] text-blue-100">{{ displayMeta }}</p>
      </div>
    </div>

    <div v-if="showAdminNav" class="border-b border-slate-300 bg-slate-100/90 px-3 py-2.5">
      <p class="mb-2 text-[9px] font-semibold uppercase tracking-wide text-slate-500">School admin</p>
      <div class="flex flex-col gap-1.5">
        <router-link
          to="/dashboard"
          class="flex items-center gap-2 rounded-lg border border-slate-300/80 bg-white px-2.5 py-2 text-[11px] font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
          @click="$emit('close')"
        >
          <svg class="h-4 w-4 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Back to dashboard</span>
        </router-link>
        <router-link
          to="/students"
          class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-[11px] font-medium text-[#0b5f84] hover:bg-white/60"
          @click="$emit('close')"
        >
          <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Students directory
        </router-link>
        <router-link
          to="/admissions/enrollments"
          class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-[11px] font-medium text-[#0b5f84] hover:bg-white/60"
          @click="$emit('close')"
        >
          <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Enrollments
        </router-link>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-1">
      <template v-for="section in menuSections" :key="section.id">
        <!-- Collapsible group -->
        <div v-if="section.type === 'group'" class="border-b border-slate-200/80">
          <button
            type="button"
            class="flex w-full items-center justify-between px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-700 hover:bg-slate-100"
            @click="toggle(section.id)"
          >
            <span>{{ section.label }}</span>
            <svg
              class="h-3.5 w-3.5 shrink-0 text-slate-500 transition-transform"
              :class="open[section.id] ? 'rotate-180' : ''"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <ul v-show="open[section.id]" class="pb-1">
            <li v-for="item in section.items" :key="item.to">
              <router-link
                :to="portalTo(item.to)"
                class="flex items-center gap-2 px-4 py-2 text-[11px] text-slate-700 transition-colors"
                :class="isActive(item.to) ? 'bg-white font-semibold text-[#0b5f84]' : 'hover:bg-slate-100'"
                @click="$emit('close')"
              >
                <component :is="item.icon" class="h-3.5 w-3.5 shrink-0 text-slate-500" />
                <span class="truncate">{{ item.label }}</span>
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Single full-width link (no accordion) -->
        <div v-else-if="section.type === 'link'" class="border-b border-slate-200/80">
          <router-link
            :to="portalTo(section.to)"
            class="flex items-center gap-2 px-4 py-2.5 text-[11px] font-medium text-slate-700 transition-colors"
            :class="isActive(section.to) ? 'bg-white font-semibold text-[#0b5f84]' : 'hover:bg-slate-100'"
            @click="$emit('close')"
          >
            <component :is="section.icon" class="h-3.5 w-3.5 shrink-0 text-slate-500" />
            <span class="truncate">{{ section.label }}</span>
          </router-link>
        </div>
      </template>
    </nav>
  </aside>
</template>

<script setup>
import { computed, inject, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { usePortalLink } from '@/composables/usePortalLink'
import {
  AcademicCapIcon,
  ArrowPathIcon,
  BanknotesIcon,
  BellIcon,
  BuildingLibraryIcon,
  CalendarDaysIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  CalendarIcon,
  LockClosedIcon,
  QrCodeIcon,
  ReceiptPercentIcon,
  ShieldCheckIcon,
  ShoppingBagIcon,
  TableCellsIcon,
  UserCircleIcon,
  UserPlusIcon,
  WalletIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  mobile: { type: Boolean, default: false },
  showAdminNav: { type: Boolean, default: false }
})
defineEmits(['close'])

const route = useRoute()
const { user } = useAuth()
const { portalTo } = usePortalLink()
const portal = inject('portalContext', null)

const photoUrl = computed(() => portal?.photoUrl || '')

const displayName = computed(() => {
  const n = portal?.displayName
  if (n) return n
  const p = user.value || {}
  return String(p.name || p.email || 'Student')
})

const displayMeta = computed(() => {
  const adm = portal?.student?.admission_number
  if (adm) return `Adm. ${adm}`
  const p = user.value || {}
  return String(p.email || `Tenant ${p.tenant_id ?? '—'}`)
})

const avatarInitials = computed(() => {
  const n = displayName.value.trim().split(/\s+/).filter(Boolean)
  if (!n.length) return 'STU'
  if (n.length === 1) return n[0].slice(0, 2).toUpperCase()
  return (n[0][0] + n[n.length - 1][0]).toUpperCase()
})

function isActive(to) {
  const path = route.path.replace(/\/$/, '') || '/'
  const target = to.replace(/\/$/, '') || '/'
  return path === target
}

/** Order matches product spec: Finance → Admission → Programme → Canteen → singles → Account */
const allMenuSections = [
  {
    id: 'finance',
    type: 'group',
    label: 'Finance Clearance',
    items: [
      { label: 'Generate PRN', to: '/portal/finance/prn', icon: QrCodeIcon },
      { label: 'Fees', to: '/portal/fees', icon: BanknotesIcon },
      { label: 'School Requirements', to: '/portal/finance/requirements', icon: ClipboardDocumentListIcon },
      { label: 'Fees Structure', to: '/portal/finance/structure', icon: TableCellsIcon },
      { label: 'My Invoice History', to: '/portal/finance/invoices', icon: DocumentTextIcon },
      { label: 'Payment Plan', to: '/portal/finance/payment-plan', icon: CalendarDaysIcon }
    ]
  },
  {
    id: 'admissions',
    type: 'group',
    label: 'Admission & Registration',
    items: [
      { label: 'Apply or Register', to: '/portal/admissions/apply', icon: UserPlusIcon },
      { label: 'Admission History', to: '/portal/admissions/history', icon: ArrowPathIcon },
      { label: 'Enrollment History', to: '/portal/admissions/enrollment-history', icon: BuildingLibraryIcon }
    ]
  },
  {
    id: 'programme',
    type: 'group',
    label: 'My Programme',
    items: [
      { label: 'My Results', to: '/portal/results', icon: AcademicCapIcon },
      { label: 'My Provisional Results', to: '/portal/results/provisional', icon: DocumentTextIcon },
      { label: 'Teaching Timetable', to: '/portal/timetable', icon: ClockIcon },
      { label: 'Exam Permit', to: '/portal/exam-permit', icon: ShieldCheckIcon },
      { label: 'Meal Card', to: '/portal/meal-card', icon: ShoppingBagIcon }
    ]
  },
  {
    id: 'canteen',
    type: 'group',
    label: 'Canteen Services',
    items: [
      { label: 'Expenditure History', to: '/portal/canteen/expenditure', icon: ReceiptPercentIcon },
      { label: 'Canteen Account', to: '/portal/canteen/account', icon: WalletIcon }
    ]
  },
  { id: 'bio', type: 'link', label: 'Bio Data', to: '/portal/bio-data', icon: UserCircleIcon },
  { id: 'notifications', type: 'link', label: 'My Notifications', to: '/portal/notifications', icon: BellIcon },
  { id: 'calendar', type: 'link', label: 'Academic Calendar', to: '/portal/calendar', icon: CalendarIcon },
  { id: 'attendance', type: 'link', label: 'Attendance History', to: '/portal/attendance', icon: CalendarDaysIcon },
  { id: 'discipline', type: 'link', label: 'Discipline Report', to: '/portal/discipline', icon: ExclamationTriangleIcon },
  {
    id: 'account',
    type: 'group',
    label: 'Account',
    items: [{ label: 'Change password', to: '/portal/password', icon: LockClosedIcon }]
  }
]

const menuSections = computed(() => {
  if (!props.showAdminNav) return allMenuSections
  return allMenuSections.filter((s) => s.id !== 'account')
})

/** Collapsible groups start closed by default */
const open = reactive({
  finance: false,
  admissions: false,
  programme: false,
  canteen: false,
  account: false
})

function toggle(id) {
  open[id] = !open[id]
}
</script>
