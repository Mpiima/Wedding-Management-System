<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 flex h-full w-[18rem] flex-col border-r border-sc-border border-l-[3px] border-l-brand-500 bg-white/95 shadow-[4px_0_32px_-12px_rgba(28,45,91,0.08)] backdrop-blur-md transition-transform duration-300 ease-out-expo lg:static lg:w-[17.5rem] lg:translate-x-0 lg:shadow-none"
    :class="[
      mobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      mobile ? '' : 'pointer-events-none lg:pointer-events-auto'
    ]"
  >
    <div class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-sc-border px-5">
      <router-link
        to="/dashboard"
        class="group/logo tap-highlight-none flex min-w-0 flex-1 items-center gap-2 rounded-xl p-0.5 outline-none ring-offset-2 transition-opacity duration-200 ease-out hover:opacity-95 active:scale-[0.99] focus-visible:ring-2 focus-visible:ring-brand-500/35"
        @click="$emit('close')"
      >
        <AppLogo size="md" class="shrink-0" />
        <!-- <div class="min-w-0 leading-tight">
          <span class="block truncate text-2xs font-semibold uppercase tracking-wider text-brand-600">School admin</span>
        </div> -->
      </router-link>
      <button
        type="button"
        class="tap-highlight-none -mr-1 flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-all duration-200 ease-out hover:scale-105 hover:bg-slate-100 hover:text-slate-800 active:scale-95 lg:hidden"
        aria-label="Close menu"
        @click="$emit('close')"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto overscroll-contain px-2 py-4">
      <ul class="space-y-1">
        <template v-for="block in menu" :key="block.id || block.to">
          <!-- Single link -->
          <li v-if="block.type === 'item' && canAccess(block.to)">
            <router-link
              :to="block.to"
              class="group/nav tap-highlight-none flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium leading-snug transition-all duration-200 ease-out"
              :class="isRoute(block.to) ? activeClass : idleClass"
              @click="$emit('close')"
            >
              <component
                :is="block.icon"
                class="h-[1.125rem] w-[1.125rem] shrink-0 stroke-[1.75] transition-transform duration-200 ease-out group-hover/nav:scale-110"
                :class="isRoute(block.to) ? 'text-brand-500' : 'text-slate-400 group-hover/nav:text-slate-600'"
              />
              <span class="truncate">{{ block.label }}</span>
            </router-link>
          </li>

          <!-- Group -->
          <li v-else-if="block.type === 'group'" class="pt-2 first:pt-0">
            <button
              type="button"
              class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wider text-slate-400 transition-colors hover:text-slate-600"
              @click="toggle(block.id)"
            >
              <component :is="block.icon" class="h-4 w-4 text-slate-400" />
              <span class="flex-1 truncate">{{ block.label }}</span>
              <svg
                class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                :class="open[block.id] && 'rotate-180'"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <ul v-show="open[block.id]" class="mt-1 space-y-0.5 border-l border-slate-100 pl-2 ml-4">
              <li v-for="child in block.children" :key="child.to">
                <router-link
                  v-if="canAccess(child.to)"
                  :to="child.to"
                  class="group/nav tap-highlight-none flex items-center rounded-xl px-3 py-2 text-sm font-medium leading-snug transition-all duration-200 ease-out"
                  :class="isRoute(child.to) ? activeClass : idleClass"
                  @click="$emit('close')"
                >
                  <span class="truncate">{{ child.label }}</span>
                </router-link>
              </li>
            </ul>
          </li>
        </template>
      </ul>
    </nav>

    <div class="border-t border-sc-border px-1 py-1" />
  </aside>
</template>

<script setup>
import { reactive } from 'vue'
import { useRoute } from 'vue-router'
import AppLogo from '@/components/common/AppLogo.vue'
import {
  HomeIcon,
  UserGroupIcon,
  BookOpenIcon,
  ClipboardDocumentListIcon,
  BanknotesIcon,
  CalendarDaysIcon,
  UsersIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
  Cog6ToothIcon,
  TableCellsIcon,
  AcademicCapIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { ROUTE_PERMISSIONS } from '@/config/permissions'

defineProps({
  mobile: { type: Boolean, default: false }
})

defineEmits(['close'])

const route = useRoute()
const authStore = useAuthStore()

const activeClass =
  'bg-brand-50 text-brand-900 shadow-sm ring-1 ring-brand-500/25'
const idleClass =
  'text-slate-600 hover:translate-x-0.5 hover:bg-slate-50 hover:text-slate-900'

const menu = [
  { type: 'item', id: 'dash', label: 'Dashboard', to: '/dashboard', icon: HomeIcon },
  {
    type: 'group',
    id: 'students',
    label: 'Students',
    icon: UserGroupIcon,
    children: [{ label: 'All Students', to: '/students' }]
  },
  {
    type: 'group',
    id: 'admissions',
    label: 'Admissions',
    icon: AcademicCapIcon,
    children: [
      { label: 'Applicants', to: '/admissions/applicants' },
      { label: 'Enrollments', to: '/admissions/enrollments' }
    ]
  },
  {
    type: 'group',
    id: 'academics',
    label: 'Academics',
    icon: BookOpenIcon,
    children: [
      { label: 'Academic setup', to: '/academics/erp-setup' },
      // { label: 'Classes', to: '/academics/classes' },
      // { label: 'Subjects', to: '/academics/subjects' },
      // { label: 'Teachers', to: '/academics/teachers' }
    ]
  },
  {
    type: 'group',
    id: 'examinations',
    label: 'Examinations',
    icon: ClipboardDocumentListIcon,
    children: [
      { label: 'Exam types', to: '/examinations/exam-types' },
      { label: 'Exam schedules', to: '/examinations/exam-schedules' },
      // { label: 'Exams', to: '/examinations/exams' },
      { label: 'Marks Entry', to: '/examinations/marks-entry' },
      { label: 'Score sheet', to: '/examinations/score-sheet' },
      { label: 'Results', to: '/examinations/results' },
      { label: 'Exam Permits', to: '/examinations/exam-permits' }
    ]
  },
  {
    type: 'group',
    id: 'finance',
    label: 'Finance',
    icon: BanknotesIcon,
    children: [
      { label: 'Overview', to: '/finance/dashboard' },
      { label: 'Student fees', to: '/finance/student-fees' },
      { label: 'Fee structures', to: '/finance/fee-structure' },
      { label: 'Invoices', to: '/finance/invoices' },
      { label: 'Payments', to: '/finance/payments' }
    ]
  },
  {
    type: 'group',
    id: 'canteen',
    label: 'Canteen',
    icon: BanknotesIcon,
    children: [
      { label: 'Dashboard', to: '/canteen/dashboard' },
      { label: 'Items', to: '/canteen/items' },
      { label: 'Transactions', to: '/canteen/transactions' }
    ]
  },
  {
    type: 'group',
    id: 'attendance',
    label: 'Attendance',
    icon: CalendarDaysIcon,
    children: [
      { label: 'School Attendance', to: '/attendance/school-general' },
      { label: 'Class Attendance', to: '/attendance' },
      { label: 'Attendance Reports', to: '/reports' }
    ]
  },
  {
    type: 'group',
    id: 'hrpayroll',
    label: 'HR & Payroll',
    icon: UsersIcon,
    children: [
      { label: 'Staff', to: '/hr-payroll/staff' },
      { label: 'None Staff', to: '/hr-payroll/none-staff' },
      { label: 'Teachers', to: '/hr-payroll/teachers' },
      { label: 'Work Attendance', to: '/hr-payroll/work-attendance' },
      { label: 'PayRoll', to: '/hr-payroll/payroll' },
      { label: 'Leave Management', to: '/hr-payroll/leave-management' }
    ]
  },
  // { type: 'item', id: 'ten', label: 'Tenants', to: '/tenants', icon: TableCellsIcon },
  { type: 'item', id: 'rep', label: 'Reports', to: '/reports', icon: ChartBarIcon },
  // { type: 'item', id: 'com', label: 'Communication', to: '/communication', icon: ChatBubbleLeftRightIcon },
  { type: 'item', id: 'usr', label: 'Users', to: '/users', icon: UsersIcon },
  { type: 'item', id: 'set', label: 'Settings', to: '/settings', icon: Cog6ToothIcon }
]

const open = reactive({
  students: true,
  admissions: true,
  academics: true,
  examinations: true,
  finance: true,
  canteen: true,
  attendance: true,
  hrpayroll: true
})

function toggle(id) {
  open[id] = !open[id]
}

function canAccess(path) {
  const perm = ROUTE_PERMISSIONS[path]
  if (perm == null) return true
  return authStore.can(perm)
}

function isRoute(path) {
  return route.path === path
}
</script>
