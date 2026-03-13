<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Vendor Contracts</h1>
        <p class="text-sm text-gray-500">Contract details and payment tracking</p>
      </div>
      <button v-if="authStore.can('vendor_contracts.add')" type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600" @click="openForm()">+ Add contract</button>
    </div>

    <p v-if="contractsStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ contractsStore.errorMessage }}</p>

    <TableComponent title="Contracts" :columns="columns" :data="contractsStore.contracts" row-key="id" :pagination="true" :page-size="10" v-model:current-page="currentPage" empty="No contracts yet">
      <template #cell-vendor_name="{ value }">{{ value || '—' }}</template>
      <template #cell-amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-paid_amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-contract_date="{ value }">{{ value ? formatDate(value) : '—' }}</template>
      <template #cell-status="{ value }">
        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="value === 'Signed' ? 'bg-emerald-100 text-emerald-700' : value === 'Completed' ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700'">{{ value }}</span>
      </template>
      <template #actions="{ row }">
        <button v-if="authStore.can('vendor_contracts.edit')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openForm(row)">Edit</button>
        <button v-if="authStore.can('vendor_contracts.delete')" type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium ml-2" @click="confirmDelete(row)">Delete</button>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" :title="editingId ? 'Edit contract' : 'Add contract'">
      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Vendor *</label>
          <select v-model="form.vendor_id" required class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20" :disabled="!!editingId">
            <option value="">Select vendor</option>
            <option v-for="v in vendorsStore.vendors" :key="v.id" :value="v.id">{{ v.name }} ({{ v.category || '—' }})</option>
          </select>
        </div>
        <FormInput v-model="form.title" label="Title *" required />
        <FormInput v-model="form.description" label="Description" />
        <FormInput v-model="form.contract_date" label="Contract date" type="date" />
        <FormInput v-model="form.amount" label="Amount (UGX)" type="number" />
        <FormInput v-model="form.paid_amount" label="Paid amount (UGX)" type="number" />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="form.status" class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20">
            <option value="Draft">Draft</option>
            <option value="Signed">Signed</option>
            <option value="Completed">Completed</option>
          </select>
        </div>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Notes</label>
          <textarea v-model="form.notes" rows="2" class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm" />
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-50" @click="showModal = false">Cancel</button>
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
import { useVendorContractsStore } from '@/stores/vendorContracts'
import { useVendorsStore } from '@/stores/vendors'
import { useAuthStore } from '@/stores/auth'

const contractsStore = useVendorContractsStore()
const vendorsStore = useVendorsStore()
const authStore = useAuthStore()
const showModal = ref(false)
const editingId = ref(null)
const currentPage = ref(1)
const form = ref({ vendor_id: '', title: '', description: '', contract_date: '', amount: '0', paid_amount: '0', status: 'Draft', notes: '' })

const columns = [
  { key: 'vendor_name', label: 'Vendor' },
  { key: 'title', label: 'Title' },
  { key: 'contract_date', label: 'Date' },
  { key: 'amount', label: 'Amount' },
  { key: 'paid_amount', label: 'Paid' },
  { key: 'status', label: 'Status' }
]

function formatUgx(v) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(Number(v) || 0)
}
function formatDate(d) {
  return new Date(d + 'Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function openForm(row = null) {
  editingId.value = row ? row.id : null
  form.value = {
    vendor_id: row ? row.vendor_id : '',
    title: row ? row.title : '',
    description: row ? row.description || '' : '',
    contract_date: row && row.contract_date ? row.contract_date.slice(0, 10) : '',
    amount: row ? row.amount : '0',
    paid_amount: row ? row.paid_amount : '0',
    status: row ? row.status || 'Draft' : 'Draft',
    notes: row ? row.notes || '' : ''
  }
  showModal.value = true
}

async function submit() {
  try {
    const payload = {
      vendor_id: Number(form.value.vendor_id),
      title: form.value.title,
      description: form.value.description || undefined,
      contract_date: form.value.contract_date || undefined,
      amount: Number(form.value.amount) || 0,
      paid_amount: Number(form.value.paid_amount) || 0,
      status: form.value.status,
      notes: form.value.notes || undefined
    }
    if (editingId.value) await contractsStore.updateContract(editingId.value, payload)
    else await contractsStore.createContract(payload)
    showModal.value = false
  } catch (_) {}
}

function confirmDelete(row) {
  if (!confirm('Delete this contract?')) return
  contractsStore.deleteContract(row.id).catch(() => {})
}

onMounted(() => {
  contractsStore.fetchContracts().catch(() => {})
  vendorsStore.fetchVendors().catch(() => {})
})
</script>
