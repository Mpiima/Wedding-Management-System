<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Contributions</h1>
        <p class="text-sm text-gray-500">Member contributions (direct payments)</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
        @click="openForm()"
      >
        + Add contribution
      </button>
    </div>

    <div v-if="report" class="grid gap-4 sm:grid-cols-3">
      <StatCard label="Total received (all)" :value="formatUgx(report.total_received)" />
      <StatCard label="From contributions" :value="formatUgx(report.total_contributions)" />
      <StatCard label="From pledge payments" :value="formatUgx(report.total_pledge_payments)" />
    </div>

    <p v-if="contributionsStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ contributionsStore.errorMessage }}</p>

    <TableComponent
      title="Contribution list"
      :columns="columns"
      :data="contributionsStore.contributions"
      row-key="id"
      :pagination="true"
      :page-size="10"
      v-model:current-page="currentPage"
      empty="No contributions yet"
    >
      <template #cell-member_name="{ value }">{{ value || '—' }}</template>
      <template #cell-amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-contribution_date="{ value }">{{ value ? formatDate(value) : '—' }}</template>
      <template #actions="{ row }">
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openForm(row)">Edit</button>
        <button type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium ml-2" @click="confirmDelete(row)">Delete</button>
      </template>
    </TableComponent>

    <ModalComponent v-model="showModal" :title="editingId ? 'Edit contribution' : 'Add contribution'">
      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Member *</label>
          <select v-model="form.member_id" required class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20" :disabled="!!editingId">
            <option value="">Select member</option>
            <option v-for="m in membersStore.members" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </div>
        <FormInput v-model="form.amount" label="Amount (UGX) *" type="number" required />
        <FormInput v-model="form.contribution_date" label="Date *" type="date" required />
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
import { useContributionsStore } from '@/stores/contributions'
import { useMembersStore } from '@/stores/members'
import { useReportsStore } from '@/stores/reports'

const contributionsStore = useContributionsStore()
const membersStore = useMembersStore()
const reportsStore = useReportsStore()

const showModal = ref(false)
const editingId = ref(null)
const form = ref({ member_id: '', amount: '', contribution_date: '' })
const currentPage = ref(1)

function today() { return new Date().toISOString().slice(0, 10) }

const report = computed(() => reportsStore.report)

const columns = [
  { key: 'member_name', label: 'Member' },
  { key: 'amount', label: 'Amount' },
  { key: 'contribution_date', label: 'Date' }
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
    member_id: row ? row.member_id : '',
    amount: row ? row.amount : '',
    contribution_date: row && row.contribution_date ? row.contribution_date.slice(0, 10) : today()
  }
  showModal.value = true
}

async function submit() {
  try {
    const payload = {
      member_id: Number(form.value.member_id),
      amount: Number(form.value.amount) || 0,
      contribution_date: form.value.contribution_date
    }
    if (editingId.value) {
      await contributionsStore.updateContribution(editingId.value, payload)
    } else {
      await contributionsStore.createContribution(payload)
    }
    showModal.value = false
    reportsStore.fetchReport().catch(() => {})
  } catch (_) {}
}

function confirmDelete(row) {
  if (!confirm('Delete this contribution?')) return
  contributionsStore.deleteContribution(row.id).then(() => reportsStore.fetchReport().catch(() => {})).catch(() => {})
}

onMounted(() => {
  form.value.contribution_date = today()
  contributionsStore.fetchContributions().catch(() => {})
  membersStore.fetchMembers().catch(() => {})
  reportsStore.fetchReport().catch(() => {})
})
</script>
