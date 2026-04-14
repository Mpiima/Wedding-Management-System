<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Messages</h1>
      <p class="mt-1 text-sm text-slate-500">Updates from teachers and the office (mock).</p>
    </div>
    <ul class="space-y-3">
      <li
        v-for="m in messages"
        :key="m.id"
        class="rounded-2xl border border-sc-border bg-white p-5 shadow-card transition hover:shadow-md"
        :class="m.unread ? 'ring-1 ring-violet-500/20' : ''"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900">{{ m.subject }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">{{ m.from }}</p>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ m.preview }}</p>
          </div>
          <div class="flex shrink-0 flex-col items-end gap-1">
            <span class="text-2xs text-slate-400">{{ m.date }}</span>
            <span
              v-if="m.unread"
              class="rounded-full bg-violet-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
            >
              New
            </span>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getParentPortalData } from '@/services/mockData'

const messages = ref([])

onMounted(async () => {
  const d = await getParentPortalData()
  messages.value = d.messages || []
})
</script>
