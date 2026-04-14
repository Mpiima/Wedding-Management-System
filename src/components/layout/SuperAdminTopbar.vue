<template>
  <header
    class="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-sc-border bg-white/80 px-4 backdrop-blur-md sm:px-6"
  >
    <button
      type="button"
      class="tap-highlight-none flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 transition-all duration-200 ease-out hover:scale-105 hover:bg-slate-100 hover:text-slate-900 lg:hidden"
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
        placeholder="Search schools, payments…"
        class="peer h-10 w-full rounded-xl border border-sc-line bg-slate-50/80 py-2 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition-all duration-200 ease-out hover:border-slate-300 hover:bg-white focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
      />
      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
        <svg class="h-[1.125rem] w-[1.125rem]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </span>
    </div>

    <div class="flex shrink-0 items-center gap-2">
      <button
        type="button"
        class="tap-highlight-none flex min-w-0 items-center gap-2.5 rounded-full border border-sc-line bg-slate-50/90 px-3 py-1.5 text-sm shadow-sm transition-all duration-200 ease-out hover:border-slate-300 hover:bg-white hover:shadow-md active:scale-[0.98]"
        @click="handleSignOut"
      >
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-semibold text-white shadow-sm ring-2 ring-white/20">
          {{ userInitial }}
        </span>
        <span class="hidden min-w-0 truncate font-medium text-slate-800 md:inline">{{ displayName }}</span>
        <span class="text-xs font-semibold text-slate-600">Sign out</span>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

defineEmits(['toggle-sidebar'])

const router = useRouter()
const { user, logout } = useAuth()

const searchQuery = ref('')

const displayName = computed(() => {
  const p = user.value
  if (!p) return 'SuperAdmin'
  return [p.firstname, p.lastname].filter(Boolean).join(' ').trim() || p.email || 'SuperAdmin'
})

const userInitial = computed(() => {
  const n = displayName.value.trim()
  return (n[0] || 'S').toUpperCase()
})

function handleSignOut() {
  logout()
  router.push('/login')
}
</script>

