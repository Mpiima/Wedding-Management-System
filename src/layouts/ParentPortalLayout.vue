<template>
  <div class="flex min-h-screen bg-gradient-to-b from-violet-50/80 to-slate-100/90 lg:flex-row">
    <ParentSidebar :mobile="sidebarOpen" @close="sidebarOpen = false" />
    <Transition name="overlay">
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
        aria-hidden="true"
        @click="sidebarOpen = false"
      />
    </Transition>
    <div class="flex min-w-0 flex-1 flex-col">
      <header
        class="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-sc-border bg-white/90 px-4 backdrop-blur-md sm:px-6"
      >
        <button
          type="button"
          class="tap-highlight-none flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 lg:hidden"
          aria-label="Open menu"
          @click="sidebarOpen = true"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <div class="min-w-0 flex-1 text-center lg:flex lg:items-center lg:gap-3 lg:text-left">
          <AppLogo size="sm" class="mx-auto lg:mx-0" />
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Parent portal</p>
            <p class="truncate text-sm font-semibold text-slate-900">SCH PRO 360</p>
          </div>
        </div>
        <div class="flex shrink-0 items-center gap-2">
          <router-link
            to="/portal/dashboard"
            class="tap-highlight-none hidden rounded-xl border border-sc-line bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 sm:inline-flex"
          >
            Student
          </router-link>
          <router-link
            to="/dashboard"
            class="tap-highlight-none rounded-xl border border-sc-line bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
          >
            Admin
          </router-link>
          <button
            type="button"
            class="tap-highlight-none rounded-xl border border-sc-line bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </header>
      <main class="flex-1 overflow-y-auto px-4 py-8 sm:px-6 lg:px-10">
        <div class="mx-auto max-w-3xl">
          <router-view />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import ParentSidebar from '@/components/layout/ParentSidebar.vue'
import AppLogo from '@/components/common/AppLogo.vue'

const sidebarOpen = ref(false)
const router = useRouter()
const { logout } = useAuth()

function handleLogout() {
  logout()
  router.push('/login')
}

watch(sidebarOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})
</script>

<style scoped>
.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.25s ease;
}
.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}
</style>
