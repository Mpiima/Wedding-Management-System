<template>
  <div class="flex min-h-screen bg-[#efefef] lg:flex-row">
    <StudentSidebar :mobile="sidebarOpen" :show-admin-nav="showAdminNav" @close="sidebarOpen = false" />
    <Transition name="overlay">
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
        aria-hidden="true"
        @click="sidebarOpen = false"
      />
    </Transition>
    <div class="flex min-w-0 flex-1 flex-col">
      <header class="sticky top-0 z-20 border-b border-slate-300 bg-white">
        <div class="flex h-8 items-center gap-2 border-b border-slate-200 px-2 sm:px-3">
          <button
            type="button"
            class="tap-highlight-none flex h-6 w-6 items-center justify-center rounded text-slate-600 lg:hidden"
            aria-label="Open menu"
            @click="sidebarOpen = true"
          >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <span class="text-[9px] font-semibold uppercase tracking-wide text-slate-500">Menu</span>

          <div v-if="showAdminNav" class="flex shrink-0 items-center gap-1">
            <router-link
              to="/dashboard"
              class="inline-flex items-center gap-0.5 rounded border border-slate-300 bg-slate-50 px-1.5 py-0.5 text-[9px] font-semibold uppercase text-slate-800 hover:bg-slate-100"
              title="Back to school dashboard"
            >
              <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Admin
            </router-link>
            <router-link
              to="/students"
              class="inline-flex rounded border border-slate-300 bg-white px-1.5 py-0.5 text-[9px] font-semibold uppercase text-slate-700 hover:bg-slate-50"
              title="Students directory"
            >
              Students
            </router-link>
          </div>

          <div class="mx-auto hidden flex-1 items-center justify-center gap-1 text-[9px] sm:flex sm:min-w-0">
            <router-link :to="portalTo('/portal/dashboard')" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold uppercase text-slate-700 hover:bg-slate-50">
              View Pupil Bio
            </router-link>
            <router-link :to="portalTo('/portal/results')" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold uppercase text-slate-700 hover:bg-slate-50">
              View Results
            </router-link>
            <router-link :to="portalTo('/portal/fees')" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold uppercase text-slate-700 hover:bg-slate-50">
              View Fee Statement
            </router-link>
            <router-link :to="portalTo('/portal/fees')" class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold uppercase text-slate-700 hover:bg-slate-50">
              View Fees Structure
            </router-link>
            <button class="rounded border border-slate-300 bg-white px-2 py-0.5 font-semibold uppercase text-slate-700 hover:bg-slate-50">
              Download Report Slip
            </button>
          </div>

          <div class="ml-auto flex items-center gap-1 text-[9px] text-slate-600">
            <span class="h-2 w-2 rounded-full bg-rose-500" />
            <span class="font-semibold uppercase">Hi, {{ shortName }}</span>
            <button
              type="button"
              class="rounded border border-slate-300 bg-white px-1.5 py-0.5 font-semibold uppercase text-slate-700 hover:bg-slate-50"
              @click="handleLogout"
            >
              Logout
            </button>
          </div>
        </div>

        <div class="flex h-7 items-center justify-between bg-[#edf2f5] px-2 text-[9px] text-slate-600 sm:px-3">
          <div class="flex items-center gap-2">
            <span class="font-semibold uppercase text-[#0b5f84]">Class:</span>
            <span class="truncate uppercase">{{ programName }}</span>
            <span class="rounded bg-emerald-600 px-1.5 py-0.5 text-[8px] font-semibold uppercase text-white">Active</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="font-semibold uppercase text-[#0b5f84]">Pupil Status:</span>
            <span class="rounded border border-orange-300 bg-orange-50 px-1.5 py-0.5 uppercase text-[8px] text-orange-700">Promoted</span>
          </div>
        </div>

        <div class="flex h-7 items-center justify-between border-t border-slate-200 bg-[#f7f9fb] px-2 text-[9px] text-slate-700 sm:px-3">
          <div class="flex items-center gap-2">
            <span class="font-semibold uppercase">Academic Yr:</span>
            <span class="rounded bg-[#0b5f84] px-1.5 py-0.5 text-white">{{ currentYear }}</span>
            <span class="font-semibold uppercase">Current Term:</span>
            <span class="rounded bg-[#0b5f84] px-1.5 py-0.5 text-white">{{ currentSemester }}</span>
            <span class="rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 uppercase text-emerald-700">Class Assigned</span>
            <span class="rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 uppercase text-emerald-700">Registered</span>
          </div>
          <div class="flex flex-wrap items-center justify-end gap-1.5 sm:gap-2">
            <span
              class="inline-flex max-w-[min(100%,14rem)] items-baseline gap-1 rounded border border-slate-300 bg-white px-1.5 py-0.5 tabular-nums"
              title="Outstanding fees for the current term (from your latest invoice)"
            >
              <span class="font-semibold uppercase text-slate-600">Fees balance</span>
              <span class="text-slate-900">{{ feesBalanceDisplay }}</span>
            </span>
            <span
              class="inline-flex max-w-[min(100%,14rem)] items-baseline gap-1 rounded bg-[#0b5f84] px-1.5 py-0.5 font-semibold text-white tabular-nums"
              title="Canteen / credit wallet balance when enabled by your school"
            >
              <span class="uppercase text-blue-100">Wallet</span>
              <span>{{ walletBalanceDisplay }}</span>
            </span>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto p-3 sm:p-4">
        <div class="rounded border border-slate-300 bg-white p-3">
          <router-view />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, provide, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { usePortalLink } from '@/composables/usePortalLink'
import { usePortalContext } from '@/composables/usePortalContext'
import { usePortalAdminNav } from '@/composables/usePortalAdminNav'
import { formatCurrency } from '@/utils/formatters'
import StudentSidebar from '@/components/layout/StudentSidebar.vue'

const sidebarOpen = ref(false)
const router = useRouter()
const { user, logout } = useAuth()
const { portalTo } = usePortalLink()
const { showAdminNav } = usePortalAdminNav()

const portal = usePortalContext()
provide('portalContext', portal)

const programName = computed(() => portal.programLabel)
const shortName = computed(() => portal.shortName || user.value?.firstname || 'Student')
const currentYear = computed(() => portal.academicYearLabel)
const currentSemester = computed(() => portal.studyPeriodLabel)

function moneyOrDash(v) {
  if (v == null || v === '') return '—'
  const n = Number(v)
  if (Number.isNaN(n)) return '—'
  return formatCurrency(n)
}

const feesBalanceDisplay = computed(() => moneyOrDash(portal.finance?.feesBalance))
const walletBalanceDisplay = computed(() => moneyOrDash(portal.finance?.walletBalance))

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
