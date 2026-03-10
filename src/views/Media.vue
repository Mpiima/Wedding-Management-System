<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Media</h1>
        <p class="text-sm text-slate-500">
          Upload and organize photos, inspiration boards, and documents.
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <select
          v-model="filterCategory"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-pink-200"
        >
          <option value="">All categories</option>
          <option value="Inspiration">Inspiration</option>
          <option value="Venue">Venue</option>
          <option value="Decor">Decor</option>
          <option value="Attire">Attire</option>
          <option value="Other">Other</option>
        </select>
        <button
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-pink-500 text-white text-sm font-medium shadow-sm hover:bg-pink-600"
          @click="showUploadArea = true"
        >
          ➕ Upload
        </button>
      </div>
    </div>

    <!-- Drag & drop upload area -->
    <section
      v-if="showUploadArea"
      class="rounded-2xl border-2 border-dashed transition-colors"
      :class="dropActive ? 'border-pink-400 bg-pink-50/50' : 'border-slate-200 bg-white'"
    >
      <div
        class="p-6 flex flex-col items-center justify-center text-center cursor-pointer"
        @dragover.prevent="onDragOver"
        @dragleave.prevent="onDragLeave"
        @drop.prevent="onDrop"
        @click="triggerFileSelect"
      >
        <div class="h-12 w-12 rounded-full bg-pink-50 text-pink-500 flex items-center justify-center text-2xl mb-3">
          ⬆
        </div>
        <p class="text-sm font-medium text-slate-800 mb-1">
          Drag & drop images or click to browse
        </p>
        <p class="text-xs text-slate-400 mb-3">
          PNG, JPG, PDF. Add a category and optional caption after upload.
        </p>
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          multiple
          accept=".png,.jpg,.jpeg,.webp,.pdf"
          @change="onFileChange"
        />
        <div v-if="pendingUploads.length" class="w-full max-w-md space-y-2 pt-3 border-t border-slate-100">
          <p class="text-xs text-slate-500">Ready to add ({{ pendingUploads.length }}):</p>
          <div class="flex flex-wrap gap-2 justify-center">
            <span
              v-for="(file, i) in pendingUploads"
              :key="i"
              class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-slate-100 text-xs text-slate-600"
            >
              {{ file.name }}
              <button
                type="button"
                class="text-slate-400 hover:text-rose-500"
                @click.stop="removePending(i)"
              >
                ✕
              </button>
            </span>
          </div>
          <div class="flex gap-2 justify-center pt-2">
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50"
              @click="clearPending"
            >
              Clear
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg bg-pink-500 text-white text-xs font-medium hover:bg-pink-600"
              @click="confirmUploads"
            >
              Add to library
            </button>
          </div>
        </div>
        <button
          type="button"
          class="mt-3 text-xs text-slate-400 hover:text-slate-600"
          @click.stop="showUploadArea = false"
        >
          Close upload
        </button>
      </div>
    </section>

    <!-- Summary -->
    <section class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-slate-100 bg-white p-4 flex items-center gap-3">
        <span class="h-9 w-9 rounded-xl bg-pink-50 text-pink-500 flex items-center justify-center text-sm">📷</span>
        <div>
          <p class="text-xs text-slate-400">Images</p>
          <p class="text-lg font-semibold text-slate-800">{{ imageCount }}</p>
        </div>
      </div>
      <div class="rounded-2xl border border-slate-100 bg-white p-4 flex items-center gap-3">
        <span class="h-9 w-9 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-sm">📄</span>
        <div>
          <p class="text-xs text-slate-400">Documents</p>
          <p class="text-lg font-semibold text-slate-800">{{ docCount }}</p>
        </div>
      </div>
      <div class="rounded-2xl border border-slate-100 bg-white p-4 flex items-center gap-3 col-span-2 sm:col-span-2">
        <span class="h-9 w-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-sm">📁</span>
        <div>
          <p class="text-xs text-slate-400">Total items</p>
          <p class="text-lg font-semibold text-slate-800">{{ filteredItems.length }}</p>
        </div>
      </div>
    </section>

    <!-- Media grid -->
    <section class="rounded-2xl border border-slate-100 bg-white overflow-hidden shadow-sm">
      <div class="p-4 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-800">Library</h2>
        <p class="text-xs text-slate-400">Click an item to edit category or caption.</p>
      </div>
      <div v-if="filteredItems.length" class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        <div
          v-for="item in filteredItems"
          :key="item.id"
          class="group relative rounded-xl border border-slate-100 bg-slate-50/50 overflow-hidden aspect-square"
        >
          <!-- Placeholder for image/document -->
          <div
            class="absolute inset-0 flex items-center justify-center text-4xl"
            :class="item.type === 'document' ? 'bg-blue-50 text-blue-400' : 'bg-pink-50/50 text-pink-300'"
          >
            {{ item.type === 'document' ? '📄' : '🖼' }}
          </div>
          <div class="absolute inset-x-0 bottom-0 p-2 bg-gradient-to-t from-slate-900/70 to-transparent">
            <p class="text-xs font-medium text-white truncate">{{ item.name }}</p>
            <p class="text-[10px] text-white/80">{{ item.category || 'Uncategorized' }}</p>
          </div>
          <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition flex gap-1">
            <button
              type="button"
              class="h-7 w-7 rounded-lg bg-white/90 text-slate-600 flex items-center justify-center text-xs shadow"
              @click="openEditModal(item)"
              title="Edit"
            >
              ✎
            </button>
            <button
              type="button"
              class="h-7 w-7 rounded-lg bg-white/90 text-rose-500 flex items-center justify-center text-xs shadow"
              @click="removeItem(item.id)"
              title="Remove"
            >
              ✕
            </button>
          </div>
        </div>
      </div>
      <div v-else class="p-8 text-center">
        <p class="text-sm text-slate-400">No media yet. Upload inspiration photos or documents above.</p>
      </div>
    </section>

    <!-- Edit modal -->
    <transition name="fade">
      <div
        v-if="editModalOpen"
        class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 px-4"
      >
        <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl p-5 space-y-4">
          <div class="flex items-start justify-between">
            <h2 class="text-sm font-semibold text-slate-800">Edit media</h2>
            <button
              class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 text-slate-400"
              @click="editModalOpen = false"
            >
              ✕
            </button>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Category</label>
            <select
              v-model="editingItem.category"
              class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200"
            >
              <option value="">Uncategorized</option>
              <option value="Inspiration">Inspiration</option>
              <option value="Venue">Venue</option>
              <option value="Decor">Decor</option>
              <option value="Attire">Attire</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Caption</label>
            <textarea
              v-model="editingItem.caption"
              rows="2"
              class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-pink-200"
            ></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              class="px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50"
              @click="editModalOpen = false"
            >
              Cancel
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-lg bg-pink-500 text-white text-xs font-medium hover:bg-pink-600"
              @click="saveEdit"
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const mediaItems = ref([
  { id: 1, name: 'Floral inspiration.jpg', type: 'image', category: 'Inspiration', caption: 'Peony centerpieces' },
  { id: 2, name: 'Venue layout.pdf', type: 'document', category: 'Venue', caption: '' },
  { id: 3, name: 'Table setting.png', type: 'image', category: 'Decor', caption: 'Rustic style' },
  { id: 4, name: 'Dress reference.jpg', type: 'image', category: 'Attire', caption: '' }
])

const filterCategory = ref('')
const showUploadArea = ref(false)
const dropActive = ref(false)
const fileInput = ref(null)
const pendingUploads = ref([])
const editModalOpen = ref(false)
const editingItem = ref({ id: null, name: '', category: '', caption: '' })

const filteredItems = computed(() => {
  if (!filterCategory.value) return mediaItems.value
  return mediaItems.value.filter((m) => m.category === filterCategory.value)
})

const imageCount = computed(() => mediaItems.value.filter((m) => m.type === 'image').length)
const docCount = computed(() => mediaItems.value.filter((m) => m.type === 'document').length)

const onDragOver = () => { dropActive.value = true }
const onDragLeave = () => { dropActive.value = false }
const onDrop = (e) => {
  dropActive.value = false
  pendingUploads.value.push(...Array.from(e.dataTransfer.files))
}
const triggerFileSelect = () => fileInput.value?.click()
const onFileChange = (e) => {
  pendingUploads.value.push(...Array.from(e.target.files || []))
  e.target.value = ''
}
const removePending = (index) => {
  pendingUploads.value.splice(index, 1)
}
const clearPending = () => {
  pendingUploads.value = []
}
const confirmUploads = () => {
  pendingUploads.value.forEach((file) => {
    const isDoc = /\.pdf$/i.test(file.name)
    const newId = Math.max(0, ...mediaItems.value.map((m) => m.id)) + 1
    mediaItems.value.push({
      id: newId,
      name: file.name,
      type: isDoc ? 'document' : 'image',
      category: '',
      caption: ''
    })
  })
  pendingUploads.value = []
  showUploadArea.value = false
}

const openEditModal = (item) => {
  editingItem.value = { ...item }
  editModalOpen.value = true
}
const saveEdit = () => {
  const index = mediaItems.value.findIndex((m) => m.id === editingItem.value.id)
  if (index !== -1) mediaItems.value[index] = { ...editingItem.value }
  editModalOpen.value = false
}
const removeItem = (id) => {
  mediaItems.value = mediaItems.value.filter((m) => m.id !== id)
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
