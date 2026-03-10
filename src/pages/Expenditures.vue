<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Expenditures</h1>
        <p class="text-sm text-gray-500">Track expenses; balance = total received − expenditures</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
        @click="openForm()"
      >
        + Add expense
      </button>
    </div>

    <div v-if="report" class="grid gap-4 sm:grid-cols-3">
      <StatCard label="Total received" :value="formatUgx(report.total_received)" />
      <StatCard label="Total expenditures" :value="formatUgx(report.total_expenditures)" />
      <StatCard label="Balance" :value="formatUgx(report.balance)" :value-class="report.balance >= 0 ? 'text-emerald-600' : 'text-rose-600'" />
    </div>

    <p v-if="expendituresStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ expendituresStore.errorMessage }}</p>

    <TableComponent
      title="Expense list"
      :columns="columns"
      :data="expendituresStore.expenditures"
      row-key="id"
      :pagination="true"
      :page-size="8"
      v-model:current-page="currentPage"
      empty="No expenditures yet"
    >
      <template #cell-amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-expenditure_date="{ value }">{{ value ? formatDate(value) : '—' }}</template>
      <template #cell-description="{ value }">{{ value || '—' }}</template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openForm(row)">Edit</button>
        <button type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium ml-2" @click="confirmDelete(row)">Delete</button>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" :title="editingId ? 'Edit expense' : 'Add expense'">
      <form class="space-y-4" @submit.prevent="submit">
        <FormInput v-model="form.item" label="Item / Category *" placeholder="e.g. Venue deposit" required />
        <FormInput v-model="form.amount" label="Amount (UGX) *" type="number" required />
        <FormInput v-model="form.description" label="Description / Reason" placeholder="Optional" />
        <FormInput v-model="form.expenditure_date" label="Date *" type="date" required />
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
import { ref, computed, onMounted } from 'vue'
import StatCard from '@/components/StatCard.vue'
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import { useExpendituresStore } from '@/stores/expenditures'
import { useReportsStore } from '@/stores/reports'

const expendituresStore = useExpendituresStore()
const reportsStore = useReportsStore()

const showModal = ref(false)
const editingId = ref(null)
const form = ref({ item: '', amount: '', description: '', expenditure_date: new Date().toISOString().slice(0, 10) })
const currentPage = ref(1)

const report = computed(() => reportsStore.report)

const columns = [
  { key: 'expenditure_date', label: 'Date' },
  { key: 'item', label: 'Item' },
  { key: 'description', label: 'Description' },
  { key: 'amount', label: 'Amount' }
]

function formatUgx(val) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(Number(val) || 0)
}
function formatDate(d) {
  return new Date(d + 'Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function openForm(row = null) {
  editingId.value = row ? row.id : null
  form.value = {
    item: row ? row.item : '',
    amount: row ? row.amount : '',
    description: row ? row.description || '' : '',
    expenditure_date: row && row.expenditure_date ? row.expenditure_date.slice(0, 10) : new Date().toISOString().slice(0, 10)
  }
  showModal.value = true
}

async function submit() {
  try {
    const payload = {
      item: form.value.item,
      amount: Number(form.value.amount) || 0,
      description: form.value.description || undefined,
      expenditure_date: form.value.expenditure_date
    }
    if (editingId.value) {
      await expendituresStore.updateExpenditure(editingId.value, payload)
    } else {
      await expendituresStore.createExpenditure(payload)
    }
    showModal.value = false
    reportsStore.fetchReport().catch(() => {})
  } catch (_) {}
}

function confirmDelete(row) {
  if (!confirm('Delete this expenditure?')) return
  expendituresStore.deleteExpenditure(row.id).then(() => reportsStore.fetchReport().catch(() => {})).catch(() => {})
}

onMounted(() => {
  expendituresStore.fetchExpenditures().catch(() => {})
  reportsStore.fetchReport().catch(() => {})
})
</script>
