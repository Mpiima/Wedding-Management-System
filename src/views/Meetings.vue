<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Meetings</h1>
        <p class="text-sm text-slate-500">Plan venue visits, tastings, and vendor calls.</p>
      </div>
      <button
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-pink-500 text-white text-sm font-medium shadow-sm hover:bg-pink-600"
      >
        ➕ Schedule meeting
      </button>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white overflow-hidden shadow-sm divide-y divide-slate-100">
      <div
        v-for="m in meetings"
        :key="m.id"
        class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3 hover:bg-slate-50/80"
      >
        <div class="flex items-center gap-3 flex-1">
          <div class="h-10 w-10 rounded-xl bg-pink-50 text-pink-500 flex flex-col items-center justify-center text-xs">
            <span class="font-semibold leading-none">{{ m.day }}</span>
            <span class="text-[10px] uppercase">{{ m.month }}</span>
          </div>
          <div>
            <p class="text-sm font-medium text-slate-800">{{ m.title }}</p>
            <p class="text-xs text-slate-400">{{ m.time }} • {{ m.with }}</p>
          </div>
        </div>
        <span
          class="inline-flex px-2 py-0.5 rounded-full border text-xs border-blue-100 bg-blue-50 text-blue-600"
        >
          {{ m.status }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const meetings = ref([
  { id: 1, title: 'Final venue walkthrough', day: '12', month: 'APR', time: '10:00', with: 'Crystal Garden', status: 'Planned' },
  { id: 2, title: 'Menu tasting', day: '15', month: 'APR', time: '14:00', with: 'Catering team', status: 'Planned' }
])
</script>
