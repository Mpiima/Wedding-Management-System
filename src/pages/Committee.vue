<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Committee</h1>
        <p class="text-sm text-gray-500">Assign roles to members. List of members with their roles.</p>
      </div>
      <button v-if="authStore.can('committee.assign')"
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600"
        @click="showAssignModal = true"
      >
        Assign role
      </button>
    </div>

    <p v-if="store.errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">{{ store.errorMessage }}</p>

    <div class="rounded-2xl border border-rose-100/60 bg-white shadow-card overflow-hidden">
      <div class="border-b border-rose-100/60 px-5 py-4 bg-white/50">
        <h2 class="font-display text-sm font-semibold text-wmis-text">Members with roles</h2>
      </div>
      <div class="divide-y divide-rose-100/50">
        <div
          v-for="item in store.list"
          :key="item.member_id"
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 hover:bg-rose-50/40"
        >
          <div class="min-w-0">
            <p class="font-medium text-wmis-text">{{ item.member_name }}</p>
            <p class="text-sm text-gray-500">{{ item.group_category_name }}</p>
            <p v-if="item.email" class="text-xs text-gray-500">{{ item.email }}</p>
            <p v-if="item.phone" class="text-xs text-gray-500">{{ item.phone }}</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <span
              v-for="role in item.roles"
              :key="role.id"
              class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700"
            >
              {{ role.name }}
              <button
                v-if="authStore.can('committee.unassign')"
                type="button"
                class="rounded-full p-0.5 hover:bg-rose-200/50"
                aria-label="Remove role"
                @click="askUnassign(item.member_id, role.id)"
              >
                <span class="text-rose-500">×</span>
              </button>
            </span>
            <span v-if="!item.roles || item.roles.length === 0" class="text-xs text-gray-400">No roles assigned</span>
          </div>
        </div>
      </div>
      <p v-if="store.list.length === 0" class="px-5 py-10 text-center text-sm text-gray-500">No committee members yet. Assign a role to a member to add them to the committee.</p>
    </div>

    <ModalComponent v-model="showAssignModal" title="Assign role to member" @update:model-value="onAssignModalClose">
      <form class="space-y-4" @submit.prevent="assign">
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Member</label>
          <select v-model.number="assignForm.member_id" required class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20">
            <option value="">Select member</option>
            <option v-for="m in membersStore.members" :key="m.id" :value="m.id">{{ m.name }} ({{ m.group_category_name }})</option>
          </select>
        </div>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Role</label>
          <select v-model.number="assignForm.role_id" required class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20">
            <option value="">Select role</option>
            <option v-for="r in rolesStore.roles" :key="r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
        <p class="text-xs text-gray-500 border-t border-rose-100/60 pt-3 mt-3">Login credentials (member can sign in with username or email and this password).</p>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Username <span class="text-rose-500">*</span></label>
          <input
            v-model.trim="assignForm.username"
            type="text"
            required
            minlength="2"
            placeholder="e.g. jane.doe"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20"
          />
        </div>
        <div v-if="!selectedMemberEmail" class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Email (for login)</label>
          <input
            v-model.trim="assignForm.email"
            type="email"
            placeholder="member@example.com"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20"
          />
          <p class="text-xs text-gray-400">Member has no email on file; provide one so they can sign in.</p>
        </div>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Password <span class="text-rose-500">*</span></label>
          <input
            v-model="assignForm.password"
            type="password"
            required
            minlength="6"
            placeholder="At least 6 characters"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20"
          />
        </div>
        <p v-if="store.errorMessage" class="text-sm text-rose-600">{{ store.errorMessage }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-50" @click="showAssignModal = false">Cancel</button>
          <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="assigning">Assign</button>
        </div>
      </form>
    </ModalComponent>

    <Confirm
      v-model="showConfirm"
      title="Remove role"
      message="Are you sure you want to remove this role from the member?"
      confirm-text="Remove"
      cancel-text="Cancel"
      :danger="true"
      @confirm="unassign"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import ModalComponent from '@/components/ModalComponent.vue'
import Confirm from '@/components/Confirm.vue'
import { useCommitteeStore } from '@/stores/committee'
import { useRolesStore } from '@/stores/roles'
import { useMembersStore } from '@/stores/members'
import { useAuthStore } from '@/stores/auth'

const store = useCommitteeStore()
const rolesStore = useRolesStore()
const membersStore = useMembersStore()
const authStore = useAuthStore()

const showAssignModal = ref(false)
const showConfirm = ref(false)
const confirmPayload = reactive({ memberId: null, roleId: null })
const assigning = ref(false)
const assignForm = reactive({
  member_id: '',
  role_id: '',
  username: '',
  password: '',
  email: ''
})

const selectedMemberEmail = computed(() => {
  if (!assignForm.member_id) return ''
  const m = membersStore.members.find(mem => mem.id === assignForm.member_id)
  return (m && m.email) ? m.email : ''
})

function onAssignModalClose() {
  assignForm.member_id = ''
  assignForm.role_id = ''
  assignForm.username = ''
  assignForm.password = ''
  assignForm.email = ''
}

async function assign() {
  if (!assignForm.member_id || !assignForm.role_id || !assignForm.username || !assignForm.password) return
  if (assignForm.password.length < 6) return
  assigning.value = true
  store.clearError()
  try {
    await store.assignRole(assignForm.member_id, assignForm.role_id, {
      username: assignForm.username,
      password: assignForm.password,
      email: assignForm.email || undefined
    })
    await store.fetchCommittee()
    showAssignModal.value = false
    onAssignModalClose()
  } finally {
    assigning.value = false
  }
}

function askUnassign(memberId, roleId) {
  confirmPayload.memberId = memberId
  confirmPayload.roleId = roleId
  showConfirm.value = true
}

async function unassign() {
  store.clearError()
  try {
    if (!confirmPayload.memberId || !confirmPayload.roleId) return
    await store.unassignRole(confirmPayload.memberId, confirmPayload.roleId)
    await store.fetchCommittee()
  } catch (_) {}
}

onMounted(async () => {
  await Promise.all([
    store.fetchCommittee().catch(() => {}),
    rolesStore.fetchRoles().catch(() => {}),
    membersStore.fetchMembers().catch(() => {})
  ])
})
</script>
