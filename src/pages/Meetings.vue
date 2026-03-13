<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Meetings</h1>
        <p class="text-sm text-gray-500">Schedule meetings and set the agenda. Add minutes later from Meeting Minutes.</p>
      </div>
      <button
        v-if="authStore.can('meeting_minutes.add')"
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
        @click="openForm()"
      >
        + Schedule meeting
      </button>
    </div>

    <p v-if="meetingsStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ meetingsStore.errorMessage }}</p>

    <TableComponent
      title="Meetings"
      :columns="columns"
      :data="meetingsStore.meetings"
      row-key="id"
      :pagination="true"
      :page-size="10"
      v-model:current-page="currentPage"
      empty="No meetings yet"
    >
      <template #cell-title="{ value }">{{ value || '—' }}</template>
      <template #cell-meeting_date="{ value }">{{ value ? formatDate(value) : '—' }}</template>
      <template #cell-agenda="{ value }">{{ contentPreview(value) }}</template>
      <template #actions="{ row }">
        <div class="flex items-center gap-2">
          <button
            v-if="authStore.can('meeting_minutes.edit')"
            type="button"
            class="text-rose-500 hover:text-rose-600 text-xs font-medium"
            @click="openForm(row)"
          >
            Edit
          </button>
          <button
            v-if="authStore.can('meeting_minutes.delete')"
            type="button"
            class="text-gray-500 hover:text-rose-600 text-xs font-medium"
            @click="confirmDelete(row)"
          >
            Delete
          </button>
        </div>
      </template>
    </TableComponent>

    <!-- Add/Edit meeting modal -->
    <ModalComponent v-model="showModal" :title="editingId ? 'Edit meeting' : 'Schedule meeting'">
      <form class="space-y-4" @submit.prevent="submit">
        <FormInput v-model="form.title" label="Title *" placeholder="e.g. March 2026 Committee Meeting" required />
        <FormInput v-model="form.meeting_date" label="Meeting date *" type="date" required />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Agenda</label>
          <textarea
            v-model="form.agenda"
            rows="6"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm text-wmis-text placeholder-gray-500 transition-all duration-200 focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
            placeholder="Meeting agenda / topics to cover..."
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
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import { useMeetingsStore } from '@/stores/meetings'
import { useAuthStore } from '@/stores/auth'

const meetingsStore = useMeetingsStore()
const authStore = useAuthStore()

const currentPage = ref(1)
const showModal = ref(false)
const editingId = ref(null)
const form = ref({ title: '', meeting_date: '', agenda: '' })

const columns = [
  { key: 'title', label: 'Title' },
  { key: 'meeting_date', label: 'Meeting date' },
  { key: 'agenda', label: 'Agenda' }
]

function today() {
  return new Date().toISOString().slice(0, 10)
}

function formatDate(d) {
  return new Date(d + 'Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function contentPreview(text) {
  if (!text || !String(text).trim()) return '—'
  const s = String(text).trim()
  return s.length > 80 ? s.slice(0, 80) + '…' : s
}

function openForm(row = null) {
  editingId.value = row ? row.id : null
  form.value = {
    title: row ? row.title : '',
    meeting_date: row && row.meeting_date ? row.meeting_date.slice(0, 10) : today(),
    agenda: row ? row.agenda || '' : ''
  }
  showModal.value = true
}

async function submit() {
  try {
    if (editingId.value) {
      await meetingsStore.updateMeeting(editingId.value, form.value)
    } else {
      await meetingsStore.createMeeting(form.value)
    }
    showModal.value = false
  } catch (_) {}
}

function confirmDelete(row) {
  if (!confirm('Delete this meeting? Any minutes for it will also be removed.')) return
  meetingsStore.deleteMeeting(row.id).catch(() => {})
}

onMounted(() => {
  meetingsStore.fetchMeetings().catch(() => {})
})
</script>
