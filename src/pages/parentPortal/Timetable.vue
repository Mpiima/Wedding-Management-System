<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Timetable</h1>
        <p class="mt-1 text-sm text-slate-500">Weekly schedule for the selected child (mock).</p>
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-2xs font-semibold uppercase tracking-wider text-slate-400" for="child-select">Child</label>
        <select
          id="child-select"
          v-model="selectedId"
          class="rounded-xl border border-sc-border bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm outline-none ring-brand-500/0 transition focus:ring-2 focus:ring-brand-500/25"
        >
          <option v-for="c in children" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
    </div>
    <div v-if="!days.length" class="rounded-2xl border border-dashed border-sc-border bg-white/80 p-8 text-center text-sm text-slate-500">
      No timetable for this child.
    </div>
    <div v-for="day in days" :key="day.day" class="rounded-2xl border border-sc-border bg-white p-5 shadow-card">
      <h2 class="text-sm font-semibold text-slate-900">{{ day.day }}</h2>
      <ul class="mt-3 space-y-2">
        <li
          v-for="(s, i) in day.slots"
          :key="i"
          class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2 text-sm"
        >
          <span class="font-medium text-slate-800">{{ s.subject }}</span>
          <span class="text-slate-500">{{ s.time }} · {{ s.room }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { getParentPortalData } from '@/services/mockData'

const children = ref([])
const timetableByChild = ref({})
const selectedId = ref('')

const days = computed(() => timetableByChild.value[selectedId.value] || [])

onMounted(async () => {
  const d = await getParentPortalData()
  children.value = d.children || []
  timetableByChild.value = d.timetableByChild || {}
  if (children.value.length) {
    selectedId.value = children.value[0].id
  }
})

watch(children, (list) => {
  if (list.length && !list.some((c) => c.id === selectedId.value)) {
    selectedId.value = list[0].id
  }
})
</script>
