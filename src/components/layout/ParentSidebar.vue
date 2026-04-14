<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 flex h-full w-64 flex-col border-r border-sc-border bg-white transition-transform duration-300 lg:static lg:translate-x-0"
    :class="mobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <div class="flex h-14 items-center justify-between border-b border-sc-border px-4">
      <span class="font-display text-sm font-semibold text-slate-900">Parents</span>
      <button type="button" class="rounded-lg p-2 text-slate-500 lg:hidden" @click="$emit('close')">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
      <router-link
        v-for="item in items"
        :key="item.to"
        :to="item.to"
        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
        :class="
          $route.path === item.to
            ? 'bg-violet-50 text-violet-900 ring-1 ring-violet-500/15'
            : 'text-slate-600 hover:bg-slate-50'
        "
        @click="$emit('close')"
      >
        <component :is="item.icon" class="h-5 w-5 text-slate-400" />
        {{ item.label }}
      </router-link>
    </nav>
  </aside>
</template>

<script setup>
import {
  HomeIcon,
  UserGroupIcon,
  AcademicCapIcon,
  CalendarDaysIcon,
  BanknotesIcon,
  ClockIcon,
  ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline'

defineProps({ mobile: { type: Boolean, default: false } })
defineEmits(['close'])

const items = [
  { label: 'Dashboard', to: '/parent/dashboard', icon: HomeIcon },
  { label: 'My Children', to: '/parent/children', icon: UserGroupIcon },
  { label: 'Results', to: '/parent/results', icon: AcademicCapIcon },
  { label: 'Attendance', to: '/parent/attendance', icon: CalendarDaysIcon },
  { label: 'Fees', to: '/parent/fees', icon: BanknotesIcon },
  { label: 'Timetable', to: '/parent/timetable', icon: ClockIcon },
  { label: 'Messages', to: '/parent/messages', icon: ChatBubbleLeftRightIcon }
]
</script>
