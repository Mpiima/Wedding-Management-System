<template>
  <div
    class="group rounded-2xl border border-rose-100/60 bg-gradient-to-br from-white to-rose-50/20 p-6 shadow-card transition-all duration-350 ease-luxury hover:shadow-card-hover hover:border-rose-200/60 hover:-translate-y-0.5"
    :class="{ 'ring-2 ring-gold-200/50 ring-offset-2': highlight }"
  >
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500">
          {{ label }}
        </p>
        <p class="mt-2 font-display text-2xl font-semibold tracking-tight text-wmis-text truncate" :class="valueClass">
          {{ displayValue }}
        </p>
        <p v-if="subtext" class="mt-1.5 text-sm text-gray-500">
          {{ subtext }}
        </p>
      </div>
      <div
        v-if="icon"
        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl transition-all duration-350 ease-luxury group-hover:scale-110 group-hover:shadow-soft"
        :class="iconBgClass"
      >
        <component :is="icon" class="h-6 w-6" :class="iconColorClass" />
      </div>
    </div>
    <div v-if="progress !== undefined" class="mt-4">
      <div class="h-2 w-full overflow-hidden rounded-full bg-rose-100/60">
        <div
          class="h-full rounded-full bg-gradient-to-r from-rose-400 to-rose-500 transition-all duration-500 ease-out"
          :style="{ width: `${Math.min(100, Math.max(0, progress))}%` }"
        />
      </div>
      <p v-if="progress !== undefined && progress < 100" class="mt-1 text-xs text-gold-600 font-medium">{{ progress }}%</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: '' },
  subtext: { type: String, default: '' },
  icon: { type: Object, default: null },
  progress: { type: Number, default: undefined },
  highlight: { type: Boolean, default: false },
  valueClass: { type: String, default: '' },
  iconBgClass: { type: String, default: 'bg-rose-50 text-rose-500' },
  iconColorClass: { type: String, default: '' }
})

const displayValue = computed(() => {
  if (typeof props.value === 'number' && props.value >= 1000 && props.value < 1000000) {
    return props.value.toLocaleString()
  }
  return props.value
})
</script>
