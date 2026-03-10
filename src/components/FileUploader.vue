<template>
  <div
    class="rounded-2xl border-2 border-dashed transition-colors"
    :class="[
      dragActive ? 'border-rose-500 bg-rose-50/50' : 'border-gray-200 bg-gray-50/50 hover:border-rose-300 hover:bg-rose-50/30',
      disabled && 'pointer-events-none opacity-60'
    ]"
    @dragover.prevent="dragActive = true"
    @dragleave.prevent="dragActive = false"
    @drop.prevent="onDrop"
  >
    <label class="flex cursor-pointer flex-col items-center justify-center px-6 py-10 text-center">
      <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-500 mb-3">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
      </span>
      <span class="text-sm font-medium text-wmis-text">Drop files here or click to browse</span>
      <span class="mt-1 text-xs text-gray-500">{{ accept }}</span>
      <input
        ref="inputRef"
        type="file"
        class="hidden"
        :accept="accept"
        :multiple="multiple"
        :disabled="disabled"
        @change="onChange"
      />
    </label>
    <div v-if="files.length" class="border-t border-gray-200 px-4 py-3">
      <p class="text-xs font-medium text-gray-500 mb-2">Selected ({{ files.length }})</p>
      <ul class="space-y-1 text-xs text-gray-600">
        <li v-for="(f, i) in files" :key="i" class="flex items-center justify-between gap-2">
          <span class="truncate">{{ f.name }}</span>
          <button
            type="button"
            class="text-rose-500 hover:text-rose-600"
            @click="removeFile(i)"
          >
            ×
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  accept: { type: String, default: 'image/*,.pdf' },
  multiple: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false }
})

const emit = defineEmits(['change'])

const inputRef = ref(null)
const dragActive = ref(false)
const files = ref([])

function addFiles(fileList) {
  const newFiles = Array.from(fileList || [])
  files.value = props.multiple ? [...files.value, ...newFiles] : newFiles
  emit('change', files.value)
}

function onDrop(e) {
  dragActive.value = false
  addFiles(e.dataTransfer.files)
}

function onChange(e) {
  addFiles(e.target.files)
  e.target.value = ''
}

function removeFile(index) {
  files.value.splice(index, 1)
  emit('change', files.value)
}
</script>
