<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Upload Media</h1>
      <p class="text-sm text-gray-500">Drag and drop uploader with image preview</p>
    </div>
    <FileUploader accept="image/*" :multiple="true" />
  </div>
</template>

<script setup>
import FileUploader from '@/components/FileUploader.vue'
</script>
