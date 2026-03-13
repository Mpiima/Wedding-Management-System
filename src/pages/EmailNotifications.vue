<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Email Notifications</h1>
      <p class="text-sm text-gray-500">Send an email to all members in a group. Configure BCC/copy-to in Settings → Email.</p>
    </div>

    <div class="rounded-2xl bg-white shadow-card border border-gray-100 p-6 max-w-3xl">
      <form class="space-y-4" @submit.prevent="send">
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Group *</label>
          <select
            v-model="selectedGroupId"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
            :disabled="loadingGroups"
          >
            <option value="">Select a group</option>
            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
          </select>
          <p v-if="loadingMembers" class="text-xs text-rose-600 mt-1">Loading members…</p>
          <p v-else-if="selectedGroupId && membersInGroup.length >= 0" class="text-xs text-gray-500 mt-1">
            {{ membersWithEmail.length }} member(s) with email will receive this message.
          </p>
        </div>

        <div v-if="membersInGroup.length > 0" class="rounded-xl border border-gray-100 bg-gray-50/50 p-3">
          <p class="text-xs font-medium text-gray-600 mb-2">Recipients ({{ membersWithEmail.length }} with email)</p>
          <ul class="text-xs text-gray-600 space-y-1 max-h-32 overflow-y-auto">
            <li v-for="m in membersInGroup" :key="m.id">
              {{ m.name }}
              <span :class="m.email && m.email.trim() ? 'text-gray-500' : 'text-amber-600'">
                {{ m.email && m.email.trim() ? m.email : '(no email)' }}
              </span>
            </li>
          </ul>
        </div>

        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Subject *</label>
          <input
            v-model="subject"
            type="text"
            placeholder="Email subject"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          />
        </div>

        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Message (HTML)</label>
          <div class="rounded-xl border border-rose-100 bg-rose-50/20 overflow-hidden focus-within:ring-2 focus-within:ring-rose-500/20 focus-within:border-rose-400">
            <div class="flex items-center gap-1 px-2 py-1.5 border-b border-rose-100 bg-white/80">
              <button type="button" class="p-1.5 rounded text-gray-600 hover:bg-rose-100 hover:text-rose-700" title="Bold" @click="format('bold')">
                <span class="font-bold text-sm">B</span>
              </button>
              <button type="button" class="p-1.5 rounded text-gray-600 hover:bg-rose-100 hover:text-rose-700 italic text-sm" title="Italic" @click="format('italic')">I</button>
              <button type="button" class="p-1.5 rounded text-gray-600 hover:bg-rose-100 hover:text-rose-700 text-sm underline" title="Insert link" @click="insertLink">Link</button>
            </div>
            <div
              ref="editorRef"
              contenteditable="true"
              class="min-h-[180px] px-4 py-3 text-sm text-gray-800 focus:outline-none prose prose-sm max-w-none"
              data-placeholder="Type your message here…"
              @input="onEditorInput"
            />
          </div>
        </div>

        <p v-if="errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">{{ errorMessage }}</p>
        <p v-if="successMessage" class="text-sm text-green-600 bg-green-50 rounded-xl px-4 py-2">{{ successMessage }}</p>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="submit"
            class="rounded-xl bg-rose-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50 inline-flex items-center gap-2"
            :disabled="sending || !selectedGroupId || !subject.trim() || membersWithEmail.length === 0"
          >
            <span v-if="sending" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            {{ sending ? 'Sending…' : 'Send email' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '@/config/api.js'
import { useGroupCategoriesStore } from '@/stores/groupCategories'

const groupStore = useGroupCategoriesStore()
const groups = computed(() => groupStore.categories)
const loadingGroups = ref(false)
const selectedGroupId = ref('')
const membersInGroup = ref([])
const loadingMembers = ref(false)
const subject = ref('')
const bodyHtml = ref('')
const editorRef = ref(null)
const sending = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const membersWithEmail = computed(() => {
  return membersInGroup.value.filter((m) => {
    const e = (m.email || '').trim()
    return e && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)
  })
})

async function loadGroups() {
  loadingGroups.value = true
  try {
    await groupStore.fetchCategories()
  } finally {
    loadingGroups.value = false
  }
}

async function loadMembersForGroup(groupId) {
  if (!groupId) {
    membersInGroup.value = []
    return
  }
  loadingMembers.value = true
  try {
    const r = await api.get('members.php', { params: { group_category_id: groupId } })
    membersInGroup.value = r?.data?.data ?? []
  } catch (_) {
    membersInGroup.value = []
  } finally {
    loadingMembers.value = false
  }
}

watch(selectedGroupId, (id) => {
  loadMembersForGroup(id ? Number(id) : 0)
})

function onEditorInput() {
  if (editorRef.value) bodyHtml.value = editorRef.value.innerHTML
}

function format(cmd) {
  document.execCommand(cmd, false, null)
  editorRef.value?.focus()
  if (editorRef.value) bodyHtml.value = editorRef.value.innerHTML
}

function insertLink() {
  const url = prompt('Enter URL:')
  if (url == null || url.trim() === '') return
  document.execCommand('createLink', false, url.trim())
  editorRef.value?.focus()
  if (editorRef.value) bodyHtml.value = editorRef.value.innerHTML
}

async function send() {
  if (!selectedGroupId.value || !subject.value.trim() || membersWithEmail.value.length === 0) return
  errorMessage.value = ''
  successMessage.value = ''
  const html = editorRef.value ? editorRef.value.innerHTML : bodyHtml.value
  if (!html || html.trim() === '' || html === '<br>') {
    errorMessage.value = 'Please enter a message.'
    return
  }
  sending.value = true
  try {
    const r = await api.post('send-notification-email.php', {
      group_id: Number(selectedGroupId.value),
      subject: subject.value.trim(),
      body_html: html
    })
    const sent = r?.data?.sent ?? 0
    const failed = r?.data?.failed ?? 0
    successMessage.value = `Email sent to ${sent} recipient(s).` + (failed > 0 ? ` ${failed} failed.` : '')
    setTimeout(() => { successMessage.value = '' }, 5000)
    if (editorRef.value) {
      editorRef.value.innerHTML = ''
      bodyHtml.value = ''
    }
    subject.value = ''
  } catch (e) {
    errorMessage.value = e?.response?.data?.error || e?.message || 'Failed to send'
  } finally {
    sending.value = false
  }
}

onMounted(() => {
  loadGroups()
})
</script>

<style scoped>
[contenteditable]:empty::before {
  content: attr(data-placeholder);
  color: #9ca3af;
}
</style>
