<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-wmis-text">Pledges</h1>
        <p class="text-sm text-gray-500">Pledge tracking, payments, and cards</p>
      </div>
      <button
        v-if="authStore.can('pledges.add')"
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition hover:bg-rose-600"
        @click="openForm()"
      >
        + Add pledge
      </button>
    </div>

    <p v-if="pledgesStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ pledgesStore.errorMessage }}</p>
    <p v-if="reminderMessage" class="rounded-xl px-4 py-2 text-sm" :class="reminderSuccess ? 'bg-green-50 text-green-700' : 'bg-rose-50 text-rose-700'">{{ reminderMessage }}</p>

    <TableComponent
      title="Pledges"
      :columns="columns"
      :data="filteredData"
      row-key="id"
      :pagination="true"
      :page-size="10"
      v-model:current-page="currentPage"
      empty="No pledges yet"
    >
      <template #toolbar>
        <TableToolbar
          v-model:search-query="searchQuery"
          :export-disabled="!filteredData.length"
          @export-excel="exportExcel"
          @export-pdf="exportPdf"
        >
          <template #filters>
            <select v-model="filterStatus" class="rounded-xl border border-rose-100 px-3 py-2 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20">
              <option value="">All statuses</option>
              <option value="Pending">Pending</option>
              <option value="Partial">Partial</option>
              <option value="Fulfilled">Fulfilled</option>
            </select>
            <input v-model="filterDateFrom" type="date" class="rounded-xl border border-rose-100 px-3 py-2 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20" title="From date" />
            <input v-model="filterDateTo" type="date" class="rounded-xl border border-rose-100 px-3 py-2 text-sm focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20" title="To date" />
          </template>
        </TableToolbar>
      </template>
      <template #cell-member_name="{ value }">{{ value || '—' }}</template>
      <template #cell-amount_pledged="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-amount_paid="{ value }">{{ formatUgx(value) }}</template>
      <template #cell-balance="{ row }">{{ formatUgx(pledgeBalance(row)) }}</template>
      <template #cell-paying_date="{ value }">{{ value ? formatDate(value) : '—' }}</template>
      <template #cell-status="{ row }">
        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="pledgeStatusClass(row)">{{ pledgeStatus(row) }}</span>
      </template>
      <template #actions="{ row }">
        <div class="flex items-center gap-2 flex-wrap">
          <button v-if="authStore.can('pledges.view')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openPaymentHistory(row)">Payment history</button>
          <button v-if="authStore.can('pledges.edit')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openPay(row)">Pay</button>
          <button v-if="authStore.can('pledges.view')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openCard(row)">Card</button>
          <button v-if="authStore.can('pledges.view')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openReceipt(row)">Receipt</button>
          <button v-if="authStore.can('pledges.edit')" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium" @click="openForm(row)">Edit</button>
          <button v-if="(authStore.can('notifications.send') || authStore.can('pledges.edit'))" type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium inline-flex items-center gap-1" :disabled="reminderLoadingId === row.id" @click="sendReminder(row)">
            <span v-if="reminderLoadingId === row.id" class="inline-block w-3 h-3 border-2 border-rose-500 border-t-transparent rounded-full animate-spin" />
            Reminder
          </button>
          <button v-if="authStore.can('pledges.delete')" type="button" class="text-gray-500 hover:text-rose-600 text-xs font-medium" @click="confirmDelete(row)">Delete</button>
        </div>
      </template>
    </TableComponent>

    <!-- Add/Edit pledge modal -->
    <ModalComponent v-model="showForm" :title="editingId ? 'Edit pledge' : 'Add pledge'">
      <form class="space-y-4" @submit.prevent="submitPledge">
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Member *</label>
          <select v-model="form.member_id" required class="block w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20" :disabled="!!editingId">
            <option value="">Select member</option>
            <option v-for="m in membersStore.members" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </div>
        <FormInput v-model="form.amount_pledged" label="Amount pledged (UGX) *" type="number" required />
        <FormInput v-model="form.paying_date" label="Paying date (optional)" type="date" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showForm = false">Cancel</button>
          <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600" @click="submitPledge">Save</button>
        </div>
      </template>
    </ModalComponent>

    <!-- Pay modal -->
    <ModalComponent v-model="showPay" title="Record payment">
      <form class="space-y-4" @submit.prevent="submitPay">
        <p v-if="payPledge" class="text-sm text-gray-600">Pledge by <strong>{{ payPledge.member_name }}</strong> — {{ formatUgx(payPledge.amount_pledged) }} pledged, {{ formatUgx(payPledge.amount_paid) }} paid.</p>
        <FormInput v-model="payForm.amount" label="Amount (UGX) *" type="number" required />
        <FormInput v-model="payForm.paid_at" label="Date of payment *" type="date" required />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showPay = false">Cancel</button>
          <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600" @click="submitPay">Record payment</button>
        </div>
      </template>
    </ModalComponent>

    <!-- Pledge card (print) -->
    <Teleport to="body">
      <div v-if="showCard" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4" @click.self="showCard = false">
        <div class="max-h-[90vh] w-full max-w-md overflow-auto rounded-2xl bg-white p-8 shadow-xl" ref="cardRef">
          <PledgeCard :pledge="cardPledge" />
          <div class="mt-6 flex gap-2">
            <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white" @click="printCard">Print card</button>
            <button type="button" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium" @click="showCard = false">Close</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Receipt (print) -->
    <Teleport to="body">
      <div v-if="showReceipt" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4" @click.self="showReceipt = false">
        <div class="max-h-[90vh] w-full max-w-md overflow-auto rounded-2xl bg-white p-8 shadow-xl" ref="receiptRef">
          <ReceiptPrint :pledge="receiptPledge" />
          <div class="mt-6 flex gap-2">
            <button type="button" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white" @click="printReceipt">Print receipt</button>
            <button type="button" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium" @click="showReceipt = false">Close</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Payment history modal -->
    <ModalComponent v-model="showPaymentHistoryModal" title="Payment history">
      <div v-if="paymentHistoryPledge" class="space-y-3">
        <p class="text-sm text-gray-600">
          <strong>{{ paymentHistoryPledge.member_name }}</strong> — Pledged {{ formatUgx(paymentHistoryPledge.amount_pledged) }}, paid {{ formatUgx(paymentHistoryPledge.amount_paid) }}, balance {{ formatUgx(pledgeBalance(paymentHistoryPledge)) }}.
        </p>
        <ul class="border border-rose-100 rounded-xl divide-y divide-rose-100 overflow-hidden">
          <li v-for="p in (paymentHistoryPledge.payments || [])" :key="p.id" class="flex justify-between items-center px-4 py-3 text-sm bg-white">
            <span class="text-gray-500">{{ p.paid_at ? formatDate(p.paid_at) : '—' }}</span>
            <span class="font-medium text-wmis-text">{{ formatUgx(p.amount) }}</span>
          </li>
        </ul>
        <p v-if="!paymentHistoryPledge.payments || !paymentHistoryPledge.payments.length" class="text-sm text-gray-500 py-2">No payment records yet.</p>
      </div>
      <template #footer>
        <div class="flex justify-end">
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showPaymentHistoryModal = false">Close</button>
        </div>
      </template>
    </ModalComponent>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import TableComponent from '@/components/TableComponent.vue'
import TableToolbar from '@/components/TableToolbar.vue'
import ModalComponent from '@/components/ModalComponent.vue'
import FormInput from '@/components/FormInput.vue'
import PledgeCard from '@/components/PledgeCard.vue'
import ReceiptPrint from '@/components/ReceiptPrint.vue'
import { usePledgesStore } from '@/stores/pledges'
import { useMembersStore } from '@/stores/members'
import { useAuthStore } from '@/stores/auth'
import api from '@/config/api.js'
import { useTableExport } from '@/composables/useTableExport'

const pledgesStore = usePledgesStore()
const membersStore = useMembersStore()
const authStore = useAuthStore()

const currentPage = ref(1)
const showForm = ref(false)
const showPay = ref(false)
const editingId = ref(null)
const form = ref({ member_id: '', amount_pledged: '', paying_date: '' })
const payPledge = ref(null)
const payForm = ref({ amount: '', paid_at: new Date().toISOString().slice(0, 10) })
const showCard = ref(false)
const cardPledge = ref(null)
const cardRef = ref(null)
const showReceipt = ref(false)
const receiptPledge = ref(null)
const receiptRef = ref(null)
const showPaymentHistoryModal = ref(false)
const paymentHistoryPledge = ref(null)
const reminderLoadingId = ref(null)
const reminderMessage = ref('')
const reminderSuccess = ref(false)
const searchQuery = ref('')
const filterStatus = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')

const columns = [
  { key: 'member_name', label: 'Member' },
  { key: 'amount_pledged', label: 'Pledged' },
  { key: 'amount_paid', label: 'Paid' },
  { key: 'balance', label: 'Balance' },
  { key: 'paying_date', label: 'Paying date' },
  { key: 'status', label: 'Status' }
]

function formatUgx(v) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(Number(v) || 0)
}
function formatDate(d) {
  return new Date(d + 'Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}
function pledgeStatus(row) {
  const paid = Number(row.amount_paid) || 0
  const pledged = Number(row.amount_pledged) || 0
  if (pledged <= 0) return '—'
  if (paid >= pledged) return 'Fulfilled'
  if (paid > 0) return 'Partial'
  return 'Pending'
}
function pledgeStatusClass(row) {
  const s = pledgeStatus(row)
  if (s === 'Fulfilled') return 'bg-emerald-100 text-emerald-700'
  if (s === 'Partial') return 'bg-amber-100 text-amber-700'
  return 'bg-gray-100 text-gray-700'
}
function pledgeBalance(row) {
  const pledged = Number(row.amount_pledged) || 0
  const paid = Number(row.amount_paid) || 0
  return Math.max(0, pledged - paid)
}

const exportColumns = [
  { key: 'member_name', label: 'Member' },
  { key: 'amount_pledged', label: 'Pledged' },
  { key: 'amount_paid', label: 'Paid' },
  { key: 'balance', label: 'Balance' },
  { key: 'paying_date', label: 'Paying date' },
  { key: 'status', label: 'Status' }
]
const { exportToExcel, exportToPdf } = useTableExport(exportColumns, 'Pledges')

const filteredData = computed(() => {
  let list = pledgesStore.pledges || []
  const q = (searchQuery.value || '').trim().toLowerCase()
  if (q) list = list.filter((r) => (r.member_name || '').toLowerCase().includes(q))
  if (filterStatus.value) list = list.filter((r) => pledgeStatus(r) === filterStatus.value)
  const from = (filterDateFrom.value || '').trim()
  const to = (filterDateTo.value || '').trim()
  if (from) list = list.filter((r) => (r.paying_date || r.created_at || '') >= from)
  if (to) list = list.filter((r) => (r.paying_date || r.created_at || '') <= to)
  return list
})

function exportDataForTable() {
  return filteredData.value.map((r) => ({
    ...r,
    balance: pledgeBalance(r),
    status: pledgeStatus(r)
  }))
}
function exportExcel() {
  exportToExcel(exportDataForTable(), 'pledges')
}
function exportPdf() {
  const data = exportDataForTable()
  const totalPledged = data.reduce((s, r) => s + (Number(r.amount_pledged) || 0), 0)
  const totalPaid = data.reduce((s, r) => s + (Number(r.amount_paid) || 0), 0)
  const totalBalance = data.reduce((s, r) => s + (Number(r.balance) || 0), 0)
  const statistics = [
    { label: 'Total pledged', value: formatUgx(totalPledged) },
    { label: 'Total paid', value: formatUgx(totalPaid) },
    { label: 'Outstanding balance', value: formatUgx(totalBalance) },
    { label: 'Number of pledges', value: String(data.length) }
  ]
  exportToPdf(data, 'pledges', statistics)
}

async function openPaymentHistory(row) {
  try {
    await pledgesStore.fetchPledgeById(row.id)
    paymentHistoryPledge.value = pledgesStore.selectedPledge
    showPaymentHistoryModal.value = true
  } catch (_) {}
}

function openForm(row = null) {
  editingId.value = row ? row.id : null
  form.value = {
    member_id: row ? row.member_id : '',
    amount_pledged: row ? row.amount_pledged : '',
    paying_date: row && row.paying_date ? row.paying_date.slice(0, 10) : ''
  }
  showForm.value = true
}

async function submitPledge() {
  try {
    const payload = {
      member_id: Number(form.value.member_id),
      amount_pledged: Number(form.value.amount_pledged) || 0,
      paying_date: form.value.paying_date || undefined
    }
    if (editingId.value) {
      await pledgesStore.updatePledge(editingId.value, payload)
    } else {
      await pledgesStore.createPledge(payload)
    }
    showForm.value = false
  } catch (_) {}
}

function openPay(row) {
  payPledge.value = row
  payForm.value = { amount: '', paid_at: new Date().toISOString().slice(0, 10) }
  showPay.value = true
}

async function submitPay() {
  if (!payPledge.value || !payForm.value.amount || Number(payForm.value.amount) <= 0) return
  try {
    await pledgesStore.recordPayment(payPledge.value.id, Number(payForm.value.amount), payForm.value.paid_at)
    showPay.value = false
    payPledge.value = null
  } catch (_) {}
}

function openCard(row) {
  cardPledge.value = row
  showCard.value = true
}

function printCard() {
  if (!cardRef.value) return
  const win = window.open('', '_blank')
  win.document.write('<html><head><title>Pledge Card</title></head><body>' + cardRef.value.innerHTML + '</body></html>')
  win.document.close()
  win.print()
  win.close()
}

async function openReceipt(row) {
  try {
    await pledgesStore.fetchPledgeById(row.id)
    receiptPledge.value = pledgesStore.selectedPledge
    showReceipt.value = true
  } catch (_) {}
}

function printReceipt() {
  if (!receiptRef.value) return
  const win = window.open('', '_blank')
  win.document.write('<html><head><title>Payment Receipt</title></head><body>' + receiptRef.value.innerHTML + '</body></html>')
  win.document.close()
  win.print()
  win.close()
}

function confirmDelete(row) {
  if (!confirm('Delete this pledge and its payment records?')) return
  pledgesStore.deletePledge(row.id).catch(() => {})
}

async function sendReminder(row) {
  reminderMessage.value = ''
  reminderLoadingId.value = row.id
  try {
    await api.post('send-pledge-reminder.php', { pledge_id: row.id })
    reminderSuccess.value = true
    reminderMessage.value = 'Reminder email sent to ' + (row.member_name || 'pledger') + '.'
    setTimeout(() => { reminderMessage.value = '' }, 4000)
  } catch (e) {
    reminderSuccess.value = false
    reminderMessage.value = e?.response?.data?.error || e?.message || 'Failed to send reminder'
  } finally {
    reminderLoadingId.value = null
  }
}

onMounted(() => {
  pledgesStore.fetchPledges().catch(() => {})
  membersStore.fetchMembers().catch(() => {})
})
</script>
