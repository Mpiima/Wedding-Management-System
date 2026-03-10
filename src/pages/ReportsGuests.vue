<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Guest Report</h1>
      <p class="text-sm text-gray-500">RSVP and attendance reports</p>
    </div>
    <ChartCard title="Guest status breakdown" />
  </div>
</template>

<script setup>
import ChartCard from '@/components/ChartCard.vue'
</script>
