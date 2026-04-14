<template>
  <div class="page-shell">
    <PageHeader title="Exam Permits List" description="Approve or block students based on exam eligibility (mock).">
      <template #actions>
        <Button variant="secondary" @click="refresh">Refresh</Button>
      </template>
    </PageHeader>

    <DataTable
      v-if="!loading"
      title="Exam permits"
      :columns="cols"
      :data="rows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="8"
      v-model:current-page="page"
    >
      <template #cell-feeStatus="{ value }">
        <Badge :tone="feeTone(value)">{{ value }}</Badge>
      </template>

      <template #cell-permitStatus="{ value }">
        <Badge :tone="permitTone(value)">{{ value }}</Badge>
      </template>

      <template #actions="{ row }">
        <div class="flex flex-wrap items-center justify-end gap-2">
          <Button
            variant="secondary"
            class="!px-2 !py-1 text-xs"
            :disabled="row.permitStatus !== 'Pending'"
            @click="openConfirm('approve', row)"
          >
            Approve
          </Button>
          <Button
            variant="secondary"
            class="!px-2 !py-1 text-xs"
            :disabled="row.permitStatus !== 'Pending'"
            @click="openConfirm('block', row)"
          >
            Block
          </Button>
          <Button variant="ghost" class="!px-2 !py-1 text-xs" @click="print(row)">Print</Button>
        </div>
      </template>
    </DataTable>

    <div v-else class="flex flex-col items-center justify-center py-20 text-slate-500">
      <svg class="h-8 w-8 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <p class="mt-3 text-sm">Loading…</p>
    </div>

    <Modal v-model="confirmOpen" :title="confirmTitle">
      <p class="text-sm text-slate-600">
        Student: <span class="font-medium text-slate-800">{{ targetPermit?.studentName }}</span>
      </p>
      <p class="mt-2 text-sm text-slate-600">
        This will set permit status to
        <span class="font-medium text-slate-800">{{ nextStatusLabel }}</span>.
      </p>

      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="confirmOpen = false">Cancel</Button>
          <Button @click="applyDecision">Confirm</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import { useExamPermitsStore } from '@/stores/examPermits'

const router = useRouter()
const permitsStore = useExamPermitsStore()

const page = ref(1)

const cols = [
  { key: 'studentName', label: 'Student', sortable: true },
  { key: 'studentClass', label: 'Class', sortable: true },
  { key: 'feeStatus', label: 'Fee status', sortable: true },
  { key: 'permitStatus', label: 'Permit status', sortable: true }
]

const loading = computed(() => permitsStore.loading)
const rows = computed(() => permitsStore.permits)

function permitTone(s) {
  if (s === 'Eligible') return 'success'
  if (s === 'Blocked') return 'danger'
  if (s === 'Pending') return 'warning'
  return 'neutral'
}

function feeTone(s) {
  if (s === 'Paid') return 'success'
  if (s === 'Partial') return 'warning'
  if (s === 'Unpaid') return 'danger'
  return 'neutral'
}

function refresh() {
  return permitsStore.fetchAll()
}

function print(row) {
  router.push(`/examinations/exam-permits/print/${row.id}`)
}

const confirmOpen = ref(false)
const confirmAction = ref('approve') // 'approve' | 'block'
const targetPermit = ref(null)

const nextStatusLabel = computed(() => (confirmAction.value === 'approve' ? 'Eligible' : 'Blocked'))

const confirmTitle = computed(() => (confirmAction.value === 'approve' ? 'Approve permit' : 'Block permit'))

function openConfirm(action, row) {
  confirmAction.value = action
  targetPermit.value = row
  confirmOpen.value = true
}

function applyDecision() {
  if (!targetPermit.value) return
  const id = targetPermit.value.id
  const next = confirmAction.value === 'approve' ? 'Eligible' : 'Blocked'
  permitsStore.updatePermitStatus(id, next)
  confirmOpen.value = false
}

onMounted(async () => {
  await permitsStore.fetchAll()
})
</script>

