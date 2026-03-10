<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Meeting Minutes</h1>
        <p class="text-sm text-gray-500">Record and view meeting minutes</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
        @click="openForm()"
      >
        + New minutes
      </button>
    </div>

    <p v-if="minutesStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ minutesStore.errorMessage }}</p>

    <div v-if="!minutesStore.minutes.length" class="card-luxury px-6 py-14 text-center">
      <p class="font-display text-sm font-medium text-gray-500">No minutes yet</p>
      <p class="mt-1 text-xs text-gray-400">Create minutes from a meeting to get started.</p>
      <button type="button" class="mt-4 rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600" @click="openForm()">+ New minutes</button>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="m in minutesStore.minutes"
        :key="m.id"
        class="rounded-2xl border border-rose-100/60 bg-white p-5 shadow-card hover:border-rose-200/60"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0 flex-1">
            <h2 class="font-display text-lg font-semibold text-wmis-text">{{ m.title }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ formatDate(m.meeting_date) }}</p>
            <div class="mt-3 text-sm text-gray-700 whitespace-pre-wrap">{{ m.content || '—' }}</div>
          </div>
          <div class="flex shrink-0 gap-2">
            <button type="button" class="text-rose-500 hover:text-rose-600 text-sm font-medium" @click="openForm(m)">Edit</button>
            <button type="button" class="text-gray-500 hover:text-rose-600 text-sm font-medium" @click="confirmDelete(m)">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <ModalComponent v-model="showModal" :title="editingId ? 'Edit minutes' : 'New minutes'">
      <form class="space-y-4" @submit.prevent="submit">
        <FormInput v-model="form.title" label="Title *" placeholder="e.g. March 2026 Committee Meeting" required />
        <FormInput v-model="form.meeting_date" label="Meeting date *" type="date" required />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Content</label>
          <textarea
            v-model="form.content"
            rows="8"
            class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
            placeholder="Meeting notes..."
          />
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showModal = false">Cancel</button>
          <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600" @click="submit">Save</button>
        </div>
      </template>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import { useMeetingMinutesStore } from '@/stores/meetingMinutes'

const minutesStore = useMeetingMinutesStore()

const showModal = ref(false)
const editingId = ref(null)
const form = ref({ title: '', meeting_date: '', content: '' })

function today() { return new Date().toISOString().slice(0, 10) }

function formatDate(d) {
  return new Date(d + 'Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function openForm(row = null) {
  editingId.value = row ? row.id : null
  form.value = {
    title: row ? row.title : '',
    meeting_date: row && row.meeting_date ? row.meeting_date.slice(0, 10) : today(),
    content: row ? row.content || '' : ''
  }
  showModal.value = true
}

async function submit() {
  try {
    if (editingId.value) {
      await minutesStore.updateMinutes(editingId.value, form.value)
    } else {
      await minutesStore.createMinutes(form.value)
    }
    showModal.value = false
  } catch (_) {}
}

function confirmDelete(row) {
  if (!confirm('Delete these minutes?')) return
  minutesStore.deleteMinutes(row.id).catch(() => {})
}

onMounted(() => {
  minutesStore.fetchMinutes().catch(() => {})
})
</script>
