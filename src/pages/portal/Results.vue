<template>
  <div class="space-y-6">
    <p v-if="studentName" class="text-sm text-slate-600">
      Results for <strong>{{ studentName }}</strong>
      <span v-if="admissionNo" class="font-mono text-slate-500"> · {{ admissionNo }}</span>
    </p>
    <h1 class="text-2xl font-semibold text-slate-900">My results</h1>
    <DataTable title="Latest term" :columns="cols" :data="rows" row-key="subject" sortable />
    <p class="text-xs text-slate-500">Subject marks below are sample data until exams are linked to this portal.</p>
  </div>
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import DataTable from '@/components/ui/DataTable.vue'

const portal = inject('portalContext', null)

const studentName = computed(() => portal?.displayName || '')
const admissionNo = computed(() => portal?.student?.admission_number || '')

const rows = ref([
  { subject: 'Mathematics', score: 82, grade: 'A' },
  { subject: 'English', score: 76, grade: 'B+' },
  { subject: 'Biology', score: 71, grade: 'B' }
])

const cols = [
  { key: 'subject', label: 'Subject', sortable: true },
  { key: 'score', label: 'Score', sortable: true },
  { key: 'grade', label: 'Grade', sortable: true }
]
</script>
