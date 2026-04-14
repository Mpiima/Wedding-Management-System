<template>
  <button
    :type="type"
    :disabled="disabled"
    class="tap-highlight-none inline-flex h-10 min-h-[2.5rem] shrink-0 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold leading-none transition-all duration-200 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
    :class="variantClass"
    @click="$emit('click', $event)"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: { type: String, default: 'primary' },
  type: { type: String, default: 'button' },
  disabled: { type: Boolean, default: false }
})

defineEmits(['click'])

const variantClass = computed(() => {
  if (props.variant === 'secondary') {
    return [
      'border border-sc-line bg-white text-slate-800 shadow-soft',
      'hover:-translate-y-px hover:border-slate-300 hover:bg-slate-50 hover:shadow-card',
      'active:translate-y-0 active:scale-[0.99]',
      'focus-visible:ring-brand-500/25'
    ].join(' ')
  }
  if (props.variant === 'ghost') {
    return [
      'h-auto min-h-0 rounded-lg px-2.5 py-1.5 text-slate-600',
      'transition-colors duration-150 ease-out',
      'hover:bg-slate-100/90 hover:text-slate-900',
      'active:scale-[0.98]',
      'focus-visible:ring-brand-500/20'
    ].join(' ')
  }
  return [
    'bg-brand-600 text-white shadow-soft',
    'hover:-translate-y-px hover:bg-brand-700 hover:shadow-card-hover',
    'active:translate-y-0 active:scale-[0.98] active:shadow-soft',
    'focus-visible:ring-brand-500/40'
  ].join(' ')
})
</script>
