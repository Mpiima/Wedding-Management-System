<template>
  <div class="space-y-6">
    <p v-if="studentName" class="text-sm text-slate-600">
      Attendance overview for <strong>{{ studentName }}</strong>
      <span v-if="admissionNo" class="font-mono text-slate-500"> · {{ admissionNo }}</span>
    </p>
    <h1 class="text-2xl font-semibold text-slate-900">My attendance</h1>
    <div class="rounded-2xl border border-sc-border bg-white p-6 shadow-card">
      <p class="text-sm text-slate-600">
        Daily attendance will appear here when your school connects the attendance module. The percentage below is illustrative.
      </p>
      <p class="mt-4 text-sm text-slate-600">This month you were present <strong>98%</strong> of the time (sample).</p>
      <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-100">
        <div class="h-full w-[98%] rounded-full bg-gradient-to-r from-brand-500 to-brand-600" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, inject } from 'vue'

const portal = inject('portalContext', null)

const studentName = computed(() => portal?.displayName || '')
const admissionNo = computed(() => portal?.student?.admission_number || '')
</script>
