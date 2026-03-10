<template>
  <div class="card-luxury">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="font-display text-sm font-semibold text-wmis-text">{{ title }}</h3>
      <slot name="actions" />
    </div>
    <div class="h-40 flex items-center justify-center rounded-xl bg-gradient-rose-subtle border border-rose-100">
      <div class="text-center text-gray-400">
        <svg class="mx-auto h-10 w-10 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
        <p class="mt-2 text-xs font-medium">Chart placeholder</p>
        <p class="text-[10px]">Connect your data source</p>
      </div>
    </div>
    <p v-if="footer" class="mt-2 text-xs text-gray-500">{{ footer }}</p>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: 'Chart' },
  footer: { type: String, default: '' }
})
</script>
