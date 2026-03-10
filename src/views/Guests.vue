<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Guests</h1>
        <p class="text-sm text-slate-500">Manage your guest list, RSVPs, and seating.</p>
      </div>
      <button
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-pink-500 text-white text-sm font-medium shadow-sm hover:bg-pink-600"
      >
        ➕ Add guest
      </button>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50/80 text-xs text-slate-400 uppercase tracking-[0.12em]">
            <tr>
              <th class="px-4 py-3">Name</th>
              <th class="px-4 py-3">Side</th>
              <th class="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="g in guests"
              :key="g.id"
              class="border-t border-slate-50 hover:bg-slate-50/70"
            >
              <td class="px-4 py-3 font-medium text-slate-800">{{ g.name }}</td>
              <td class="px-4 py-3 text-xs text-slate-500">{{ g.side }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex px-2 py-0.5 rounded-full border text-xs"
                  :class="g.status === 'Confirmed' ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-slate-200 bg-slate-50 text-slate-500'"
                >
                  {{ g.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const guests = ref([
  { id: 1, name: 'Emma Stone', side: 'Bride', status: 'Confirmed' },
  { id: 2, name: 'Liam Johnson', side: 'Groom', status: 'Pending' }
])
</script>
