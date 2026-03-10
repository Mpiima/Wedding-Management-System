<template>
  <PagePlaceholder
    title="Meeting Minutes"
    subtitle="Rich text editor and structured meeting minutes template"
    icon="📝"
    empty-title="No minutes yet"
    empty-subtitle="Create minutes from a meeting to get started."
  >
    <template #action>
      <button type="button" class="mt-4 rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600">+ New minutes</button>
    </template>
  </PagePlaceholder>
</template>

<script setup>
import PagePlaceholder from '@/components/PagePlaceholder.vue'
</script>
