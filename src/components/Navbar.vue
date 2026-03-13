<template>
  <header class="flex h-14 shrink-0 items-center justify-between gap-2 border-b border-rose-100/80 bg-white/95 backdrop-blur-sm px-3 sm:px-4 md:px-6 shadow-soft">
    <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
      <button
        type="button"
        class="lg:hidden flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-500 transition-all duration-300 hover:bg-rose-50 hover:text-rose-600"
        aria-label="Open menu"
        @click="$emit('toggle-sidebar')"
      >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
      </button>
      <div class="relative hidden min-w-0 flex-1 sm:block max-w-xs md:max-w-sm">
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search..."
          class="w-full rounded-xl border border-rose-100 bg-rose-50/30 py-2.5 pl-9 pr-4 text-sm placeholder-gray-500 focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 transition-all duration-200"
        />
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        </span>
      </div>
      <!-- Wedding profile summary + countdown -->
      <div class="hidden md:flex items-center gap-2 min-w-0">
        <router-link
          v-if="weddingProfile"
          to="/settings/profile"
          class="flex items-center gap-2 rounded-xl border border-rose-100/80 bg-gradient-to-r from-rose-50/80 to-gold-50/50 px-3 py-2 text-sm transition-all duration-200 hover:border-rose-200 hover:shadow-soft min-w-0"
        >
          <span class="hidden lg:inline truncate text-wmis-text font-medium">{{ weddingSummary }}</span>
          <span class="flex-shrink-0 text-gray-500">·</span>
          <WeddingCountdown :date="weddingProfile.wedding_date" :show-hours="false" :compact="true" />
        </router-link>
        <router-link
          v-else
          to="/settings/profile"
          class="rounded-xl border border-dashed border-rose-200 bg-rose-50/40 px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 transition-all"
        >
          Set wedding date
        </router-link>
      </div>
    </div>
    <div class="flex shrink-0 items-center gap-1 sm:gap-2">
      <div ref="notifRef" class="relative">
        <button
          type="button"
          class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition-all duration-300 hover:bg-rose-50 hover:text-rose-500"
          @click.stop="toggleNotifications"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
          <span v-if="notificationCount > 0" class="absolute right-0 top-0 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-medium text-white ring-2 ring-white">{{ notificationCount > 9 ? '9+' : notificationCount }}</span>
        </button>
        <div
          v-if="showNotifications"
          class="absolute right-0 top-full z-10 mt-1 w-80 max-w-[calc(100vw-2rem)] max-h-[70vh] overflow-auto rounded-2xl border border-rose-100/80 bg-white py-2 shadow-soft-lg"
          @click.stop
        >
          <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Notifications</p>
          <template v-if="notificationItems.length">
            <button
              v-for="n in notificationItems"
              :key="n.id"
              type="button"
              class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm hover:bg-rose-50/50 transition-colors"
              @click="goToNotification(n)"
            >
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm" :class="n.iconBg">{{ n.icon }}</span>
              <div class="min-w-0 flex-1">
                <p class="font-medium text-wmis-text truncate">{{ n.title }}</p>
                <p class="text-xs text-gray-500">{{ n.time }}</p>
              </div>
            </button>
          </template>
          <p v-else class="px-4 py-6 text-sm text-gray-500 text-center">No recent activity</p>
        </div>
      </div>
      <div ref="profileRef" class="relative">
        <button
          type="button"
          class="flex items-center gap-2 rounded-full border border-rose-100 bg-rose-50/40 px-2.5 py-1.5 text-sm transition-all duration-300 hover:bg-rose-50 hover:border-rose-200 min-w-0"
          @click.stop="showProfile = !showProfile; showNotifications = false"
        >
          <span v-if="user?.avatar" class="flex h-8 w-8 shrink-0 rounded-full overflow-hidden bg-rose-100 border border-rose-200/60">
            <img :src="avatarSrc" alt="" class="w-full h-full object-cover" />
          </span>
          <span v-else class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-rose-600 text-sm font-semibold text-white shadow-soft">{{ userInitial }}</span>
          <span class="hidden text-gray-700 md:inline truncate">{{ displayName }}</span>
          <svg class="h-4 w-4 shrink-0 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </button>
        <div
          v-if="showProfile"
          class="absolute right-0 top-full z-10 mt-1 w-48 min-w-[12rem] max-w-[calc(100vw-2rem)] rounded-2xl border border-rose-100/80 bg-white py-2 shadow-soft-lg"
          @click.stop
        >
          <router-link to="/settings/account" class="block px-4 py-2 text-sm text-gray-700 hover:bg-rose-50/50">My account</router-link>
          <router-link to="/settings/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-rose-50/50">Wedding Profile</router-link>
          <router-link to="/settings/system" class="block px-4 py-2 text-sm text-gray-700 hover:bg-rose-50/50">Settings</router-link>
          <button type="button" class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-rose-50/50" @click="handleSignOut">Sign out</button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useWeddingProfileStore } from '@/stores/weddingProfile'
import { useActivityStore } from '@/stores/activity'
import { baseURL } from '@/config/api.js'
import WeddingCountdown from '@/components/WeddingCountdown.vue'

defineEmits(['toggle-sidebar'])

const router = useRouter()
const { user, logout } = useAuth()
const weddingStore = useWeddingProfileStore()
const activityStore = useActivityStore()

const avatarSrc = computed(() => {
  const path = user.value?.avatar
  if (!path) return ''
  const base = (baseURL || '').replace(/\/$/, '')
  return base ? `${base}/${path.replace(/^\//, '')}` : path
})

const weddingProfile = computed(() => weddingStore.profile)
const weddingSummary = computed(() => {
  const p = weddingProfile.value
  if (!p) return ''
  const bride = (p.bride_name || '').trim()
  const groom = (p.groom_name || '').trim()
  if (bride && groom) return `${bride} & ${groom}`
  if (bride) return bride
  if (groom) return groom
  return 'Our wedding'
})

const notificationItems = computed(() => {
  const list = (activityStore.items || []).slice(0, 10)
  const icons = { meeting: '📅', contribution: '💰', pledge: '💵', expenditure: '📤', member: '👤' }
  const iconBg = { meeting: 'bg-amber-100', contribution: 'bg-rose-100', pledge: 'bg-emerald-100', expenditure: 'bg-sky-100', member: 'bg-violet-100' }
  return list.map((a) => ({
    ...a,
    id: `${a.type}-${a.id}`,
    icon: icons[a.type] || '•',
    iconBg: iconBg[a.type] || 'bg-gray-100',
    time: formatNotifDate(a.date || a.created_at)
  }))
})
const notificationCount = computed(() => activityStore.items?.length ?? 0)

function formatNotifDate(iso) {
  if (!iso) return ''
  const d = new Date(iso + (iso.length === 10 ? 'Z' : ''))
  if (isNaN(d.getTime())) return iso
  const now = new Date()
  const diffMs = now - d
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins} min ago`
  if (diffHours < 24) return `${diffHours} hr ago`
  if (diffDays < 7) return `${diffDays} days ago`
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}

function toggleNotifications() {
  showNotifications.value = !showNotifications.value
  showProfile.value = false
  if (showNotifications.value) activityStore.fetchActivity().catch(() => {})
}

function goToNotification(n) {
  showNotifications.value = false
  if (n.type === 'meeting') router.push('/meeting-minutes')
  else if (n.type === 'contribution') router.push('/contributions')
  else if (n.type === 'pledge') router.push('/pledges')
  else if (n.type === 'expenditure') router.push('/expenditures')
  else if (n.type === 'member') router.push('/members')
}

const userInitial = computed(() => {
  const p = user.value
  if (!p) return 'A'
  const name = [p.firstname, p.lastname].filter(Boolean).join(' ').trim() || p.email
  if (name) return name.slice(0, 1).toUpperCase()
  return 'A'
})

const displayName = computed(() => {
  const p = user.value
  if (!p) return 'Profile'
  return [p.firstname, p.lastname].filter(Boolean).join(' ').trim() || p.email || 'Profile'
})

function handleSignOut() {
  logout()
  showProfile.value = false
  router.push('/login')
}

const searchQuery = ref('')
const showNotifications = ref(false)
const showProfile = ref(false)
const notifRef = ref(null)
const profileRef = ref(null)

function closeDropdowns(e) {
  if (notifRef.value?.contains(e.target) || profileRef.value?.contains(e.target)) return
  showNotifications.value = false
  showProfile.value = false
}
onMounted(() => {
  document.addEventListener('click', closeDropdowns)
  activityStore.fetchActivity().catch(() => {})
})
onUnmounted(() => document.removeEventListener('click', closeDropdowns))
</script>
