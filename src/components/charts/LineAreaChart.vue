<template>
  <div class="rounded-2xl border border-sc-border bg-white p-6 shadow-card sm:p-7">
    <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
    <p v-if="subtitle" class="mt-1 text-xs text-slate-500">{{ subtitle }}</p>
    <div class="mt-6 h-48 sm:h-56">
      <svg class="h-full w-full overflow-visible" :viewBox="`0 0 ${w} ${h}`" preserveAspectRatio="none">
        <defs>
          <linearGradient id="g-area" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="rgb(44, 170, 226)" stop-opacity="0.35" />
            <stop offset="100%" stop-color="rgb(44, 170, 226)" stop-opacity="0.02" />
          </linearGradient>
        </defs>
        <polygon :points="areaPoints" fill="url(#g-area)" class="transition-all duration-500 ease-out" />
        <polyline
          :points="linePoints"
          fill="none"
          stroke="rgb(94, 106, 210)"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
          class="transition-all duration-500 ease-out"
        />
        <g v-for="(p, i) in points" :key="i">
          <circle :cx="p.x" :cy="p.y" r="3.5" fill="white" stroke="rgb(44, 170, 226)" stroke-width="2" />
        </g>
      </svg>
      <div class="mt-3 flex justify-between text-2xs text-slate-500 sm:text-xs">
        <span v-for="(lab, i) in labels" :key="i" class="truncate text-center" :style="{ width: `${100 / labels.length}%` }">
          {{ lab }}
        </span>
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

const w = 400
const h = 160
const pad = 12

const points = computed(() => {
  const vals = props.values.map((n) => Number(n) || 0)
  const max = Math.max(...vals, 1)
  const n = vals.length || 1
  return vals.map((v, i) => {
    const x = pad + (i / Math.max(n - 1, 1)) * (w - pad * 2)
    const y = h - pad - (v / max) * (h - pad * 2)
    return { x, y }
  })
})

const linePoints = computed(() => points.value.map((p) => `${p.x},${p.y}`).join(' '))

const areaPoints = computed(() => {
  if (!points.value.length) return ''
  const first = points.value[0]
  const last = points.value[points.value.length - 1]
  const baseY = h - pad
  const top = points.value.map((p) => `${p.x},${p.y}`).join(' ')
  return `${first.x},${baseY} ${top} ${last.x},${baseY}`
})
</script>
