<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Meetings</h1>
        <p class="text-sm text-gray-500">Meeting schedule and upcoming meetings</p>
      </div>
      <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600">+ Schedule meeting</button>
    </div>

    <div class="rounded-2xl bg-white shadow-card border border-gray-100 overflow-hidden">
      <div class="border-b border-gray-100 px-5 py-3">
        <h2 class="text-sm font-semibold text-wmis-text">Upcoming meetings</h2>
      </div>
      <ul class="divide-y divide-gray-50">
        <li
          v-for="m in meetings"
          :key="m.id"
          class="flex items-center gap-4 px-5 py-4 hover:bg-rose-50/30 transition"
        >
          <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-rose-50 text-rose-600">
            <span class="text-lg font-bold leading-none">{{ m.day }}</span>
            <span class="text-[10px] uppercase">{{ m.month }}</span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-medium text-wmis-text">{{ m.title }}</p>
            <p class="text-sm text-gray-500">{{ m.time }} · {{ m.with }} · {{ m.location }}</p>
          </div>
          <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">{{ m.status }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const meetings = ref([
  { id: 1, day: '12', month: 'Apr', title: 'Venue final visit', time: '10:00', with: 'Crystal Garden', location: 'On-site', status: 'Planned' },
  { id: 2, day: '15', month: 'Apr', title: 'Menu tasting', time: '14:00', with: 'Catering', location: 'Kitchen studio', status: 'Planned' }
])
</script>
