<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Roles</h1>
        <p class="text-sm text-slate-500">
          Assign committee members and wedding party roles.
        </p>
      </div>
      <button
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-pink-500 text-white text-sm font-medium shadow-sm hover:bg-pink-600"
        @click="openRoleModal()"
      >
        ➕ Add member
      </button>
    </div>

    <!-- Summary -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="stat in roleStats"
        :key="stat.label"
        class="rounded-2xl border border-slate-100 bg-white p-4 flex items-center gap-3"
      >
        <span
          class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-sm"
          :class="stat.badgeClass"
        >
          {{ stat.icon }}
        </span>
        <div>
          <p class="text-xs text-slate-400 uppercase tracking-[0.12em]">{{ stat.label }}</p>
          <p class="text-lg font-semibold text-slate-800">{{ stat.count }}</p>
        </div>
      </div>
    </section>

    <!-- Roles table -->
    <section class="rounded-2xl border border-slate-100 bg-white overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50/80 text-xs text-slate-400 uppercase tracking-[0.12em]">
            <tr>
              <th class="px-4 py-3">Name</th>
              <th class="px-4 py-3">Role</th>
              <th class="px-4 py-3">Contact</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="member in members"
              :key="member.id"
              class="border-t border-slate-50 hover:bg-slate-50/70"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span
                    class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold"
                    :class="roleBadgeClass(member.role)"
                  >
                    {{ member.initials }}
                  </span>
                  <div>
                    <p class="text-sm font-medium text-slate-800">{{ member.name }}</p>
                    <p class="text-xs text-slate-400">{{ member.relation }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-xs">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full border"
                  :class="roleLabelClass(member.role)"
                >
                  {{ member.role }}
                </span>
              </td>
              <td class="px-4 py-3 text-xs text-slate-500">
                {{ member.phone || '—' }}<br />
                <span class="text-[11px] text-slate-400">{{ member.email || '—' }}</span>
              </td>
              <td class="px-4 py-3 text-xs">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full border"
                  :class="member.confirmed ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-amber-100 bg-amber-50 text-amber-600'"
                >
                  {{ member.confirmed ? 'Confirmed' : 'Pending' }}
                </span>
              </td>
              <td class="px-4 py-3 text-xs text-right space-x-1 whitespace-nowrap">
                <button
                  class="px-2 py-1 rounded-md border border-slate-200 text-slate-500 hover:bg-slate-50"
                  @click="openRoleModal(member)"
                >
                  Edit
                </button>
                <button
                  class="px-2 py-1 rounded-md border border-rose-100 text-rose-500 hover:bg-rose-50"
                  @click="removeMember(member.id)"
                >
                  Remove
                </button>
              </td>
            </tr>
            <tr v-if="!members.length">
              <td colspan="5" class="px-4 py-6 text-center text-xs text-slate-400">
                No committee members added yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Role modal -->
    <transition name="fade">
      <div
        v-if="roleModalOpen"
        class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 px-4"
      >
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl p-5 space-y-4">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-sm font-semibold text-slate-800">
                {{ editingMember ? 'Edit member' : 'Add committee member' }}
              </h2>
              <p class="text-xs text-slate-400">Assign role and contact details.</p>
            </div>
            <button
              class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 text-slate-400"
              @click="closeRoleModal"
            >
              ✕
            </button>
          </div>

          <form class="space-y-3" @submit.prevent="saveMember">
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Full name</label>
              <input
                v-model="memberForm.name"
                type="text"
                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
              />
              <p v-if="memberErrors.name" class="mt-1 text-[11px] text-rose-500">
                {{ memberErrors.name }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Role</label>
              <select
                v-model="memberForm.role"
                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
              >
                <option value="">Select role</option>
                <option value="Best Man">Best Man</option>
                <option value="Maid of Honor">Maid of Honor</option>
                <option value="Bridesmaid">Bridesmaid</option>
                <option value="Groomsman">Groomsman</option>
                <option value="Usher">Usher</option>
                <option value="MC">MC</option>
                <option value="Coordinator">Coordinator</option>
                <option value="Other">Other</option>
              </select>
              <p v-if="memberErrors.role" class="mt-1 text-[11px] text-rose-500">
                {{ memberErrors.role }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Relation</label>
              <input
                v-model="memberForm.relation"
                type="text"
                placeholder="Friend, sibling, cousin..."
                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
              />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Phone</label>
                <input
                  v-model="memberForm.phone"
                  type="tel"
                  class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
                <input
                  v-model="memberForm.email"
                  type="email"
                  class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
                />
              </div>
            </div>
            <div class="flex items-end">
              <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                <input
                  v-model="memberForm.confirmed"
                  type="checkbox"
                  class="rounded border-slate-300 text-pink-500 focus:ring-pink-200"
                />
                Role confirmed
              </label>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Notes</label>
              <textarea
                v-model="memberForm.notes"
                rows="2"
                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
              ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs text-slate-600 hover:bg-slate-50"
                @click="closeRoleModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="px-4 py-2 rounded-lg bg-pink-500 text-white text-xs font-medium hover:bg-pink-600"
              >
                Save
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'

const members = ref([
  {
    id: 1,
    name: 'Chris Lee',
    initials: 'CL',
    role: 'Best Man',
    relation: 'Brother',
    phone: '+1 202 555 0144',
    email: 'chris@example.com',
    confirmed: true,
    notes: ''
  },
  {
    id: 2,
    name: 'Maya Chen',
    initials: 'MC',
    role: 'Maid of Honor',
    relation: 'Sister',
    phone: '+1 202 555 0155',
    email: 'maya@example.com',
    confirmed: true,
    notes: ''
  },
  {
    id: 3,
    name: 'Jordan Taylor',
    initials: 'JT',
    role: 'MC',
    relation: 'Friend',
    phone: '+1 202 555 0166',
    email: '',
    confirmed: false,
    notes: 'Will confirm by next week'
  }
])

const roleStats = computed(() => [
  {
    label: 'Wedding party',
    count: members.value.filter((m) => ['Best Man', 'Maid of Honor', 'Bridesmaid', 'Groomsman', 'Usher'].includes(m.role)).length,
    icon: '👫',
    badgeClass: 'bg-pink-50 text-pink-500'
  },
  {
    label: 'Confirmed',
    count: members.value.filter((m) => m.confirmed).length,
    icon: '✓',
    badgeClass: 'bg-emerald-50 text-emerald-500'
  },
  {
    label: 'Pending',
    count: members.value.filter((m) => !m.confirmed).length,
    icon: '⏳',
    badgeClass: 'bg-amber-50 text-amber-500'
  },
  {
    label: 'Total',
    count: members.value.length,
    icon: '🧩',
    badgeClass: 'bg-slate-100 text-slate-500'
  }
])

const roleBadgeClass = (role) => {
  if (['Best Man', 'Groomsman', 'Usher'].includes(role)) return 'bg-blue-50 text-blue-500'
  if (['Maid of Honor', 'Bridesmaid'].includes(role)) return 'bg-pink-50 text-pink-500'
  return 'bg-slate-100 text-slate-500'
}

const roleLabelClass = (role) => {
  if (['Best Man', 'Groomsman', 'Usher'].includes(role)) return 'border-blue-100 bg-blue-50 text-blue-600'
  if (['Maid of Honor', 'Bridesmaid'].includes(role)) return 'border-pink-100 bg-pink-50 text-pink-600'
  return 'border-slate-200 bg-slate-50 text-slate-600'
}

const roleModalOpen = ref(false)
const editingMember = ref(null)

const memberForm = reactive({
  id: null,
  name: '',
  role: '',
  relation: '',
  phone: '',
  email: '',
  confirmed: false,
  notes: ''
})

const memberErrors = reactive({
  name: '',
  role: ''
})

const openRoleModal = (member = null) => {
  if (member) {
    Object.assign(memberForm, member)
    editingMember.value = member
  } else {
    memberForm.id = null
    memberForm.name = ''
    memberForm.role = ''
    memberForm.relation = ''
    memberForm.phone = ''
    memberForm.email = ''
    memberForm.confirmed = false
    memberForm.notes = ''
    editingMember.value = null
  }
  memberErrors.name = ''
  memberErrors.role = ''
  roleModalOpen.value = true
}

const closeRoleModal = () => {
  roleModalOpen.value = false
}

const validateMember = () => {
  memberErrors.name = memberForm.name ? '' : 'Name is required.'
  memberErrors.role = memberForm.role ? '' : 'Role is required.'
  return !memberErrors.name && !memberErrors.role
}

const initials = (name) =>
  name
    .split(' ')
    .map((p) => p[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)

const saveMember = () => {
  if (!validateMember()) return
  if (editingMember.value) {
    const index = members.value.findIndex((m) => m.id === editingMember.value.id)
    if (index !== -1) members.value[index] = { ...memberForm, initials: initials(memberForm.name) }
  } else {
    const newId = Math.max(0, ...members.value.map((m) => m.id)) + 1
    members.value.push({ ...memberForm, id: newId, initials: initials(memberForm.name) })
  }
  roleModalOpen.value = false
}

const removeMember = (id) => {
  members.value = members.value.filter((m) => m.id !== id)
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
