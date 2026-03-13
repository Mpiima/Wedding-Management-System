<template>
  <div class="space-y-8">
    <div class="flex flex-col gap-1">
      <h1 class="font-display text-2xl font-semibold tracking-tight text-wmis-text">Dashboard</h1>
      <p class="text-sm text-gray-500">Wedding overview and recent activity</p>
    </div>

    <!-- Loading state -->
    <div v-if="dashboardLoading" class="flex flex-col items-center justify-center py-16 gap-6">
      <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
        <svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
      </div>
      <p class="text-sm font-medium text-gray-600">Loading dashboard…</p>
      <div class="flex gap-2">
        <div class="h-2 w-2 rounded-full bg-rose-400 animate-bounce" style="animation-delay: 0ms" />
        <div class="h-2 w-2 rounded-full bg-rose-400 animate-bounce" style="animation-delay: 150ms" />
        <div class="h-2 w-2 rounded-full bg-rose-400 animate-bounce" style="animation-delay: 300ms" />
      </div>
    </div>

    <template v-else>
    <div
      v-if="weddingProfile"
      class="rounded-2xl border border-rose-100/60 bg-gradient-to-br from-white via-rose-50/30 to-gold-50/20 p-6 shadow-card overflow-hidden"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Photos: couple (or bride + groom) -->
        <div class="flex items-center gap-3 shrink-0">
          <div v-if="weddingProfile.couple_photo" class="h-24 w-24 rounded-2xl border-2 border-rose-200/80 overflow-hidden shadow-inner-gold flex-shrink-0">
            <img :key="`couple-${weddingProfile.updated_at}-${weddingProfile.couple_photo}`" :src="photoUrl(weddingProfile.couple_photo, weddingProfile.updated_at)" alt="Couple" class="w-full h-full object-cover" />
          </div>
          <template v-else>
            <div v-if="weddingProfile.bride_photo" class="h-24 w-24 rounded-2xl border-2 border-rose-200/80 overflow-hidden shadow-inner-gold flex-shrink-0">
              <img :key="`bride-${weddingProfile.updated_at}-${weddingProfile.bride_photo}`" :src="photoUrl(weddingProfile.bride_photo, weddingProfile.updated_at)" alt="Bride" class="w-full h-full object-cover" />
            </div>
            <div v-if="weddingProfile.groom_photo" class="h-24 w-24 rounded-2xl border-2 border-rose-200/80 overflow-hidden shadow-inner-gold flex-shrink-0">
              <img :key="`groom-${weddingProfile.updated_at}-${weddingProfile.groom_photo}`" :src="photoUrl(weddingProfile.groom_photo, weddingProfile.updated_at)" alt="Groom" class="w-full h-full object-cover" />
            </div>
          </template>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500">Our Wedding</p>
          <h2 class="font-display text-xl sm:text-2xl font-semibold text-wmis-text mt-1">
            {{ weddingProfile.bride_name && weddingProfile.groom_name ? `${weddingProfile.bride_name} & ${weddingProfile.groom_name}` : (weddingProfile.bride_name || weddingProfile.groom_name || 'Our wedding') }}
          </h2>
          <p v-if="weddingProfile.venue_name" class="text-sm text-gray-600 mt-1">{{ weddingProfile.venue_name }}</p>
          <p v-if="weddingProfile.wedding_date" class="text-sm text-gray-500 mt-0.5">
            {{ formatWeddingDate(weddingProfile.wedding_date) }}
          </p>
        </div>
        <div class="flex-shrink-0 flex items-center gap-4">
          <div class="rounded-2xl bg-white/80 border border-rose-100/80 px-5 py-4 shadow-inner-gold">
            <WeddingCountdown :date="weddingProfile.wedding_date" />
          </div>
          <router-link
            to="/settings/profile"
            class="rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-medium text-rose-600 hover:bg-rose-50 transition-all"
          >
            Edit profile
          </router-link>
        </div>
      </div>
    </div>
    <div
      v-else
      class="rounded-2xl border border-dashed border-rose-200 bg-rose-50/30 p-6 text-center"
    >
      <p class="text-wmis-text font-medium">Set up your wedding profile</p>
      <p class="text-sm text-gray-500 mt-1">Add your names, wedding date and venue to see the countdown here and in the header.</p>
      <router-link
        to="/settings/profile"
        class="inline-flex mt-4 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-rose-600"
      >
        Create wedding profile
      </router-link>
    </div>

    <!-- Stat cards: 3 per row -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
      <StatCard
        label="Total Budget"
        :value="budgetPlanned"
        subtext="Cost of budget items"
        :animate="true"
        :formatter="formatUgx"
      />
      <StatCard
        label="Total Expenditures"
        :value="totalExpenditures"
        subtext="Total spent / expenses recorded"
        :animate="true"
        :formatter="formatUgx"
      />
      <StatCard
        label="Total Remaining to Finish Budget"
        :value="amountStillNeeded"
        subtext="Budget minus (contributions + cost of items covered by default)"
        :value-class="amountStillNeeded >= 0 ? 'text-emerald-600' : 'text-rose-600'"
        :animate="true"
        :formatter="formatUgx"
      />
      <StatCard
        label="Contributions Received"
        :value="contributionsReceived"
        subtext="Amount from Pledges + direct contributions"
        :animate="true"
        :formatter="formatUgx"
      />
      <StatCard
        label="Total Balance Remaining"
        :value="totalBalanceRemaining"
        subtext="Total contributions minus total expenditures"
        :value-class="totalBalanceRemaining >= 0 ? 'text-emerald-600' : 'text-rose-600'"
        :animate="true"
        :formatter="formatUgx"
      />
      <StatCard
        label="Total Amount Pledged"
        :value="totalPledged"
        subtext="Pledged"
        :animate="true"
        :formatter="formatUgx"
      />
      <StatCard
        label="Amount From Pledges"
        :value="amountFromPledges"
        subtext="Received from pledges"
        :animate="true"
        :formatter="formatUgx"
      />
      <StatCard
        label="Amount Remaining From Pledges"
        :value="pledgesRemaining"
        subtext="Outstanding on pledges"
        :animate="true"
        :formatter="formatUgx"
      />
      <StatCard
        label="Total Amount From Direct Contributions"
        :value="directContributions"
        subtext="Direct contributions only"
        :animate="true"
        :formatter="formatUgx"
      />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Budget summary -->
      <div class="lg:col-span-2 space-y-5">
        <div class="card-luxury">
          <h2 class="font-display text-base font-semibold text-wmis-text mb-5">Budget Summary</h2>
          <div class="space-y-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Total budget</span>
              <span class="font-medium text-wmis-text">{{ formatUgx(budgetPlanned) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Covered by default (not from contributions)</span>
              <span class="font-medium text-wmis-text">{{ formatUgx(costOfItemsCoveredByDefault) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Contributions received</span>
              <span class="font-medium text-emerald-600">{{ formatUgx(contributionsReceived) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Remaining to finish budget</span>
              <span class="font-medium" :class="amountStillNeeded >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ formatUgx(amountStillNeeded) }}</span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-rose-100/60 overflow-hidden">
              <div
                class="h-full rounded-full bg-gradient-to-r from-rose-400 to-rose-500 transition-all duration-700 ease-out"
                :style="{ width: `${budgetFundedProgress}%` }"
              />
            </div>
            <p class="text-xs font-medium text-gold-600">{{ budgetFundedProgress }}% funded (by default + contributions)</p>
          </div>
        </div>
        <ChartCard title="Contributions progress" footer="Total received vs expenditures" />
      </div>

      <!-- Activity & upcoming -->
      <div class="space-y-5">
        <ActivityFeed :items="activityItems" />
        <div class="card-luxury">
          <h2 class="font-display text-base font-semibold text-wmis-text mb-4">Upcoming Meetings</h2>
          <ul class="space-y-2">
            <li
              v-for="m in upcomingMeetingsList"
              :key="m.id"
              class="flex items-center gap-3 rounded-xl border border-rose-100/60 bg-gradient-to-r from-white to-rose-50/30 px-4 py-3 text-sm transition-all duration-300 hover:shadow-soft hover:border-rose-200/60"
            >
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-rose-100 to-rose-200/50 text-rose-600 text-xs font-semibold shadow-inner-gold">
                {{ m.day }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="font-medium text-wmis-text">{{ m.title }}</p>
                <p class="text-xs text-gray-500">{{ m.dateFormatted }}</p>
              </div>
              <span class="h-2 w-2 shrink-0 rounded-full bg-gold-400" />
            </li>
          </ul>
          <p v-if="upcomingMeetingsList.length === 0" class="py-4 text-center text-sm text-gray-500">No upcoming meetings</p>
        </div>
      </div>
    </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import StatCard from '@/components/StatCard.vue'
import ChartCard from '@/components/ChartCard.vue'
import ActivityFeed from '@/components/ActivityFeed.vue'
import WeddingCountdown from '@/components/WeddingCountdown.vue'
import { useWeddingProfileStore } from '@/stores/weddingProfile'
import { useReportsStore } from '@/stores/reports'
import { useInvitedGuestsStore } from '@/stores/invitedGuests'
import { useBudgetCategoriesStore } from '@/stores/budgetCategories'
import { useBudgetItemsStore } from '@/stores/budgetItems'
import { useMeetingMinutesStore } from '@/stores/meetingMinutes'
import { useMeetingsStore } from '@/stores/meetings'
import { useActivityStore } from '@/stores/activity'
import { weddingPhotoUrl } from '@/config/api.js'

const weddingProfile = computed(() => useWeddingProfileStore().profile)
function photoUrl(path, cacheKey) {
  return weddingPhotoUrl(path, cacheKey)
}
const reportsStore = useReportsStore()
const guestsStore = useInvitedGuestsStore()
const budgetStore = useBudgetCategoriesStore()
const budgetItemsStore = useBudgetItemsStore()
const minutesStore = useMeetingMinutesStore()
const meetingsStore = useMeetingsStore()
const activityStore = useActivityStore()

function formatWeddingDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (isNaN(d.getTime())) return iso
  return d.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const budgetPlanned = computed(() => {
  return budgetItemsStore.items.reduce((s, i) => s + (Number(i.cost) || 0), 0)
})
const budgetSpent = computed(() => Number(reportsStore.report?.total_expenditures) || 0)
const budgetRemaining = computed(() => budgetPlanned.value - budgetSpent.value)
const budgetProgress = computed(() => (budgetPlanned.value > 0 ? Math.min(100, Math.round((budgetSpent.value / budgetPlanned.value) * 100)) : 0))

const totalPledged = computed(() => Number(reportsStore.report?.total_pledged) || 0)
const amountFromPledges = computed(() => Number(reportsStore.report?.total_pledge_payments) || 0)
const pledgesRemaining = computed(() => Math.max(0, totalPledged.value - amountFromPledges.value))

const contributionsReceived = computed(() => Number(reportsStore.report?.total_received) || 0)
const totalBalanceRemaining = computed(() => Number(reportsStore.report?.balance) ?? 0)
const totalExpenditures = computed(() => Number(reportsStore.report?.total_expenditures) || 0)
const directContributions = computed(() => Number(reportsStore.report?.total_contributions) || 0)

const costOfItemsCoveredByDefault = computed(() =>
  budgetItemsStore.items
    .filter((i) => i.status === 'COVERED' && i.covered_type !== 'FROM_CONTRIBUTIONS')
    .reduce((s, i) => s + (Number(i.cost) || 0), 0)
)
const amountStillNeeded = computed(() =>
  budgetPlanned.value - (contributionsReceived.value + costOfItemsCoveredByDefault.value)
)
const budgetFundedProgress = computed(() =>
  budgetPlanned.value > 0
    ? Math.min(100, Math.round(((costOfItemsCoveredByDefault.value + contributionsReceived.value) / budgetPlanned.value) * 100))
    : 0
)
const contributionsProgress = computed(() => (totalPledged.value > 0 ? Math.min(100, Math.round((contributionsReceived.value / totalPledged.value) * 100)) : (contributionsReceived.value > 0 ? 100 : 0)))
const contributionsDisplay = computed(() => formatUgx(contributionsReceived.value))

const guestTotal = computed(() => guestsStore.guests.length)
const guestConfirmed = computed(() => guestsStore.guests.filter((g) => g.status === 'Confirmed').length)
const guestProgress = computed(() => (guestTotal.value > 0 ? Math.round((guestConfirmed.value / guestTotal.value) * 100) : 0))

const today = ref('')
const dashboardLoading = ref(true)
const upcomingMeetingsList = computed(() => {
  if (!today.value) return []
  return meetingsStore.meetings
    .filter((m) => m.meeting_date && m.meeting_date >= today.value)
    .sort((a, b) => (a.meeting_date || '').localeCompare(b.meeting_date || ''))
    .slice(0, 5)
    .map((m) => ({
      id: m.id,
      title: m.title,
      day: m.meeting_date ? new Date(m.meeting_date + 'Z').getDate() : '',
      dateFormatted: m.meeting_date ? new Date(m.meeting_date + 'Z').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : ''
    }))
})
const upcomingMeetingsCount = computed(() => upcomingMeetingsList.value.length)

const activityItems = computed(() => {
  const list = activityStore.items || []
  const icons = { meeting: '📅', contribution: '💰', pledge: '💵', expenditure: '📤', member: '👤' }
  return list.map((a) => ({
    id: `${a.type}-${a.id}`,
    icon: icons[a.type] || '•',
    title: a.title,
    description: '',
    time: a.date ? formatActivityDate(a.date) : ''
  }))
})

function formatActivityDate(iso) {
  if (!iso) return ''
  const d = new Date(iso + (iso.length === 10 ? 'Z' : ''))
  if (isNaN(d.getTime())) return iso
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatUgx(val) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(val || 0)
}

onMounted(async () => {
  today.value = new Date().toISOString().slice(0, 10)
  dashboardLoading.value = true
  try {
    await Promise.all([
      reportsStore.fetchReport(),
      guestsStore.fetchGuests(),
      budgetStore.fetchCategories(),
      budgetItemsStore.fetchItems(),
      minutesStore.fetchMinutes(),
      meetingsStore.fetchMeetings(),
      activityStore.fetchActivity()
    ])
  } catch (_) {
    // individual stores may set errorMessage
  } finally {
    dashboardLoading.value = false
  }
})
</script>
