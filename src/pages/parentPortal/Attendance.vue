<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Attendance</h1>
      <p class="mt-1 text-sm text-slate-500">Monthly summary per child (mock).</p>
    </div>
    <DataTable
      title="Register summary"
      :columns="cols"
      :data="rows"
      row-key="childId"
      searchable
      :filters="filters"
      sortable
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/components/ui/DataTable.vue'
import { getParentPortalData } from '@/services/mockData'

const raw = ref([])

const cols = [
  { key: 'childName', label: 'Child', sortable: true },
  { key: 'period', label: 'Period', sortable: true },
  { key: 'presentDays', label: 'Present', sortable: true },
  { key: 'absentDays', label: 'Absent', sortable: true },
  { key: 'ratePct', label: 'Rate %', sortable: true }
]

const filters = computed(() => {
  const names = [...new Set(raw.value.map((r) => r.childName))]
  return [
    {
      key: 'childName',
      label: 'Child',
      options: [{ value: '', label: 'All children' }, ...names.map((n) => ({ value: n, label: n }))]
    }
  ]
})

const rows = computed(() => raw.value)

onMounted(async () => {
  const d = await getParentPortalData()
  raw.value = d.attendance || []
})
</script>
