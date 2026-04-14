<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">My children</h1>
      <p class="mt-1 text-sm text-slate-500">Linked learners on your account (mock).</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
      <div
        v-for="c in children"
        :key="c.id"
        class="rounded-2xl border border-sc-border bg-white p-5 shadow-card"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-semibold text-slate-900">{{ c.name }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ c.class }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ c.admissionNo }}</p>
          </div>
          <span
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-sm font-bold text-violet-800"
          >
            {{ initials(c.name) }}
          </span>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 text-sm">
          <div>
            <dt class="text-2xs font-semibold uppercase tracking-wider text-slate-400">Attendance</dt>
            <dd class="mt-0.5 font-medium text-slate-800">{{ c.attendancePct }}%</dd>
          </div>
          <div>
            <dt class="text-2xs font-semibold uppercase tracking-wider text-slate-400">Fees balance</dt>
            <dd class="mt-0.5 font-medium text-slate-800">{{ formatCurrency(c.feesBalance) }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getParentPortalData } from '@/services/mockData'
import { formatCurrency } from '@/utils/formatters'

const children = ref([])

function initials(name) {
  const parts = name.split(' ').filter((p) => p.length > 0)
  const a = parts[0] ? parts[0][0] : ''
  const b = parts[1] ? parts[1][0] : ''
  return (a + b).toUpperCase()
}

onMounted(async () => {
  const d = await getParentPortalData()
  children.value = d.children || []
})
</script>
