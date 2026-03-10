<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-wmis-text">Invited Guests</h1>
        <p class="text-sm text-gray-500">Guest list, RSVP tracking, invitation cards</p>
      </div>
      <div class="flex gap-2">
        <input
          v-model="search"
          type="search"
          placeholder="Search guests..."
          class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
        />
        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600" @click="openForm()">+ Add guest</button>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <StatCard label="Invited" :value="guestsStore.guests.length" />
      <StatCard label="Confirmed" :value="confirmedCount" />
      <StatCard label="Pending" :value="guestsStore.guests.length - confirmedCount" />
    </div>

    <p v-if="guestsStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ guestsStore.errorMessage }}</p>

    <TableComponent
      title="Invited guests"
      :columns="columns"
      :data="filteredGuests"
      row-key="id"
      :pagination="true"
      :page-size="10"
      v-model:current-page="currentPage"
      empty="No guests yet"
    >
      <template #cell-status="{ value }">
        <span
          class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
          :class="value === 'Confirmed' ? 'bg-emerald-100 text-emerald-700' : value === 'Declined' ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700'"
        >
          {{ value }}
        </span>
      </template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openCard(row)">Card</button>
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium ml-2" @click="openForm(row)">Edit</button>
        <button type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium ml-2" @click="confirmDelete(row)">Delete</button>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" :title="editingId ? 'Edit guest' : 'Add guest'">
      <form class="space-y-4" @submit.prevent="submit">
        <FormInput v-model="form.name" label="Name *" placeholder="Full name" required />
        <FormInput v-model="form.side" label="Side" placeholder="e.g. Bride, Groom, Both" />
        <FormInput v-model="form.email" label="Email" type="email" placeholder="Optional" />
        <FormInput v-model="form.phone" label="Phone" placeholder="Optional" />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="form.status" class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20">
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Declined">Declined</option>
          </select>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showModal = false">Cancel</button>
          <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600" @click="submit">Save</button>
        </div>
      </template>
    </ModalComponent>

    <Teleport to="body">
      <div v-if="showCard" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4" @click.self="showCard = false">
        <div class="max-h-[90vh] w-full max-w-md overflow-auto rounded-2xl bg-white p-8 shadow-xl" ref="cardRef">
          <InvitationCard :guest="cardGuest" />
          <div class="mt-6 flex gap-2">
            <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white" @click="printCard">Print card</button>
            <button type="button" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium" @click="showCard = false">Close</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import StatCard from '@/components/StatCard.vue'
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import InvitationCard from '@/components/InvitationCard.vue'
import { useInvitedGuestsStore } from '@/stores/invitedGuests'
import { useWeddingProfileStore } from '@/stores/weddingProfile'

const guestsStore = useInvitedGuestsStore()
const weddingProfile = useWeddingProfileStore()

const search = ref('')
const currentPage = ref(1)
const showModal = ref(false)
const editingId = ref(null)
const form = ref({ name: '', side: '', email: '', phone: '', status: 'Pending' })
const showCard = ref(false)
const cardGuest = ref(null)
const cardRef = ref(null)

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'side', label: 'Side' },
  { key: 'email', label: 'Email' },
  { key: 'status', label: 'Status' }
]

const confirmedCount = computed(() => guestsStore.guests.filter(g => g.status === 'Confirmed').length)
const filteredGuests = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return guestsStore.guests
  return guestsStore.guests.filter(g => (g.name && g.name.toLowerCase().includes(q)) || (g.email && g.email.toLowerCase().includes(q)))
})

function openForm(row = null) {
  editingId.value = row ? row.id : null
  form.value = {
    name: row ? row.name : '',
    side: row ? row.side || '' : '',
    email: row ? row.email || '' : '',
    phone: row ? row.phone || '' : '',
    status: row ? row.status || 'Pending' : 'Pending'
  }
  showModal.value = true
}

async function submit() {
  try {
    if (editingId.value) {
      await guestsStore.updateGuest(editingId.value, form.value)
    } else {
      await guestsStore.createGuest(form.value)
    }
    showModal.value = false
  } catch (_) {}
}

function openCard(row) {
  cardGuest.value = row
  showCard.value = true
}

function printCard() {
  if (!cardRef.value) return
  const win = window.open('', '_blank')
  win.document.write('<html><head><title>Invitation</title></head><body>' + cardRef.value.innerHTML + '</body></html>')
  win.document.close()
  win.print()
  win.close()
}

function confirmDelete(row) {
  if (!confirm('Remove this guest?')) return
  guestsStore.deleteGuest(row.id).catch(() => {})
}

onMounted(() => {
  guestsStore.fetchGuests().catch(() => {})
})
</script>
