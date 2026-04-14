<template>
  <header
    class="flex flex-col gap-6 border-b border-sc-border bg-gradient-to-b from-transparent to-transparent pb-8 transition-colors duration-300 lg:flex-row lg:items-end lg:justify-between lg:gap-8"
  >
    <div class="min-w-0 space-y-2">
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-[1.75rem] sm:leading-tight lg:text-3xl">
        {{ title }}
      </h1>
      <p v-if="description" class="max-w-2xl text-sm leading-relaxed text-slate-500 lg:text-[0.9375rem]">
        {{ description }}
      </p>
    </div>
    <div
      v-if="$slots.actions"
      class="flex shrink-0 flex-wrap items-center gap-2.5 sm:justify-end lg:pb-0.5"
    >
      <slot name="actions" />
    </div>
  </header>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' }
})
</script>
