<template>
  <span
    class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-semibold leading-none transition-transform duration-200 ease-out hover:scale-[1.02]"
    :class="toneClass"
  >
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  tone: { type: String, default: 'neutral' }
})

const toneClass = computed(() => {
  const map = {
    success: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-600/15',
    warning: 'bg-amber-50 text-amber-900 ring-1 ring-amber-600/15',
    danger: 'bg-rose-50 text-rose-800 ring-1 ring-rose-600/15',
    info: 'bg-sky-50 text-sky-900 ring-1 ring-sky-600/15',
    neutral: 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10'
  }
  return map[props.tone] || map.neutral
})
</script>
