<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Budget</h1>
        <p class="text-sm text-slate-500">Track allocations, actual spend, and contributions.</p>
      </div>
      <button
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-pink-500 text-white text-sm font-medium shadow-sm hover:bg-pink-600"
      >
        ➕ Add budget item
      </button>
    </div>
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="rounded-2xl border border-slate-100 bg-white p-4">
        <p class="text-xs text-slate-400 uppercase tracking-[0.15em]">Planned</p>
        <p class="text-lg font-semibold text-slate-800">{{ formatCurrency(totalPlanned) }}</p>
      </div>
      <div class="rounded-2xl border border-slate-100 bg-white p-4">
        <p class="text-xs text-slate-400 uppercase tracking-[0.15em]">Actual</p>
        <p class="text-lg font-semibold text-emerald-600">{{ formatCurrency(totalActual) }}</p>
      </div>
      <div class="rounded-2xl border border-slate-100 bg-white p-4">
        <p class="text-xs text-slate-400 uppercase tracking-[0.15em]">Remaining</p>
        <p class="text-lg font-semibold text-slate-800">{{ formatCurrency(remaining) }}</p>
      </div>
    </section>
    <div class="rounded-2xl border border-slate-100 bg-white overflow-hidden shadow-sm">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50/80 text-xs text-slate-400 uppercase tracking-[0.12em]">
          <tr>
            <th class="px-4 py-3">Category</th>
            <th class="px-4 py-3">Item</th>
            <th class="px-4 py-3">Planned</th>
            <th class="px-4 py-3">Actual</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in budgetItems"
            :key="item.id"
            class="border-t border-slate-50 hover:bg-slate-50/70"
          >
            <td class="px-4 py-3 text-slate-500">{{ item.category }}</td>
            <td class="px-4 py-3 text-slate-800">{{ item.name }}</td>
            <td class="px-4 py-3 text-slate-500">{{ formatCurrency(item.planned) }}</td>
            <td class="px-4 py-3 text-slate-500">{{ formatCurrency(item.actual) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const budgetItems = ref([
  { id: 1, category: 'Venue', name: 'Hall & Garden', planned: 9000, actual: 8800 },
  { id: 2, category: 'Catering', name: 'Dinner & Drinks', planned: 7000, actual: 7200 }
])
const totalPlanned = computed(() => budgetItems.value.reduce((s, i) => s + (i.planned || 0), 0))
const totalActual = computed(() => budgetItems.value.reduce((s, i) => s + (i.actual || 0), 0))
const remaining = computed(() => totalPlanned.value - totalActual.value)
const formatCurrency = (v) => new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(v || 0)
</script>
