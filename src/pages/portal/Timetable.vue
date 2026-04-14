<template>
  <div class="space-y-6">
    <p v-if="studentName" class="text-sm text-slate-600">
      Timetable for <strong>{{ studentName }}</strong>
      <span v-if="classLabel" class="text-slate-500"> · {{ classLabel }}</span>
    </p>
    <h1 class="text-2xl font-semibold text-slate-900">Timetable</h1>
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
    <p class="text-xs text-slate-500">Slots below are sample data until your school publishes live timetables.</p>
  </div>
</template>

<script setup>
import { computed, inject, ref, onMounted } from 'vue'
import { getPortalData } from '@/services/mockData'

const portal = inject('portalContext', null)

const studentName = computed(() => portal?.displayName || '')
const classLabel = computed(() => {
  const e = portal?.enrollment
  if (!e) return ''
  const c = e.class_name || ''
  const st = e.stream_name || ''
  return [c, st].filter(Boolean).join(' · ')
})

const days = ref([])

onMounted(async () => {
  const d = await getPortalData()
  days.value = d.timetable || []
})
</script>
