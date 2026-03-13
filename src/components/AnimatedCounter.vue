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

const current = ref(0)
const formattedDisplay = ref(props.formatter(0))

let rafId = null

function easeOutQuart(t) {
  return 1 - Math.pow(1 - t, 4)
}

function animate(from, to, durationMs) {
  const start = performance.now()
  function tick(now) {
    const elapsed = now - start
    const t = Math.min(1, elapsed / durationMs)
    const eased = easeOutQuart(t)
    current.value = from + (to - from) * eased
    formattedDisplay.value = props.formatter(current.value)
    if (t < 1) rafId = requestAnimationFrame(tick)
  }
  rafId = requestAnimationFrame(tick)
}

function run() {
  const to = Number(props.to) || 0
  if (to === 0) {
    current.value = 0
    formattedDisplay.value = props.formatter(0)
    return
  }
  cancelAnimationFrame(rafId)
  animate(current.value, to, props.duration)
}

watch(() => props.to, () => run())
onMounted(() => {
  current.value = 0
  run()
})
</script>
