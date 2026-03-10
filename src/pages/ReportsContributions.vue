<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Contributions Report</h1>
      <p class="text-sm text-gray-500">Contribution summary and thank-you status</p>
    </div>
    <ChartCard title="Contributions over time" />
  </div>
</template>

<script setup>
import ChartCard from '@/components/ChartCard.vue'
</script>
