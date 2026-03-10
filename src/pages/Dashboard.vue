<template>
  <div class="space-y-8">
    <div class="flex flex-col gap-1">
      <h1 class="font-display text-2xl font-semibold tracking-tight text-wmis-text">Dashboard</h1>
      <p class="text-sm text-gray-500">Wedding overview and recent activity</p>
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
        :value="`${guestConfirmed} / ${guestTotal}`"
        subtext="Confirmed"
        :progress="guestProgress"
      />
      <StatCard
        label="Upcoming Meetings"
        :value="upcomingMeetings"
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
            <div class="h-2.5 w-full rounded-full bg-rose-100/60 overflow-hidden">
              <div
                class="h-full rounded-full bg-gradient-to-r from-rose-400 to-rose-500 transition-all duration-700 ease-out"
                :style="{ width: `${budgetProgress}%` }"
              />
            </div>
            <p class="text-xs font-medium text-gold-600">{{ budgetProgress }}% used</p>
          </div>
        </div>
        <ChartCard title="Contributions progress" footer="Last 6 months" />
      </div>

      <!-- Activity & upcoming -->
      <div class="space-y-5">
        <ActivityFeed :items="activities" />
        <div class="card-luxury">
          <h2 class="font-display text-base font-semibold text-wmis-text mb-4">Upcoming Meetings</h2>
          <ul class="space-y-2">
            <li
              v-for="m in meetings"
              :key="m.id"
              class="flex items-center gap-3 rounded-xl border border-rose-100/60 bg-gradient-to-r from-white to-rose-50/30 px-4 py-3 text-sm transition-all duration-300 hover:shadow-soft hover:border-rose-200/60"
            >
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-rose-100 to-rose-200/50 text-rose-600 text-xs font-semibold shadow-inner-gold">
                {{ m.day }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="font-medium text-wmis-text">{{ m.title }}</p>
                <p class="text-xs text-gray-500">{{ m.time }} · {{ m.with }}</p>
              </div>
              <span class="h-2 w-2 shrink-0 rounded-full bg-gold-400" />
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import StatCard from '@/components/StatCard.vue'
import ChartCard from '@/components/ChartCard.vue'
import ActivityFeed from '@/components/ActivityFeed.vue'

const budgetPlanned = ref(95000000)
const budgetSpent = ref(52000000)
const budgetProgress = computed(() => Math.round((budgetSpent.value / budgetPlanned.value) * 100))
const budgetDisplay = computed(() => formatUgx(budgetPlanned.value - budgetSpent.value))

const contributionsReceived = ref(47000000)
const contributionsTarget = ref(60000000)
const contributionsProgress = computed(() => Math.min(100, Math.round((contributionsReceived.value / contributionsTarget.value) * 100)))
const contributionsDisplay = computed(() => formatUgx(contributionsReceived.value))

const guestConfirmed = ref(98)
const guestTotal = ref(150)
const guestProgress = computed(() => Math.round((guestConfirmed.value / guestTotal.value) * 100))

const upcomingMeetings = ref(3)

const activities = ref([
  { id: 1, icon: '💰', title: 'Contribution received from ', description: 'Jane Doe — UGX 500,000', time: '2 min ago' },
  { id: 2, icon: '✅', title: 'RSVP confirmed — ', description: 'John Smith', time: '1 hr ago' },
  { id: 3, icon: '📅', title: 'Meeting scheduled: ', description: 'Venue walkthrough', time: 'Yesterday' }
])

const meetings = ref([
  { id: 1, day: '12', title: 'Venue final visit', time: '10:00', with: 'Crystal Garden' },
  { id: 2, day: '15', title: 'Menu tasting', time: '14:00', with: 'Catering' }
])

function formatUgx(val) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(val || 0)
}
</script>
