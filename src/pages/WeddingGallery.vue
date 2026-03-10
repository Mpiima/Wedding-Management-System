<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Wedding Gallery</h1>
        <p class="text-sm text-gray-500">Upload and organize wedding photos</p>
      </div>
      <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600" @click="showUpload = true">+ Upload</button>
    </div>

    <FileUploader v-if="showUpload" class="mb-6" @change="onUpload" />

    <PhotoGrid :items="items" :columns="4" @select="selected = $event" />

    <ModalComponent v-model="showPreview" title="Photo">
      <div v-if="selected" class="text-center text-gray-500">
        <div class="aspect-square rounded-xl bg-gray-100 flex items-center justify-center text-6xl">🖼</div>
        <p class="mt-2 text-sm">{{ selected.caption || selected.name }}</p>
      </div>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import FileUploader from '@/components/FileUploader.vue'
import PhotoGrid from '@/components/PhotoGrid.vue'
import ModalComponent from '@/components/ModalComponent.vue'

const showUpload = ref(false)
const showPreview = ref(false)
const selected = ref(null)

watch(selected, (v) => { if (v) showPreview.value = true })

const items = ref([
  { id: 1, name: 'Floral inspiration', caption: 'Peony centerpieces', url: '' },
  { id: 2, name: 'Venue layout', caption: '', url: '' }
])

function onUpload(files) {
  showUpload.value = false
  files.forEach((f, i) => items.value.push({ id: Date.now() + i, name: f.name, caption: '', url: '' }))
}
</script>
