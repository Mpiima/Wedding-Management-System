<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Meeting Minutes</h1>
        <p class="text-sm text-gray-500">Record minutes for an already-scheduled meeting. Create meetings first under Meetings.</p>
      </div>
      <button
        v-if="authStore.can('meeting_minutes.add')"
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
      <p class="mt-1 text-xs text-gray-400">Schedule a meeting first, then add minutes by selecting that meeting.</p>
      <router-link v-if="authStore.can('meeting_minutes.add')" to="/meetings" class="inline-block mt-4 rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600">Go to Meetings</router-link>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="m in minutesStore.minutes"
        :key="m.id"
        class="rounded-2xl border border-rose-100/60 bg-white p-5 shadow-card hover:border-rose-200/60"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0 flex-1">
            <h2 class="font-display text-lg font-semibold text-wmis-text">{{ m.meeting_title || 'Meeting' }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ formatDate(m.meeting_date) }}</p>
            <div class="mt-3 text-sm text-gray-700 whitespace-pre-wrap">{{ m.content || '—' }}</div>
          </div>
          <div class="flex shrink-0 gap-2">
            <button v-if="authStore.can('meeting_minutes.edit')" type="button" class="text-rose-500 hover:text-rose-600 text-sm font-medium" @click="openForm(m)">Edit</button>
            <button v-if="authStore.can('meeting_minutes.delete')" type="button" class="text-gray-500 hover:text-rose-600 text-sm font-medium" @click="confirmDelete(m)">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <ModalComponent v-model="showModal" :title="editingId ? 'Edit minutes' : 'New minutes'">
      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-1" v-if="!editingId">
          <label class="block text-sm font-medium text-gray-700">Meeting *</label>
          <select
            v-model="form.meeting_id"
            required
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm text-wmis-text focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          >
            <option value="">Select a meeting</option>
            <option v-for="mtg in meetingsWithoutMinutes" :key="mtg.id" :value="mtg.id">
              {{ mtg.title }} — {{ formatDate(mtg.meeting_date) }}
            </option>
          </select>
          <p v-if="meetingsStore.meetings.length && !meetingsWithoutMinutes.length" class="text-xs text-amber-600 mt-1">All meetings already have minutes. Add a new meeting first.</p>
        </div>
        <p v-else class="text-sm text-gray-600">Meeting: <strong>{{ editingMeetingTitle }}</strong></p>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Minutes / Notes</label>
          <textarea
            v-model="form.content"
            rows="8"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm text-wmis-text placeholder-gray-500 transition-all duration-200 focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
            placeholder="Meeting notes..."
          />
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showModal = false">Cancel</button>
          <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600" :disabled="!canSave" @click="submit">Save</button>
        </div>
      </template>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import ModalComponent from '@/components/ModalComponent.vue'
import { useMeetingMinutesStore } from '@/stores/meetingMinutes'
import { useMeetingsStore } from '@/stores/meetings'
import { useAuthStore } from '@/stores/auth'

const minutesStore = useMeetingMinutesStore()
const meetingsStore = useMeetingsStore()
const authStore = useAuthStore()

const showModal = ref(false)
const editingId = ref(null)
const form = ref({ meeting_id: '', content: '' })

const meetingsWithoutMinutes = computed(() => {
  const withMinutes = new Set((minutesStore.minutes || []).map((m) => Number(m.meeting_id)))
  return (meetingsStore.meetings || []).filter((m) => !withMinutes.has(Number(m.id)))
})

const editingMeetingTitle = computed(() => {
  if (!editingId.value) return ''
  const m = minutesStore.minutes.find((x) => Number(x.id) === Number(editingId.value))
  return m ? m.meeting_title : ''
})

const canSave = computed(() => {
  if (editingId.value) return true
  return form.value.meeting_id && Number(form.value.meeting_id) > 0
})

function formatDate(d) {
  return new Date((d || '') + 'Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function openForm(row = null) {
  editingId.value = row ? row.id : null
  form.value = {
    meeting_id: row ? row.meeting_id : '',
    content: row ? row.content || '' : ''
  }
  showModal.value = true
}

async function submit() {
  if (!canSave.value) return
  try {
    if (editingId.value) {
      await minutesStore.updateMinutes(editingId.value, { content: form.value.content })
    } else {
      await minutesStore.createMinutes({ meeting_id: Number(form.value.meeting_id), content: form.value.content })
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
  meetingsStore.fetchMeetings().catch(() => {})
})
</script>
