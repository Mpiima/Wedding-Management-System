<template>
  <!-- Full-colour logo: use on light backgrounds. On dark, use onDark for a white backing. -->
  <div
    v-if="onDark"
    class="inline-flex items-center justify-center rounded-lg bg-white px-2 py-1.5 shadow-sm ring-1 ring-white/10"
  >
    <img
      :src="src"
      alt="SCH PRO 360"
      :class="[sizeClass, 'w-auto max-w-[200px] object-contain object-left']"
      loading="lazy"
      decoding="async"
    />
  </div>
  <img
    v-else
    :src="src"
    alt="SCH PRO 360"
    :class="[sizeClass, 'w-auto max-w-[220px] object-contain object-left']"
    loading="lazy"
    decoding="async"
  />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  /** sm ~32px, md ~40px, lg ~48px height */
  size: { type: String, default: 'md' },
  /** White card behind logo for dark footers / headers */
  onDark: { type: Boolean, default: false },
  /** Override image URL (default: public asset) */
  src: { type: String, default: '/logo/schpro360.png' }
})

const sizeClass = computed(() => {
  const map = { sm: 'h-8', md: 'h-10', lg: 'h-12' }
  return map[props.size] || map.md
})
</script>
