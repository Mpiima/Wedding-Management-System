<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Contributions</h1>
        <p class="text-sm text-gray-500">Member contributions and thank-you status</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
        @click="showModal = true"
      >
        + Add contribution
      </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <StatCard label="Total received" :value="formatUgx(totalReceived)" />
      <StatCard label="Pledged" :value="formatUgx(totalPledged)" />
      <StatCard label="Thank-you sent" :value="`${thankYouCount} / ${contributions.length}`" />
    </div>

    <TableComponent
      title="Contribution list"
      :columns="columns"
      :data="contributions"
      row-key="id"
    >
      <template #cell-amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-type="{ value }">
        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="value === 'Cash' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'">{{ value }}</span>
      </template>
      <template #cell-thankYou="{ value }">
        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="value ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ value ? 'Sent' : 'Pending' }}</span>
      </template>
      <template #actions>
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium">Edit</button>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" title="Add contribution">
      <form class="space-y-4">
        <FormInput label="Contributor name" placeholder="Full name" />
        <FormInput label="Amount (UGX)" type="number" />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Type</label>
          <select class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20">
            <option>Cash</option>
            <option>Pledge</option>
            <option>Transfer</option>
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
import { ref, computed } from 'vue'
import StatCard from '@/components/StatCard.vue'
import TableComponent from '@/components/TableComponent.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'

const showModal = ref(false)

const columns = [
  { key: 'contributor', label: 'Contributor' },
  { key: 'amount', label: 'Amount' },
  { key: 'type', label: 'Type' },
  { key: 'date', label: 'Date' },
  { key: 'thankYou', label: 'Thank-you' }
]

const contributions = ref([
  { id: 1, contributor: 'Uncle James', amount: 2000000, type: 'Cash', date: '2026-02-15', thankYou: true },
  { id: 2, contributor: 'Sarah & Mike', amount: 750000, type: 'Transfer', date: '2026-03-01', thankYou: false }
])

const totalReceived = computed(() => contributions.value.filter(c => c.type !== 'Pledge').reduce((s, c) => s + c.amount, 0))
const totalPledged = computed(() => contributions.value.filter(c => c.type === 'Pledge').reduce((s, c) => s + c.amount, 0))
const thankYouCount = computed(() => contributions.value.filter(c => c.thankYou).length)

function formatUgx(val) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(val || 0)
}
</script>
