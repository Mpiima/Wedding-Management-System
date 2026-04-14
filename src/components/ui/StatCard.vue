<template>
  <div
    class="group relative overflow-hidden rounded-2xl border border-sc-border bg-white p-6 shadow-card transition-all duration-300 ease-out hover:-translate-y-0.5 hover:border-sc-border-strong hover:shadow-card-hover sm:p-7"
  >
    <div
      class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-gradient-to-br from-brand-400/10 to-transparent opacity-0 transition-opacity duration-400 ease-out group-hover:opacity-100"
      aria-hidden="true"
    />
    <div class="relative flex items-start justify-between gap-5">
      <div class="min-w-0 flex-1 space-y-1">
        <p class="text-2xs font-semibold uppercase tracking-wider text-slate-500">
          {{ label }}
        </p>
        <p
          class="font-display text-3xl font-semibold tracking-tight text-slate-900 tabular-nums"
          :class="valueClass"
        >
          <template v-if="animate && typeof value === 'number'">
            <AnimatedCounter :to="value" :duration="1200" :formatter="formatter || defaultFmt" />
          </template>
          <template v-else>
            {{ displayValue }}
          </template>
        </p>
        <p v-if="subtext" class="text-sm leading-relaxed text-slate-500">
          {{ subtext }}
        </p>
        <p v-if="trend" class="pt-1 text-xs font-semibold text-emerald-600">{{ trend }}</p>
      </div>
      <div
        v-if="icon"
        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-500/25 transition-transform duration-300 ease-out group-hover:scale-105 group-hover:shadow-sm"
      >
        <component :is="icon" class="h-6 w-6 transition-transform duration-300 ease-out group-hover:scale-110" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: '' },
  subtext: { type: String, default: '' },
  trend: { type: String, default: '' },
  icon: { type: Object, default: null },
  valueClass: { type: String, default: '' },
  animate: { type: Boolean, default: false },
  formatter: { type: Function, default: null }
})

const defaultFmt = (n) => {
  if (typeof n !== 'number') return String(n)
  return n >= 1000 ? Math.round(n).toLocaleString() : String(Math.round(n))
}

const displayValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.formatter ? props.formatter(props.value) : defaultFmt(props.value)
  }
  return props.value
})
</script>
