<template>
  <div class="card-luxury">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0 flex-1">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500">{{ label }}</p>
        <p class="mt-2 font-display text-xl font-semibold text-wmis-text">{{ value }}%</p>
      </div>
      <div class="relative h-14 w-14 shrink-0">
        <svg class="h-full w-full -rotate-90" viewBox="0 0 36 36">
          <path
            class="text-rose-100"
            stroke="currentColor"
            stroke-width="3"
            fill="none"
            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
          />
          <path
            class="text-rose-500 transition-all duration-700 ease-out"
            stroke="currentColor"
            stroke-width="3"
            stroke-dasharray="100, 100"
            :stroke-dashoffset="100 - value"
            stroke-linecap="round"
            fill="none"
            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
          />
        </svg>
      </div>
    </div>
    <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-rose-100/60">
      <div
        class="h-full rounded-full bg-gradient-to-r from-rose-400 to-rose-500 transition-all duration-700 ease-out"
        :style="{ width: `${Math.min(100, value)}%` }"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: Number, required: true }
})
</script>
