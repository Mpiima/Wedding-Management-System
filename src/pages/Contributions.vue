<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Contributions</h1>
        <p class="text-sm text-gray-500">Direct contributions and pledge payments</p>
      </div>
      <button
        v-if="authStore.can('contributions.add')"
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
        @click="showChoiceModal = true"
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
      :data="tableData"
      row-key="_key"
      :pagination="true"
      :page-size="10"
      v-model:current-page="currentPage"
      empty="No contributions yet"
    >
      <template #toolbar>
        <TableToolbar
          v-model:search-query="searchQuery"
          :export-disabled="!filteredContributions.length"
          @export-excel="exportExcel"
          @export-pdf="exportPdf"
        >
          <template #filters>
            <select v-model="filterType" class="rounded-xl border border-rose-100 px-3 py-2 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20">
              <option value="">All types</option>
              <option value="direct">Direct</option>
              <option value="pledge">Pledge</option>
            </select>
            <input v-model="filterDateFrom" type="date" class="rounded-xl border border-rose-100 px-3 py-2 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20" title="From date" />
            <input v-model="filterDateTo" type="date" class="rounded-xl border border-rose-100 px-3 py-2 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20" title="To date" />
          </template>
        </TableToolbar>
      </template>
      <template #cell-type="{ value }">
        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="value === 'pledge' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
          {{ value === 'pledge' ? 'Pledge' : 'Direct' }}
        </span>
      </template>
      <template #cell-member_name="{ value }">{{ value || '—' }}</template>
      <template #cell-amount="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-date="{ value }">{{ value ? formatDate(value) : '—' }}</template>
      <template #actions="{ row }">
        <template v-if="row.type === 'direct' && (authStore.can('contributions.edit') || authStore.can('contributions.delete'))">
          <button v-if="authStore.can('contributions.edit')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openForm(row)">Edit</button>
          <button v-if="authStore.can('contributions.delete')" type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium ml-2" @click="confirmDelete(row)">Delete</button>
        </template>
        <span v-else-if="row.type === 'pledge'" class="text-xs text-gray-400">—</span>
      </template>
    </TableComponent>

    <!-- Choice: Direct or Pledge payment -->
    <ModalComponent v-model="showChoiceModal" title="Add contribution">
      <p class="text-sm text-gray-600 mb-4">What would you like to add?</p>
      <div class="flex flex-col sm:flex-row gap-3">
        <button
          type="button"
          class="flex-1 rounded-xl border-2 border-emerald-200 bg-emerald-50/50 px-5 py-4 text-left transition hover:border-emerald-400 hover:bg-emerald-50"
          @click="chooseDirect()"
        >
          <span class="font-medium text-emerald-800">Direct contribution</span>
          <p class="text-xs text-emerald-600 mt-1">Record a direct payment from a member (not toward a pledge).</p>
        </button>
        <button
          type="button"
          class="flex-1 rounded-xl border-2 border-amber-200 bg-amber-50/50 px-5 py-4 text-left transition hover:border-amber-400 hover:bg-amber-50"
          @click="choosePledgePayment()"
        >
          <span class="font-medium text-amber-800">Pledge payment</span>
          <p class="text-xs text-amber-600 mt-1">Record a payment toward an existing pledge.</p>
        </button>
      </div>
    </ModalComponent>

    <!-- Pledge list: pick a pledge and click Pay -->
    <ModalComponent v-model="showPledgeListModal" title="Pay a pledge" size="wide">
      <p class="text-sm text-gray-600 mb-4">Select a pledge and click Pay to record a payment.</p>
      <div class="max-h-[50vh] overflow-y-auto rounded-xl border border-gray-200">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 sticky top-0">
            <tr>
              <th class="text-left px-4 py-2 font-medium text-gray-700">Member</th>
              <th class="text-right px-4 py-2 font-medium text-gray-700">Pledged</th>
              <th class="text-right px-4 py-2 font-medium text-gray-700">Paid</th>
              <th class="text-right px-4 py-2 font-medium text-gray-700">Balance</th>
              <th class="w-20 px-2 py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in pledgesWithBalance" :key="p.id" class="hover:bg-rose-50/30">
              <td class="px-4 py-2">{{ p.member_name || '—' }}</td>
              <td class="px-4 py-2 text-right">{{ formatUgx(p.amount_pledged) }}</td>
              <td class="px-4 py-2 text-right">{{ formatUgx(p.amount_paid) }}</td>
              <td class="px-4 py-2 text-right font-medium">{{ formatUgx(pledgeBalance(p)) }}</td>
              <td class="px-2 py-2">
                <button type="button" class="rounded-lg bg-rose-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-600" @click="openPayForPledge(p)">Pay</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="pledgesWithBalance.length === 0 && !pledgesLoading" class="py-4 text-center text-sm text-gray-500">No pledges with outstanding balance.</p>
      <p v-if="pledgesLoading" class="py-4 text-center text-sm text-gray-500">Loading pledges…</p>
    </ModalComponent>

    <!-- Record payment for selected pledge -->
    <ModalComponent v-model="showPayModal" title="Record payment">
      <form v-if="selectedPledge" class="space-y-4" @submit.prevent="submitPay">
        <p class="text-sm text-gray-600">
          Pledge by <strong>{{ selectedPledge.member_name }}</strong> — {{ formatUgx(selectedPledge.amount_pledged) }} pledged, {{ formatUgx(selectedPledge.amount_paid) }} paid. Balance: {{ formatUgx(pledgeBalance(selectedPledge)) }}.
        </p>
        <FormInput v-model="payForm.amount" label="Amount (UGX) *" type="number" required />
        <FormInput v-model="payForm.paid_at" label="Date of payment *" type="date" required />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showPayModal = false">Cancel</button>
          <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600" :disabled="paying" @click="submitPay">{{ paying ? 'Recording…' : 'Record payment' }}</button>
        </div>
      </template>
    </ModalComponent>

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
import TableToolbar from '@/components/TableToolbar.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import { useContributionsStore } from '@/stores/contributions'
import { useMembersStore } from '@/stores/members'
import { useReportsStore } from '@/stores/reports'
import { useAuthStore } from '@/stores/auth'
import { usePledgesStore } from '@/stores/pledges'
import { useTableExport } from '@/composables/useTableExport'

const contributionsStore = useContributionsStore()
const membersStore = useMembersStore()
const reportsStore = useReportsStore()
const authStore = useAuthStore()
const pledgesStore = usePledgesStore()

const showChoiceModal = ref(false)
const showPledgeListModal = ref(false)
const showPayModal = ref(false)
const showModal = ref(false)
const editingId = ref(null)
const form = ref({ member_id: '', amount: '', contribution_date: '' })
const selectedPledge = ref(null)
const payForm = ref({ amount: '', paid_at: '' })
const paying = ref(false)
const pledgesLoading = ref(false)
const currentPage = ref(1)
const searchQuery = ref('')
const filterType = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')

function today() { return new Date().toISOString().slice(0, 10) }

const report = computed(() => reportsStore.report)

const exportColumns = [
  { key: 'type', label: 'Type' },
  { key: 'member_name', label: 'Member' },
  { key: 'amount', label: 'Amount' },
  { key: 'date', label: 'Date' }
]
const { exportToExcel, exportToPdf } = useTableExport(exportColumns, 'Contributions')

const filteredContributions = computed(() => {
  let list = contributionsStore.contributions || []
  const q = (searchQuery.value || '').trim().toLowerCase()
  if (q) list = list.filter((r) => (r.member_name || '').toLowerCase().includes(q))
  if (filterType.value) list = list.filter((r) => (r.type || '') === filterType.value)
  const from = (filterDateFrom.value || '').trim()
  const to = (filterDateTo.value || '').trim()
  if (from) list = list.filter((r) => (r.date || '') >= from)
  if (to) list = list.filter((r) => (r.date || '') <= to)
  return list
})

const tableData = computed(() =>
  filteredContributions.value.map((row) => ({
    ...row,
    _key: `${row.type || 'direct'}-${row.id}`
  }))
)

function exportExcel() {
  exportToExcel(filteredContributions.value, 'contributions')
}
function exportPdf() {
  const r = report.value
  const stats = r
    ? [
        { label: 'Total received (all)', value: formatUgx(r.total_received) },
        { label: 'From contributions', value: formatUgx(r.total_contributions) },
        { label: 'From pledge payments', value: formatUgx(r.total_pledge_payments) }
      ]
    : []
  exportToPdf(filteredContributions.value, 'contributions', stats)
}

const pledgesWithBalance = computed(() =>
  (pledgesStore.pledges || []).filter((p) => (Number(p.amount_paid) || 0) < (Number(p.amount_pledged) || 0))
)

function pledgeBalance(p) {
  return Math.max(0, (Number(p.amount_pledged) || 0) - (Number(p.amount_paid) || 0))
}

function chooseDirect() {
  showChoiceModal.value = false
  openForm()
}

async function choosePledgePayment() {
  showChoiceModal.value = false
  pledgesLoading.value = true
  try {
    await pledgesStore.fetchPledges()
  } finally {
    pledgesLoading.value = false
  }
  showPledgeListModal.value = true
}

function openPayForPledge(pledge) {
  selectedPledge.value = pledge
  payForm.value = { amount: '', paid_at: today() }
  showPayModal.value = true
}

async function submitPay() {
  if (!selectedPledge.value || !payForm.value.amount || Number(payForm.value.amount) <= 0) return
  paying.value = true
  try {
    await pledgesStore.recordPayment(selectedPledge.value.id, Number(payForm.value.amount), payForm.value.paid_at)
    showPayModal.value = false
    await contributionsStore.fetchContributions().catch(() => {})
    await reportsStore.fetchReport().catch(() => {})
    await pledgesStore.fetchPledges().catch(() => {})
  } catch (_) {}
  finally {
    paying.value = false
  }
}

const columns = [
  { key: 'type', label: 'Type' },
  { key: 'member_name', label: 'Member' },
  { key: 'amount', label: 'Amount' },
  { key: 'date', label: 'Date' }
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
    contribution_date: row ? (row.date || row.contribution_date || '').toString().slice(0, 10) : today()
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
  if (row.type !== 'direct') return
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
