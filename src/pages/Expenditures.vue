<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Expenditures</h1>
        <p class="text-sm text-gray-500">Track and categorize expenses</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
        @click="showModal = true"
      >
        + Add expense
      </button>
    </div>

    <TableComponent
      title="Expense list"
      :columns="columns"
      :data="expenses"
      row-key="id"
      :pagination="true"
      :page-size="8"
      v-model:current-page="currentPage"
    >
      <template #cell-amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-category="{ value }">
        <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">{{ value }}</span>
      </template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium">Edit</button>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" title="Add expense">
      <form class="space-y-4" @submit.prevent="showModal = false">
        <FormInput v-model="form.description" label="Description" placeholder="e.g. Venue deposit" />
        <FormInput v-model="form.amount" label="Amount (UGX)" type="number" />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Category</label>
          <select
            v-model="form.category"
            class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
          >
            <option value="Venue">Venue</option>
            <option value="Catering">Catering</option>
            <option value="Photography">Photography</option>
            <option value="Decor">Decor</option>
          </select>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showModal = false">Cancel</button>
          <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600" @click="showModal = false">Save</button>
        </div>
      </template>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'

const showModal = ref(false)
const currentPage = ref(1)
const form = ref({ description: '', amount: '', category: 'Venue' })

const columns = [
  { key: 'date', label: 'Date' },
  { key: 'description', label: 'Description' },
  { key: 'category', label: 'Category' },
  { key: 'amount', label: 'Amount' }
]

const expenses = ref([
  { id: 1, date: '2026-03-01', description: 'Venue deposit', category: 'Venue', amount: 10000000 },
  { id: 2, date: '2026-03-05', description: 'Catering tasting', category: 'Catering', amount: 500000 }
])

function formatUgx(val) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(val || 0)
}
</script>
