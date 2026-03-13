<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 flex h-full w-64 flex-col border-r border-rose-100/80 bg-gradient-to-b from-white to-rose-50/30 transition-transform duration-300 ease-out lg:static lg:translate-x-0 lg:!transition-none"
    :class="[mobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', mobile ? '' : 'pointer-events-none lg:pointer-events-auto']"
  >
    <div class="flex h-14 shrink-0 items-center justify-between gap-2 border-b border-rose-100/60 px-4">
      <div class="flex items-center gap-2">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 text-white text-lg font-display font-semibold shadow-soft">
          ♥
        </span>
        <span class="font-display text-lg font-semibold tracking-tight text-wmis-text">WMIS</span>
      </div>
      <button
        type="button"
        class="lg:hidden flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition-colors duration-200 hover:bg-rose-50 hover:text-rose-600 -mr-1"
        aria-label="Close menu"
        @click="$emit('close')"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>
    <nav class="flex-1 overflow-y-auto py-4 overscroll-contain">
      <router-link
        to="/"
        class="mx-2 mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-300 ease-luxury"
        :class="isActive('/') && route.path === '/' ? 'bg-gradient-to-r from-rose-50 to-rose-100/50 text-rose-700 border border-rose-200/50' : 'text-gray-600 hover:bg-rose-50/60 hover:text-rose-600 border border-transparent'"
        @click="$emit('close')"
      >
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100/80 text-rose-500">📊</span>
        <span class="truncate">Dashboard</span>
      </router-link>

      <template v-for="group in menuGroups" :key="group.label">
        <template v-if="filterByPermission(group.items).length > 0">
          <p class="px-4 pt-5 pb-1.5 text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-400">
            {{ group.label }}
          </p>
          <router-link
            v-for="item in filterByPermission(group.items)"
            :key="item.to"
          :to="item.to"
          class="mx-2 mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-300 ease-luxury border border-transparent"
          :class="isActive(item.to) ? 'bg-gradient-to-r from-rose-50 to-rose-100/50 text-rose-700 border-rose-200/50' : 'text-gray-600 hover:bg-rose-50/60 hover:text-rose-600'"
          @click="$emit('close')"
        >
          <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100/80 text-gray-500 transition-colors duration-300 group-hover:bg-gold-100/60 group-hover:text-gold-600"> {{ item.icon }} </span>
          <span class="truncate">{{ item.name }}</span>
        </router-link>
      </template>
      </template>
    </nav>
  </aside>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ROUTE_PERMISSIONS } from '@/config/permissions'

const route = useRoute()
const authStore = useAuthStore()

defineProps({
  mobile: { type: Boolean, default: false }
})

defineEmits(['close'])

function can(perm) {
  return authStore.can(perm)
}

function filterByPermission(items) {
  return items.filter((item) => {
    const perm = ROUTE_PERMISSIONS[item.to]
    if (perm == null) return true
    return can(perm)
  })
}

const menuGroups = [
  {
    label: 'Wedding Planning',
    items: [
      { name: 'Budget', to: '/budget', icon: '💰' },
      { name: 'Expenditures', to: '/expenditures', icon: '📤' },
      { name: 'Contributions', to: '/contributions', icon: '🏦' },
      { name: 'Pledges', to: '/pledges', icon: '📋' }
    ]
  },
  {
    label: 'People Management',
    items: [
      { name: 'Members', to: '/members', icon: '👥' },
      { name: 'Group Category', to: '/group-category', icon: '📁' },
      { name: 'Roles', to: '/roles', icon: '🧩' },
      { name: 'Committee', to: '/committee', icon: '🏛️' },
      { name: 'Invited Guests', to: '/guests', icon: '👫' }
    ]
  },
  {
    label: 'Events',
    items: [
      { name: 'Meetings', to: '/meetings', icon: '📅' },
      { name: 'Meeting Minutes', to: '/meeting-minutes', icon: '📝' },
      { name: 'Wedding Program', to: '/wedding-program', icon: '📜' }
    ]
  },
  {
    label: 'Vendors',
    items: [
      { name: 'Service Providers', to: '/vendors', icon: '🤝' },
      { name: 'Vendor Contracts', to: '/vendor-contracts', icon: '📄' }
    ]
  },
  {
    label: 'Communication',
    items: [
      { name: 'Email Notifications', to: '/email-notifications', icon: '✉️' },
      { name: 'SMS Notifications', to: '/sms-notifications', icon: '📱' }
    ]
  },
  {
    label: 'Media',
    items: [
      { name: 'Wedding Gallery', to: '/media', icon: '🖼' },
      { name: 'Upload Photos', to: '/upload-media', icon: '⬆' }
    ]
  },
  {
    label: 'Reports',
    items: [
      { name: 'Financial Reports', to: '/reports/financial', icon: '📈' },
      { name: 'Contributions Report', to: '/reports/contributions', icon: '📊' },
      { name: 'Guest Report', to: '/reports/guests', icon: '👥' }
    ]
  },
  {
    label: 'Settings',
    items: [
      { name: 'My account', to: '/settings/account', icon: '👤' },
      { name: 'Wedding Profile', to: '/settings/profile', icon: '💒' },
      { name: 'Email configuration', to: '/settings/email', icon: '✉️' },
      { name: 'System Settings', to: '/settings/system', icon: '⚙️' }
    ]
  }
]

function isActive(path) {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>
