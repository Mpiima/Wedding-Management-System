<template>
  <div class="space-y-8">
    <div class="flex flex-col gap-1">
      <h1 class="font-display text-2xl font-semibold tracking-tight text-wmis-text">{{ title }}</h1>
      <p v-if="subtitle" class="text-sm text-gray-500">{{ subtitle }}</p>
    </div>
    <div class="card-luxury px-6 py-14 text-center">
      <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-rose-subtle border border-rose-100 text-4xl text-rose-300">{{ icon }}</div>
      <p class="mt-5 font-display text-sm font-medium text-gray-500">{{ emptyTitle }}</p>
      <p class="mt-1 text-xs text-gray-400">{{ emptySubtitle }}</p>
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  icon: { type: String, default: '📄' },
  emptyTitle: { type: String, default: 'No data yet' },
  emptySubtitle: { type: String, default: 'Get started by adding your first item.' }
})
</script>
