<template>
  <div class="wedding-countdown inline-flex flex-wrap items-center gap-2" :class="{ compact }">
    <template v-if="status === 'past'">
      <span class="text-sm font-medium text-gray-500">Wedding day passed</span>
    </template>
    <template v-else-if="status === 'today'">
      <span class="countdown-badge countdown-today">Today is the day!</span>
    </template>
    <template v-else-if="status === 'tomorrow'">
      <span class="countdown-badge countdown-tomorrow">Tomorrow!</span>
    </template>
    <template v-else>
      <span class="countdown-number">{{ days }}</span>
      <span class="countdown-unit">{{ days === 1 ? 'day' : 'days' }}</span>
      <span v-if="showHours && days < 14" class="countdown-extra">
        {{ String(hours).padStart(2, '0') }}h {{ String(mins).padStart(2, '0') }}m
      </span>
      <span class="countdown-label">to go</span>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  /** ISO date string (YYYY-MM-DD) or full ISO datetime */
  date: { type: String, default: '' },
  /** Show hours and minutes when under 14 days */
  showHours: { type: Boolean, default: true },
  /** Compact style (smaller text) */
  compact: { type: Boolean, default: false }
})

const now = ref(new Date())

const parsed = computed(() => {
  if (!props.date || !props.date.trim()) return null
  const d = new Date(props.date.trim())
  return isNaN(d.getTime()) ? null : d
})

const startOfDay = (d) => {
  const x = new Date(d)
  x.setHours(0, 0, 0, 0)
  return x
}

const diff = computed(() => {
  const end = parsed.value
  if (!end) return null
  const start = startOfDay(now.value)
  const endDay = startOfDay(end)
  const diffMs = endDay - start
  const days = Math.floor(diffMs / (24 * 60 * 60 * 1000))
  const sameDay = start.getTime() === endDay.getTime()
  const tomorrow = days === 1 && (endDay - start) > 0
  const today = sameDay || (days === 0 && diffMs >= 0)
  const past = diffMs < 0 && !sameDay
  return { days, sameDay, tomorrow, today, past, endDay, diffMs }
})

const status = computed(() => {
  const d = diff.value
  if (!d) return 'none'
  if (d.past) return 'past'
  if (d.today) return 'today'
  if (d.tomorrow) return 'tomorrow'
  return 'future'
})

const days = computed(() => {
  const d = diff.value
  if (!d || d.days < 0) return 0
  return d.days
})

const hours = computed(() => {
  const end = parsed.value
  if (!end || !diff.value || diff.value.days >= 14) return 0
  const endDay = startOfDay(end)
  const remaining = endDay.getTime() - now.value.getTime()
  if (remaining <= 0) return 0
  return Math.floor((remaining % (24 * 60 * 60 * 1000)) / (60 * 60 * 1000))
})

const mins = computed(() => {
  const end = parsed.value
  if (!end || !diff.value || diff.value.days >= 14) return 0
  const endDay = startOfDay(end)
  const remaining = endDay.getTime() - now.value.getTime()
  if (remaining <= 0) return 0
  return Math.floor((remaining % (60 * 60 * 1000)) / (60 * 1000))
})

let tick
onMounted(() => {
  tick = setInterval(() => { now.value = new Date() }, 60 * 1000)
})
onUnmounted(() => {
  if (tick) clearInterval(tick)
})
</script>

<style scoped>
.wedding-countdown {
  font-variant-numeric: tabular-nums;
}
.countdown-number {
  @apply font-display font-bold text-2xl text-rose-600;
}
.countdown-unit {
  @apply text-sm font-medium text-gray-600;
}
.countdown-extra {
  @apply text-xs font-medium text-gold-600 bg-gold-50 px-1.5 py-0.5 rounded;
}
.countdown-label {
  @apply text-sm text-gray-500;
}
.countdown-badge {
  @apply inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold;
}
.countdown-today {
  @apply bg-rose-500 text-white shadow-soft;
}
.countdown-tomorrow {
  @apply bg-gold-100 text-gold-800;
}
.compact .countdown-number {
  @apply text-lg;
}
.compact .countdown-unit,
.compact .countdown-label,
.compact .countdown-extra {
  @apply text-xs;
}
</style>
