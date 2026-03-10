<template>
  <div class="space-y-6">
    <!-- Overview cards - colored like benchmark -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <div class="rounded-xl bg-blue-600 p-5 text-white shadow-sm flex items-start justify-between">
        <div>
          <p class="text-blue-100 text-sm font-medium">Wedding Budget</p>
          <p class="text-2xl font-bold mt-1">{{ budgetDisplay }}</p>
          <p class="text-blue-200 text-xs mt-1">Left</p>
        </div>
        <span class="text-3xl opacity-90">💰</span>
      </div>
      <div class="rounded-xl bg-sky-500 p-5 text-white shadow-sm flex items-start justify-between">
        <div>
          <p class="text-sky-100 text-sm font-medium">Contributions</p>
          <p class="text-2xl font-bold mt-1">{{ contributionsDisplay }}</p>
          <p class="text-sky-200 text-xs mt-1">Received</p>
        </div>
        <span class="text-3xl opacity-90">🔒</span>
      </div>
      <div class="rounded-xl bg-sky-500 p-5 text-white shadow-sm flex items-start justify-between">
        <div>
          <p class="text-sky-100 text-sm font-medium">Guest List</p>
          <p class="text-2xl font-bold mt-1">{{ guestCount }}</p>
          <p class="text-sky-200 text-xs mt-1">Invited</p>
        </div>
        <span class="text-3xl opacity-90">👫</span>
      </div>
      <div class="rounded-xl bg-amber-400 p-5 text-slate-800 shadow-sm flex items-start justify-between">
        <div>
          <p class="text-amber-900/80 text-sm font-medium">Upcoming Meetings</p>
          <p class="text-2xl font-bold mt-1">{{ meetingCount }}</p>
          <p class="text-amber-900/70 text-xs mt-1">Scheduled</p>
        </div>
        <span class="text-3xl opacity-90">📅</span>
      </div>
    </section>

    <!-- Two columns: Recent Contributions + Task Overview -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <!-- Recent Contributions -->
      <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-800">Recent Contributions</h2>
          <button class="p-1 rounded text-slate-400 hover:bg-slate-100 hover:text-slate-600">⋯</button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider">
              <tr>
                <th class="px-4 py-2.5 text-left">Name</th>
                <th class="px-4 py-2.5 text-left">Amount</th>
                <th class="px-4 py-2.5 text-left">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="c in recentContributions"
                :key="c.id"
                class="border-t border-slate-100 hover:bg-slate-50/80"
              >
                <td class="px-4 py-2.5 font-medium text-slate-800">{{ c.name }}</td>
                <td class="px-4 py-2.5 text-slate-600">{{ formatCurrency(c.amount) }}</td>
                <td class="px-4 py-2.5">
                  <span
                    class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="c.status === 'Paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                  >
                    {{ c.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Task Overview -->
      <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-800">Task Overview</h2>
          <div class="flex items-center gap-1">
            <button class="p-1.5 rounded text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Edit">✎</button>
            <button class="p-1 rounded text-slate-400 hover:bg-slate-100 hover:text-slate-600">⋯</button>
          </div>
        </div>
        <div class="p-4 space-y-3">
          <div
            v-for="task in tasks"
            :key="task.id"
            class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50/50 p-3 hover:bg-slate-50"
          >
            <span class="text-xl shrink-0">{{ task.icon }}</span>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-800">{{ task.title }}</p>
              <p v-if="task.due" class="text-xs text-slate-500">{{ task.due }}</p>
            </div>
            <div class="flex items-center gap-1 shrink-0">
              <button class="p-1.5 rounded text-slate-400 hover:bg-slate-200 hover:text-slate-600" title="Calendar">📅</button>
              <button class="p-1.5 rounded text-slate-400 hover:bg-slate-200 hover:text-slate-600" title="Folder">📁</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Top Vendors -->
    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Top Vendors</h2>
        <button class="p-1 rounded text-slate-400 hover:bg-slate-100 hover:text-slate-600">⋯</button>
      </div>
      <div class="p-4">
        <div class="flex flex-wrap gap-4">
          <router-link
            v-for="v in topVendors"
            :key="v.id"
            to="/vendors"
            class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50/50 px-4 py-3 hover:bg-sky-50 hover:border-sky-200 min-w-0 flex-1 sm:flex-none"
          >
            <span class="h-10 w-10 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center text-sm font-semibold shrink-0">
              {{ v.initials }}
            </span>
            <span class="text-sm font-medium text-slate-800 truncate">{{ v.name }}</span>
            <span class="text-slate-400 shrink-0">→</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Photo Gallery -->
    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Photo Gallery</h2>
        <router-link
          to="/media"
          class="text-xs font-medium text-blue-600 hover:text-blue-700"
        >
          View all →
        </router-link>
      </div>
      <div class="p-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <div
            v-for="i in 6"
            :key="i"
            class="aspect-square rounded-lg bg-slate-200 flex items-center justify-center text-slate-400 text-3xl overflow-hidden"
          >
            🖼
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const budgetTotal = ref(95000000)
const budgetSpent = ref(70000000)
const budgetDisplay = computed(() => {
  const left = budgetTotal.value - budgetSpent.value
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(left)
})

const contributionsReceived = ref(47000000)
const contributionsDisplay = computed(() =>
  new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(contributionsReceived.value)
)

const guestCount = ref(120)
const meetingCount = ref(3)

const formatCurrency = (v) =>
  new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(v || 0)

const recentContributions = ref([
  { id: 1, name: 'John Doe', amount: 500000, status: 'Paid' },
  { id: 2, name: 'Jane Smith', amount: 750000, status: 'Paid' },
  { id: 3, name: 'Mark Taylor', amount: 300000, status: 'Pending' }
])

const tasks = ref([
  { id: 1, icon: '📍', title: 'Plan Venue Visit', due: null },
  { id: 2, icon: '🎯', title: 'Send Invitations', due: 'Due Tomorrow' }
])

const topVendors = ref([
  { id: 1, name: 'Catering Co.', initials: 'CC' },
  { id: 2, name: 'Dream Photography', initials: 'DP' },
  { id: 3, name: 'Floral Designs', initials: 'FD' }
])
</script>
