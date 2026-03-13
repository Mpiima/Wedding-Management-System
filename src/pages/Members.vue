<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-wmis-text">Members</h1>
        <p class="text-sm text-gray-500">Member list and contact details. Every member belongs to a group category.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-if="authStore.can('members.add')" type="button" class="btn-gold" @click="openInviteModal">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
          Invite members
        </button>
        <button v-if="authStore.can('members.add')" type="button" class="btn-primary" @click="openModal()">+ Add member</button>
      </div>
    </div>

    <p v-if="store.errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">{{ store.errorMessage }}</p>

    <TableComponent title="Members" :columns="columns" :data="store.members" row-key="id" empty="No members yet">
      <template #cell-group_category_id="{ value }">{{ categoryName(value) }}</template>
      <template #cell-email="{ value }">{{ value || '—' }}</template>
      <template #cell-phone="{ value }">{{ value || '—' }}</template>
      <template #actions="{ row }">
        <template v-if="authStore.can('members.edit') || authStore.can('members.delete')">
          <button v-if="authStore.can('members.edit')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openModal(row)">Edit</button>
          <button v-if="authStore.can('members.delete')" type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium ml-2" @click="confirmDelete(row)">Delete</button>
        </template>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" :title="editingId ? 'Edit member' : 'Add member'" @update:model-value="editingId = null">
      <form class="space-y-4" @submit.prevent="save">
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Group category <span class="text-rose-500">*</span></label>
          <select v-model.number="form.group_category_id" required class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20">
            <option value="">Select category</option>
            <option v-for="c in categoryStore.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <p v-if="errors.group_category_id" class="text-xs text-rose-500">{{ errors.group_category_id }}</p>
        </div>
        <FormInput v-model="form.name" label="Name" placeholder="Full name" required :error="errors.name" />
        <FormInput v-model="form.email" label="Email" type="email" placeholder="Optional" />
        <FormInput v-model="form.phone" label="Phone" placeholder="Optional" />
        <p v-if="store.errorMessage" class="text-sm text-rose-600">{{ store.errorMessage }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-50" @click="showModal = false">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
        </div>
      </form>
    </ModalComponent>

    <ModalComponent v-model="showDeleteModal" title="Delete member" @update:model-value="deleteTarget = null">
      <p class="text-sm text-gray-600">Delete <strong>{{ deleteTarget?.name }}</strong>? This cannot be undone.</p>
      <div class="flex justify-end gap-2 pt-4">
        <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-50" @click="showDeleteModal = false">Cancel</button>
        <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="deleting" @click="doDelete">Delete</button>
      </div>
    </ModalComponent>

    <ModalComponent v-model="showInviteModal" title="Invite committee members">
      <p class="text-sm text-gray-600 mb-4">Send an invitation link to add committee members. Share the link below or enter email addresses to send invites.</p>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Invitation link</label>
          <div class="flex gap-2">
            <input :value="inviteLink" type="text" readonly class="flex-1 rounded-xl border border-rose-100 bg-rose-50/30 px-4 py-2.5 text-sm text-gray-700 select-all" />
            <button type="button" class="btn-primary shrink-0" @click="copyLink">{{ copied ? 'Copied!' : 'Copy link' }}</button>
          </div>
        </div>
        <div class="border-t border-rose-100/60 pt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Send via email</label>
          <p class="text-xs text-gray-500 mb-2">Enter one or more email addresses separated by commas.</p>
          <div class="flex gap-2">
            <input v-model="inviteEmails" type="text" placeholder="e.g. member@example.com" class="flex-1 rounded-xl border border-rose-100 bg-white px-4 py-2.5 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20" />
            <button type="button" class="btn-primary shrink-0" :disabled="!inviteEmails.trim()" @click="sendInvites">Send invite</button>
          </div>
          <p v-if="sendSuccess" class="mt-2 text-sm text-emerald-600">Invitation(s) sent successfully.</p>
        </div>
      </div>
      <template #footer>
        <button type="button" class="rounded-xl border border-rose-100 px-4 py-2 text-sm font-medium hover:bg-rose-50" @click="showInviteModal = false">Done</button>
      </template>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import { useMembersStore } from '@/stores/members'
import { useGroupCategoriesStore } from '@/stores/groupCategories'
import { useAuthStore } from '@/stores/auth'

const store = useMembersStore()
const categoryStore = useGroupCategoriesStore()
const authStore = useAuthStore()

const showModal = ref(false)
const showDeleteModal = ref(false)
const showInviteModal = ref(false)
const editingId = ref(null)
const deleteTarget = ref(null)
const saving = ref(false)
const deleting = ref(false)
const copied = ref(false)
const inviteEmails = ref('')
const sendSuccess = ref(false)
const inviteLink = ref('')

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'group_category_id', label: 'Group category' },
  { key: 'phone', label: 'Phone' },
  { key: 'email', label: 'Email' }
]

const form = reactive({ group_category_id: '', name: '', email: '', phone: '' })
const errors = reactive({ group_category_id: '', name: '' })

function categoryName(id) {
  const c = categoryStore.categories.find((x) => Number(x.id) === Number(id))
  return c ? c.name : '—'
}

function openModal(row = null) {
  store.clearError()
  errors.group_category_id = ''
  errors.name = ''
  if (row) {
    editingId.value = row.id
    form.group_category_id = row.group_category_id
    form.name = row.name || ''
    form.email = row.email || ''
    form.phone = row.phone || ''
  } else {
    editingId.value = null
    form.group_category_id = categoryStore.categories.length ? categoryStore.categories[0].id : ''
    form.name = ''
    form.email = ''
    form.phone = ''
  }
  showModal.value = true
}

async function save() {
  errors.group_category_id = ''
  errors.name = ''
  if (!form.group_category_id) { errors.group_category_id = 'Select a category'; return }
  if (!form.name.trim()) { errors.name = 'Name is required'; return }
  saving.value = true
  try {
    if (editingId.value) {
      await store.updateMember(editingId.value, {
        group_category_id: form.group_category_id,
        name: form.name.trim(),
        email: form.email.trim() || null,
        phone: form.phone.trim() || null
      })
    } else {
      await store.createMember({
        group_category_id: form.group_category_id,
        name: form.name.trim(),
        email: form.email.trim() || null,
        phone: form.phone.trim() || null
      })
    }
    showModal.value = false
  } finally {
    saving.value = false
  }
}

function confirmDelete(row) {
  deleteTarget.value = row
  showDeleteModal.value = true
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await store.deleteMember(deleteTarget.value.id)
    showDeleteModal.value = false
    deleteTarget.value = null
  } finally {
    deleting.value = false
  }
}

function openInviteModal() {
  inviteLink.value = typeof window !== 'undefined' ? `${window.location.origin}/join/committee?token=wm-${Math.random().toString(36).slice(2, 10)}` : ''
  showInviteModal.value = true
}

function copyLink() {
  if (!inviteLink.value) return
  navigator.clipboard.writeText(inviteLink.value).then(() => {
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  })
}

function sendInvites() {
  const emails = inviteEmails.value.split(',').map((e) => e.trim()).filter(Boolean)
  if (!emails.length) return
  sendSuccess.value = true
  setTimeout(() => { sendSuccess.value = false; inviteEmails.value = '' }, 3000)
}

onMounted(async () => {
  await categoryStore.fetchCategories().catch(() => {})
  await store.fetchMembers().catch(() => {})
})
</script>
