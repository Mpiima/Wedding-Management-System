<template>
  <div class="page-shell max-w-[1200px]">
    <PageHeader title="Payments" description="Record collections and browse receipts. Payments always link to an invoice." />

    <div class="mb-4 flex flex-wrap gap-2">
      <Button type="button" @click="payModal = true">Record payment</Button>
      <Button type="button" variant="secondary" :disabled="loading" @click="reload">Refresh</Button>
    </div>

    <DataTable
      title="Recent payments"
      :columns="cols"
      :data="rows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="10"
      v-model:current-page="page"
    >
      <template #cell-amount="{ value }">{{ formatCurrency(value) }}</template>
      <template #cell-paid_at="{ value }">{{ value }}</template>
      <template #actions="{ row }">
        <Button variant="ghost" type="button" class="!text-xs" @click="printReceipt(row)">Receipt</Button>
      </template>
    </DataTable>

    <Modal v-model="payModal" title="Record payment">
      <FormInput v-model="payForm.invoiceId" label="Invoice ID" placeholder="e.g. 12" />
      <p class="mb-2 text-xs text-slate-500">Find the invoice ID from the Invoices list or student profile.</p>
      <FormInput v-model="payForm.amount" label="Amount" type="number" step="0.01" />
      <SelectInput v-model="payForm.method" label="Method" :options="methodOptions" />
      <FormInput v-model="payForm.reference" label="Reference" />
      <FormInput v-model="payForm.paidAt" label="Date" type="date" />
      <FormInput v-model="payForm.notes" label="Notes (optional)" />
      <template #footer>
        <Button variant="secondary" type="button" @click="payModal = false">Cancel</Button>
        <Button type="button" :disabled="saving" @click="submitPay">{{ saving ? 'Saving…' : 'Save' }}</Button>
      </template>
    </Modal>

    <Teleport to="body">
      <div
        v-if="receiptRow"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="receiptRow = null"
      >
        <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl print:shadow-none" id="receipt-print">
          <h3 class="text-center text-lg font-bold text-slate-900">Payment receipt</h3>
          <p class="mt-1 text-center text-xs text-slate-500">{{ receiptRow.receipt_number }}</p>
          <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-slate-500">Student</dt>
              <dd class="font-medium">{{ receiptRow.student_name }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Invoice</dt>
              <dd>{{ receiptRow.invoice_number }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Amount</dt>
              <dd class="text-lg font-bold">{{ formatCurrency(receiptRow.amount) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Method</dt>
              <dd class="capitalize">{{ receiptRow.method }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-slate-500">Date</dt>
              <dd>{{ receiptRow.paid_at }}</dd>
            </div>
            <div v-if="receiptRow.reference" class="flex justify-between">
              <dt class="text-slate-500">Reference</dt>
              <dd class="font-mono text-xs">{{ receiptRow.reference }}</dd>
            </div>
          </dl>
          <div class="mt-6 flex gap-2 print:hidden">
            <Button type="button" class="flex-1" @click="doPrint">Print</Button>
            <Button variant="secondary" type="button" class="flex-1" @click="receiptRow = null">Close</Button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { formatCurrency } from '@/utils/formatters'
import { financeApi } from '@/services/financeApi'
import useNotificationStore from '@/stores/notificationStore'

const route = useRoute()
const { successToast, errorToast } = useNotificationStore()

const loading = ref(false)
const saving = ref(false)
const page = ref(1)
const rows = ref([])
const payModal = ref(false)
const receiptRow = ref(null)

const payForm = ref({
  invoiceId: '',
  amount: '',
  method: 'cash',
  reference: '',
  paidAt: new Date().toISOString().slice(0, 10),
  notes: ''
})

const methodOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'mobile_money', label: 'Mobile money' },
  { value: 'bank', label: 'Bank' },
  { value: 'card', label: 'Card' },
  { value: 'other', label: 'Other' }
]

const cols = [
  { key: 'receipt_number', label: 'Receipt', sortable: true },
  { key: 'paid_at', label: 'Date', sortable: true },
  { key: 'student_name', label: 'Student', sortable: true },
  { key: 'invoice_number', label: 'Invoice', sortable: true },
  { key: 'method', label: 'Method', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'reference', label: 'Reference', sortable: true }
]

async function reload() {
  loading.value = true
  try {
    const { data } = await financeApi.payments.list()
    rows.value = data?.data || []
  } catch (e) {
    errorToast('Payments', e?.response?.data?.error || e?.message)
  } finally {
    loading.value = false
  }
}

async function submitPay() {
  saving.value = true
  try {
    const { data } = await financeApi.payments.record({
      invoiceId: Number(payForm.value.invoiceId),
      amount: Number(payForm.value.amount),
      method: payForm.value.method,
      reference: payForm.value.reference || undefined,
      paidAt: payForm.value.paidAt,
      notes: payForm.value.notes || undefined
    })
    successToast('Saved', `Receipt ${data?.data?.receiptNumber || ''}`)
    payModal.value = false
    payForm.value = {
      invoiceId: '',
      amount: '',
      method: 'cash',
      reference: '',
      paidAt: new Date().toISOString().slice(0, 10),
      notes: ''
    }
    await reload()
  } catch (e) {
    errorToast('Payment', e?.response?.data?.error || e?.message)
  } finally {
    saving.value = false
  }
}

function printReceipt(row) {
  receiptRow.value = { ...row }
}

function doPrint() {
  window.print()
}

watch(
  () => route.query.invoice,
  (id) => {
    if (id) {
      payForm.value.invoiceId = String(id)
      payModal.value = true
    }
  },
  { immediate: true }
)

onMounted(reload)
</script>
