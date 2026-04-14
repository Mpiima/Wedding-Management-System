<template>
  <div class="rounded-2xl border border-sc-border bg-white p-6 shadow-card sm:p-7">
    <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
    <p v-if="subtitle" class="mt-1 text-xs text-slate-500">{{ subtitle }}</p>
    <div class="mt-6 flex h-48 items-end justify-between gap-2 sm:h-56 sm:gap-3">
      <div
        v-for="(v, i) in normalized"
        :key="i"
        class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-2"
      >
        <div
          class="w-full max-w-[3rem] rounded-t-lg bg-gradient-to-t from-brand-600 to-brand-400 transition-all duration-300 ease-out group-hover:from-brand-700 group-hover:to-brand-500"
          :style="{ height: `${Math.max(v.pct, 4)}%` }"
        />
        <span class="truncate text-2xs font-medium text-slate-500 sm:text-xs">{{ labels[i] }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  labels: { type: Array, default: () => [] },
  values: { type: Array, default: () => [] }
})

const normalized = computed(() => {
  const vals = props.values.map((n) => Number(n) || 0)
  const max = Math.max(...vals, 1)
  return vals.map((v) => ({ pct: (v / max) * 100 }))
})
</script>
