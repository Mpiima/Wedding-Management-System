<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Vendors</h1>
        <p class="text-sm text-slate-500">Keep your service providers and contracts organized.</p>
      </div>
      <button
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-pink-500 text-white text-sm font-medium shadow-sm hover:bg-pink-600"
      >
        ➕ Add vendor
      </button>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white overflow-hidden shadow-sm">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50/80 text-xs text-slate-400 uppercase tracking-[0.12em]">
          <tr>
            <th class="px-4 py-3">Vendor</th>
            <th class="px-4 py-3">Category</th>
            <th class="px-4 py-3">Contact</th>
            <th class="px-4 py-3">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="v in vendors"
            :key="v.id"
            class="border-t border-slate-50 hover:bg-slate-50/70"
          >
            <td class="px-4 py-3 font-medium text-slate-800">{{ v.name }}</td>
            <td class="px-4 py-3 text-xs text-slate-500">{{ v.category }}</td>
            <td class="px-4 py-3 text-xs text-slate-500">{{ v.contact }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex px-2 py-0.5 rounded-full border text-xs"
                :class="v.status === 'Booked' ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-amber-100 bg-amber-50 text-amber-600'"
              >
                {{ v.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const vendors = ref([
  { id: 1, name: 'Crystal Garden Venue', category: 'Venue', contact: 'Amelia', status: 'Booked' },
  { id: 2, name: 'Sunrise Photography', category: 'Photography', contact: 'Leo', status: 'Considering' }
])
</script>
