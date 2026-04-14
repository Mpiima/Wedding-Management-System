<template>
  <div class="page-shell">
    <PageHeader title="Results" description="Report cards (mock)." />
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div
        v-for="rep in reports"
        :key="rep.studentId"
        class="rounded-2xl border border-sc-border bg-white p-6 shadow-card transition hover:shadow-card-hover"
      >
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <p class="text-lg font-semibold text-slate-900">{{ rep.name }}</p>
            <p class="text-sm text-slate-500">{{ rep.class }} · {{ rep.term }}</p>
          </div>
          <div class="rounded-xl bg-brand-50 px-3 py-2 text-center ring-1 ring-brand-500/15">
            <p class="text-2xs font-semibold uppercase text-slate-500">GPA</p>
            <p class="text-xl font-bold text-brand-700">{{ rep.gpa }}</p>
          </div>
        </div>
        <ul class="mt-4 space-y-2">
          <li
            v-for="g in rep.grades"
            :key="g.subject"
            class="flex items-center justify-between rounded-xl bg-slate-50/80 px-3 py-2 text-sm"
          >
            <span class="font-medium text-slate-800">{{ g.subject }}</span>
            <span class="text-slate-600">{{ g.score }} · {{ g.grade }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { getResults } from '@/services/mockData'

const reports = ref([])

onMounted(async () => {
  reports.value = await getResults()
})
</script>
