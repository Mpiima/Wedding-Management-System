<template>
  <div class="space-y-6">
    <p v-if="studentName" class="text-sm text-slate-600">
      Fee statement for <strong>{{ studentName }}</strong>
      <span v-if="admissionNo" class="font-mono text-slate-500"> · {{ admissionNo }}</span>
    </p>
    <h1 class="text-2xl font-semibold text-slate-900">Fees</h1>
    <DataTable title="Statement" :columns="cols" :data="rows" row-key="id" searchable>
      <template #cell-amount="{ value }">
        {{ formatCurrency(value) }}
      </template>
      <template #cell-status="{ value }">
        <Badge :tone="value === 'Paid' ? 'success' : 'warning'">{{ value }}</Badge>
      </template>
    </DataTable>
    <p class="text-xs text-slate-500">Detailed fee lines will connect to billing when your school enables finance integration.</p>
  </div>
</template>

<script setup>
import { computed, inject, ref, onMounted } from 'vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import { getPortalData } from '@/services/mockData'
import { formatCurrency } from '@/utils/formatters'

const portal = inject('portalContext', null)

const studentName = computed(() => portal?.displayName || '')
const admissionNo = computed(() => portal?.student?.admission_number || '')

const rows = ref([])
const cols = [
  { key: 'description', label: 'Description' },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'status', label: 'Status' }
]

onMounted(async () => {
  const d = await getPortalData()
  rows.value = d.fees || []
})
</script>
