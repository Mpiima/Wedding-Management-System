<template>
  <span>{{ formattedDisplay }}</span>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  to: { type: Number, default: 0 },
  duration: { type: Number, default: 1200 },
  formatter: { type: Function, default: (n) => String(Math.round(n)) }
})

const formattedDisplay = ref(props.formatter(0))
let rafId = null

function easeOutQuart(t) {
  return 1 - (1 - t) ** 4
}

function animate(from, toVal, durationMs) {
  const start = performance.now()
  function tick(now) {
    const elapsed = now - start
    const t = Math.min(1, elapsed / durationMs)
    const eased = easeOutQuart(t)
    const current = from + (toVal - from) * eased
    formattedDisplay.value = props.formatter(current)
    if (t < 1) rafId = requestAnimationFrame(tick)
  }
  rafId = requestAnimationFrame(tick)
}

function run() {
  const toVal = Number(props.to) || 0
  cancelAnimationFrame(rafId)
  animate(0, toVal, props.duration)
}

watch(() => props.to, () => run())
onMounted(() => run())
</script>
