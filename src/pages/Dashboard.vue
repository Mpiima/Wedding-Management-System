<template>
  <div class="space-y-8">
    <div class="flex flex-col gap-1">
      <h1 class="font-display text-2xl font-semibold tracking-tight text-wmis-text">Dashboard</h1>
      <p class="text-sm text-gray-500">Wedding overview and recent activity</p>
    </div>

    <!-- Wedding profile & countdown card -->
    <div
      v-if="weddingProfile"
      class="rounded-2xl border border-rose-100/60 bg-gradient-to-br from-white via-rose-50/30 to-gold-50/20 p-6 shadow-card overflow-hidden"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="min-w-0">
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

    <!-- Stat cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        label="Total Budget"
        :value="budgetDisplay"
        subtext="Planned"
        :progress="budgetProgress"
      />
      <StatCard
        label="Contributions"
        :value="contributionsDisplay"
        subtext="Received"
        :progress="contributionsProgress"
      />
      <StatCard
        label="Guests"
        :value="guestTotal > 0 ? `${guestConfirmed} / ${guestTotal}` : '0'"
        subtext="Confirmed"
        :progress="guestProgress"
      />
      <StatCard
        label="Upcoming Meetings"
        :value="upcomingMeetingsCount"
        subtext="Scheduled"
        :highlight="true"
      />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Budget summary -->
      <div class="lg:col-span-2 space-y-5">
        <div class="card-luxury">
          <h2 class="font-display text-base font-semibold text-wmis-text mb-5">Budget Summary</h2>
          <div class="space-y-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Planned</span>
              <span class="font-medium text-wmis-text">{{ formatUgx(budgetPlanned) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Spent</span>
              <span class="font-medium text-rose-600">{{ formatUgx(budgetSpent) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Balance</span>
              <span class="font-medium" :class="balance >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ formatUgx(balance) }}</span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-rose-100/60 overflow-hidden">
              <div
                class="h-full rounded-full bg-gradient-to-r from-rose-400 to-rose-500 transition-all duration-700 ease-out"
                :style="{ width: `${budgetProgress}%` }"
              />
            </div>
            <p class="text-xs font-medium text-gold-600">{{ budgetProgress }}% used</p>
          </div>
        </div>
        <ChartCard title="Contributions progress" footer="Total received vs expenditures" />
      </div>

      <!-- Activity & upcoming -->
      <div class="space-y-5">
        <ActivityFeed :items="activities" />
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
import { useMeetingMinutesStore } from '@/stores/meetingMinutes'

const weddingProfile = computed(() => useWeddingProfileStore().profile)
const reportsStore = useReportsStore()
const guestsStore = useInvitedGuestsStore()
const budgetStore = useBudgetCategoriesStore()
const minutesStore = useMeetingMinutesStore()

function formatWeddingDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (isNaN(d.getTime())) return iso
  return d.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const budgetPlanned = computed(() => {
  return budgetStore.categories.reduce((s, c) => s + (Number(c.planned_amount) || 0), 0)
})
const budgetSpent = computed(() => Number(reportsStore.report?.total_expenditures) || 0)
const balance = computed(() => Number(reportsStore.report?.balance) ?? 0)
const budgetProgress = computed(() => (budgetPlanned.value > 0 ? Math.min(100, Math.round((budgetSpent.value / budgetPlanned.value) * 100)) : 0))
const budgetDisplay = computed(() => formatUgx(Math.max(0, budgetPlanned.value - budgetSpent.value)))

const contributionsReceived = computed(() => Number(reportsStore.report?.total_received) || 0)
const totalPledged = computed(() => Number(reportsStore.report?.total_pledged) || 0)
const contributionsProgress = computed(() => (totalPledged.value > 0 ? Math.min(100, Math.round((contributionsReceived.value / totalPledged.value) * 100)) : (contributionsReceived.value > 0 ? 100 : 0)))
const contributionsDisplay = computed(() => formatUgx(contributionsReceived.value))

const guestTotal = computed(() => guestsStore.guests.length)
const guestConfirmed = computed(() => guestsStore.guests.filter((g) => g.status === 'Confirmed').length)
const guestProgress = computed(() => (guestTotal.value > 0 ? Math.round((guestConfirmed.value / guestTotal.value) * 100) : 0))

const today = ref('')
const upcomingMeetingsList = computed(() => {
  if (!today.value) return []
  return minutesStore.minutes
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

const activities = ref([
  { id: 1, icon: '💰', title: 'Contributions', description: 'Track pledge payments and direct contributions in Reports', time: '' },
  { id: 2, icon: '✅', title: 'Guests', description: 'Manage RSVPs in Invited Guests', time: '' },
  { id: 3, icon: '📅', title: 'Meetings', description: 'View meeting minutes in Meeting Minutes', time: '' }
])

function formatUgx(val) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(val || 0)
}

onMounted(() => {
  today.value = new Date().toISOString().slice(0, 10)
  reportsStore.fetchReport().catch(() => {})
  guestsStore.fetchGuests().catch(() => {})
  budgetStore.fetchCategories().catch(() => {})
  minutesStore.fetchMinutes().catch(() => {})
})
</script>
