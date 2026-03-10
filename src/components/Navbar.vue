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
    </div>
    <div class="flex shrink-0 items-center gap-1 sm:gap-2">
      <div ref="notifRef" class="relative">
        <button
          type="button"
          class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition-all duration-300 hover:bg-rose-50 hover:text-rose-500"
          @click.stop="showNotifications = !showNotifications; showProfile = false"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
          <span class="absolute right-0 top-0 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-medium text-white ring-2 ring-white">3</span>
        </button>
        <div
          v-if="showNotifications"
          class="absolute right-0 top-full z-10 mt-1 w-72 max-w-[calc(100vw-2rem)] rounded-2xl border border-rose-100/80 bg-white py-2 shadow-soft-lg"
          @click.stop
        >
          <p class="px-4 py-2 text-xs font-semibold text-gray-500">Notifications</p>
          <button type="button" class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm hover:bg-rose-50/50">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-500 text-xs">💰</span>
            <div><p class="font-medium text-wmis-text">New contribution received</p><p class="text-xs text-gray-500">2 min ago</p></div>
          </button>
          <button type="button" class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm hover:bg-rose-50/50">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold-100 text-gold-600 text-xs">📅</span>
            <div><p class="font-medium text-wmis-text">Meeting tomorrow</p><p class="text-xs text-gray-500">1 hr ago</p></div>
          </button>
        </div>
      </div>
      <div ref="profileRef" class="relative">
        <button
          type="button"
          class="flex items-center gap-2 rounded-full border border-rose-100 bg-rose-50/40 px-2.5 py-1.5 text-sm transition-all duration-300 hover:bg-rose-50 hover:border-rose-200 min-w-0"
          @click.stop="showProfile = !showProfile; showNotifications = false"
        >
          <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-rose-600 text-sm font-semibold text-white shadow-soft">A</span>
          <span class="hidden text-gray-700 md:inline truncate">Profile</span>
          <svg class="h-4 w-4 shrink-0 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </button>
        <div
          v-if="showProfile"
          class="absolute right-0 top-full z-10 mt-1 w-48 min-w-[12rem] max-w-[calc(100vw-2rem)] rounded-2xl border border-rose-100/80 bg-white py-2 shadow-soft-lg"
          @click.stop
        >
          <router-link to="/settings/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-rose-50/50">Wedding Profile</router-link>
          <router-link to="/settings/system" class="block px-4 py-2 text-sm text-gray-700 hover:bg-rose-50/50">Settings</router-link>
          <button type="button" class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-rose-50/50">Sign out</button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

defineEmits(['toggle-sidebar'])

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
onMounted(() => document.addEventListener('click', closeDropdowns))
onUnmounted(() => document.removeEventListener('click', closeDropdowns))
</script>
