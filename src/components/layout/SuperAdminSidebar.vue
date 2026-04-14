<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 flex h-full w-[18rem] flex-col border-r border-sc-border border-l-[3px] border-l-brand-500 bg-white/95 backdrop-blur-md shadow-[4px_0_32px_-12px_rgba(28,45,91,0.08)] transition-transform duration-300 ease-out lg:static lg:translate-x-0 lg:shadow-none"
    :class="mobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <div class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-sc-border px-5">
      <router-link
        to="/super-admin/dashboard"
        class="flex min-w-0 items-center gap-2 rounded-xl p-0.5 transition-opacity hover:opacity-95"
        @click="$emit('close')"
      >
        <AppLogo size="md" class="shrink-0" />
        <div class="min-w-0 leading-tight">
          <span class="block truncate font-display text-[0.9375rem] font-semibold tracking-tight text-slate-900"
            >Super Admin</span
          >
          <span class="block truncate text-2xs font-medium uppercase tracking-wider text-brand-600">SaaS console</span>
        </div>
      </router-link>

      <button
        type="button"
        class="tap-highlight-none -mr-1 flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-all duration-200 ease-out hover:scale-105 hover:bg-slate-100 hover:text-slate-800 lg:hidden"
        aria-label="Close menu"
        @click="$emit('close')"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-2 py-4">
      <ul class="space-y-1">
        <li v-for="item in items" :key="item.to">
          <router-link
            :to="item.to"
            class="group/nav tap-highlight-none flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium leading-snug transition-all duration-200 ease-out"
            :class="isActive(item.to) ? activeClass : idleClass"
            @click="$emit('close')"
          >
            <component
              :is="item.icon"
              class="h-[1.125rem] w-[1.125rem] shrink-0 stroke-[1.75] transition-transform duration-200 ease-out group-hover/nav:scale-110"
              :class="isActive(item.to) ? 'text-brand-500' : 'text-slate-400 group-hover/nav:text-slate-600'"
            />
            <span class="truncate">{{ item.label }}</span>
          </router-link>
        </li>
      </ul>
    </nav>
  </aside>
</template>

<script setup>
import { useRoute } from 'vue-router'
import AppLogo from '@/components/common/AppLogo.vue'
import {
  HomeIcon,
  AcademicCapIcon,
  BanknotesIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
  Cog6ToothIcon,
  KeyIcon
} from '@heroicons/vue/24/outline'

defineProps({
  mobile: { type: Boolean, default: false }
})

defineEmits(['close'])

const route = useRoute()

const activeClass = 'bg-brand-50 text-brand-900 shadow-sm ring-1 ring-brand-500/25'
const idleClass = 'text-slate-600 hover:translate-x-0.5 hover:bg-slate-50 hover:text-slate-900'

const items = [
  { label: 'Dashboard', to: '/super-admin/dashboard', icon: HomeIcon },
  { label: 'Schools', to: '/super-admin/schools', icon: AcademicCapIcon },
  { label: 'Subscriptions', to: '/super-admin/subscriptions', icon: BanknotesIcon },
  { label: 'Roles & Permissions', to: '/super-admin/roles-permissions', icon: KeyIcon },
  { label: 'Payments', to: '/super-admin/payments', icon: BanknotesIcon },
  { label: 'Analytics', to: '/super-admin/analytics', icon: ChartBarIcon },
  { label: 'Support', to: '/super-admin/support', icon: ChatBubbleLeftRightIcon },
  { label: 'Settings', to: '/super-admin/settings', icon: Cog6ToothIcon }
]

function isActive(to) {
  return route.path === to
}
</script>

