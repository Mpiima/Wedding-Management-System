<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Service Providers</h1>
        <p class="text-sm text-gray-500">Vendor list, categories, and contact info</p>
      </div>
      <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600" @click="openForm()">+ Add vendor</button>
    </div>
    <p v-if="vendorsStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ vendorsStore.errorMessage }}</p>
    <TableComponent title="Vendors" :columns="columns" :data="vendorsStore.vendors" row-key="id" empty="No vendors yet">
      <template #cell-contact_person="{ row }">{{ row.contact_person || row.email || row.phone || '—' }}</template>
      <template #cell-status="{ value }">
        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="value === 'Booked' ? 'bg-emerald-100 text-emerald-700' : value === 'Completed' ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700'">{{ value }}</span>
      </template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openForm(row)">Edit</button>
        <button type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium ml-2" @click="confirmDelete(row)">Delete</button>
      </template>
    </TableComponent>
    <ModalComponent v-model="showModal" :title="editingId ? 'Edit vendor' : 'Add vendor'">
      <form class="space-y-4" @submit.prevent="submit">
        <FormInput v-model="form.name" label="Vendor name *" required />
        <FormInput v-model="form.category" label="Category" placeholder="e.g. Venue, Catering" />
        <FormInput v-model="form.contact_person" label="Contact person" />
        <FormInput v-model="form.email" label="Email" type="email" />
        <FormInput v-model="form.phone" label="Phone" />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="form.status" class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20">
            <option value="Considering">Considering</option>
            <option value="Booked">Booked</option>
            <option value="Completed">Completed</option>
          </select>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm" @click="showModal = false">Cancel</button>
          <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm text-white" @click="submit">Save</button>
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
import { useVendorsStore } from '@/stores/vendors'

const vendorsStore = useVendorsStore()
const showModal = ref(false)
const editingId = ref(null)
const form = ref({ name: '', category: '', contact_person: '', email: '', phone: '', status: 'Considering' })

const columns = [
  { key: 'name', label: 'Vendor' },
  { key: 'category', label: 'Category' },
  { key: 'contact_person', label: 'Contact' },
  { key: 'status', label: 'Status' }
]

function openForm(row = null) {
  editingId.value = row ? row.id : null
  form.value = {
    name: row ? row.name : '',
    category: row ? row.category || '' : '',
    contact_person: row ? row.contact_person || '' : '',
    email: row ? row.email || '' : '',
    phone: row ? row.phone || '' : '',
    status: row ? row.status || 'Considering' : 'Considering'
  }
  showModal.value = true
}

async function submit() {
  try {
    if (editingId.value) await vendorsStore.updateVendor(editingId.value, form.value)
    else await vendorsStore.createVendor(form.value)
    showModal.value = false
  } catch (_) {}
}

function confirmDelete(row) {
  if (!confirm('Delete this vendor?')) return
  vendorsStore.deleteVendor(row.id).catch(() => {})
}

onMounted(() => vendorsStore.fetchVendors().catch(() => {}))
</script>
