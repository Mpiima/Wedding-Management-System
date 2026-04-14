<template>
  <header
    class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-4 border-b border-sc-border bg-white/80 px-4 shadow-[0_1px_0_0_rgba(15,23,42,0.04)] backdrop-blur-xl backdrop-saturate-150 sm:px-6 lg:px-8"
  >
    <div class="flex min-w-0 flex-1 items-center gap-3">
      <button
        type="button"
        class="tap-highlight-none flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-600 transition-all duration-200 ease-out hover:scale-105 hover:bg-slate-100 hover:text-slate-900 active:scale-95 lg:hidden"
        aria-label="Open menu"
        @click="$emit('toggle-sidebar')"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <div class="relative hidden min-w-0 flex-1 sm:block lg:max-w-md">
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search records…"
          class="peer h-10 w-full rounded-xl border border-sc-line bg-slate-50/80 py-2 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition-all duration-200 ease-out hover:border-slate-300 hover:bg-white focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/15"
        />
        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 transition-colors duration-200 ease-out peer-focus:text-brand-500">
          <svg class="h-[1.125rem] w-[1.125rem]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </span>
      </div>
    </div>
    <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
      <div ref="notifRef" class="relative">
        <button
          type="button"
          class="tap-highlight-none relative flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition-all duration-200 ease-out hover:scale-105 hover:bg-slate-100 hover:text-brand-600 active:scale-95"
          @click.stop="toggleNotifications"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span
            v-if="notificationCount > 0"
            class="absolute right-1.5 top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-brand-600 px-0.5 text-[10px] font-semibold text-white ring-2 ring-white transition-transform duration-200 ease-out"
          >
            {{ notificationCount > 9 ? '9+' : notificationCount }}
          </span>
        </button>
        <div
          v-if="showNotifications"
          class="absolute right-0 top-full z-20 mt-2 max-h-[70vh] w-[min(100vw-2rem,20rem)] origin-top-right animate-fade-in overflow-auto rounded-2xl border border-sc-border bg-white/95 py-2 shadow-soft-lg backdrop-blur-xl"
          @click.stop
        >
          <p class="px-4 pb-2 pt-1 text-2xs font-semibold uppercase tracking-wider text-slate-400">Notifications</p>
          <template v-if="notificationItems.length">
            <button
              v-for="n in notificationItems"
              :key="n.id"
              type="button"
              class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors duration-150 ease-out first:rounded-t-xl last:rounded-b-xl hover:bg-slate-50 active:bg-slate-100/80"
              @click="dismiss(n.id)"
            >
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium leading-snug text-slate-900">{{ n.title }}</p>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ n.detail }}</p>
                <p class="mt-1.5 text-2xs font-medium uppercase tracking-wide text-slate-400">{{ n.time }}</p>
              </div>
            </button>
          </template>
          <p v-else class="px-4 py-8 text-center text-sm text-slate-500">No notifications</p>
        </div>
      </div>
      <div ref="profileRef" class="relative">
        <button
          type="button"
          class="group tap-highlight-none flex min-w-0 max-w-[12rem] items-center gap-2.5 rounded-full border border-sc-line bg-slate-50/90 py-1.5 pl-1.5 pr-3 text-sm shadow-sm transition-all duration-200 ease-out hover:border-slate-300 hover:bg-white hover:shadow-md active:scale-[0.98]"
          @click.stop="showProfile = !showProfile; showNotifications = false"
        >
          <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-semibold text-white shadow-sm ring-2 ring-white/20 transition-transform duration-200 ease-out group-hover:scale-105"
          >
            {{ userInitial }}
          </span>
          <span class="hidden min-w-0 truncate font-medium text-slate-800 md:inline">{{ displayName }}</span>
          <svg class="hidden h-4 w-4 shrink-0 text-slate-400 md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div
          v-if="showProfile"
          class="absolute right-0 top-full z-20 mt-2 w-52 min-w-[13rem] max-w-[calc(100vw-2rem)] origin-top-right animate-fade-in overflow-hidden rounded-2xl border border-sc-border bg-white/95 py-1.5 shadow-soft-lg backdrop-blur-xl"
          @click.stop
        >
          <router-link
            to="/settings"
            class="block px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors duration-150 ease-out hover:bg-slate-50 active:bg-slate-100/80"
            @click="showProfile = false"
          >
            Settings
          </router-link>
          <router-link
            to="/portal/dashboard"
            class="block px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors duration-150 ease-out hover:bg-slate-50 active:bg-slate-100/80"
            @click="showProfile = false"
          >
            ◆ Open student portal
          </router-link>
          <router-link
            to="/parent/dashboard"
            class="block px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors duration-150 ease-out hover:bg-slate-50 active:bg-slate-100/80"
            @click="showProfile = false"
          >
            ◇ Open parent portal
          </router-link>
          <router-link
            to="/super-admin/dashboard"
            class="block px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors duration-150 ease-out hover:bg-slate-50 active:bg-slate-100/80"
            @click="showProfile = false"
          >
            ★ Open super admin
          </router-link>
          <div class="my-1 border-t border-sc-border" />
          <button
            type="button"
            class="w-full px-4 py-2.5 text-left text-sm font-medium text-slate-700 transition-colors duration-150 ease-out hover:bg-slate-50 active:bg-slate-100/80"
            @click="handleSignOut"
          >
            Sign out
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuth } from '@/composables/useAuth'
import { useUIStore } from '@/stores/ui'

defineEmits(['toggle-sidebar'])

const router = useRouter()
const { user, logout } = useAuth()
const uiStore = useUIStore()
const { notifications, notificationCount } = storeToRefs(uiStore)

const notificationItems = computed(() => notifications.value)

const userInitial = computed(() => {
  const p = user.value
  if (!p) return 'U'
  const fullName = [p.firstname, p.lastname].filter(Boolean).join(' ').trim()
  const fallback = String(p.name || p.email || 'User').trim()
  const safeName = fullName || fallback
  return safeName.slice(0, 1).toUpperCase()
})

const displayName = computed(() => {
  const p = user.value
  if (!p) return 'User'
  return [p.firstname, p.lastname].filter(Boolean).join(' ').trim() || p.name || p.email || 'User'
})

const searchQuery = ref('')
const showNotifications = ref(false)
const showProfile = ref(false)
const notifRef = ref(null)
const profileRef = ref(null)

function toggleNotifications() {
  showNotifications.value = !showNotifications.value
  showProfile.value = false
}

function dismiss(id) {
  uiStore.dismissNotification(id)
}

function handleSignOut() {
  logout()
  showProfile.value = false
  router.push('/login')
}

function closeDropdowns(e) {
  if (notifRef.value?.contains(e.target) || profileRef.value?.contains(e.target)) return
  showNotifications.value = false
  showProfile.value = false
}

onMounted(() => document.addEventListener('click', closeDropdowns))
onUnmounted(() => document.removeEventListener('click', closeDropdowns))
</script>
