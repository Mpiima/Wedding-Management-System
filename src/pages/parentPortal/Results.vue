<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Results</h1>
      <p class="mt-1 text-sm text-slate-500">Latest term grades by child (mock).</p>
    </div>
    <DataTable
      title="Academic performance"
      :columns="cols"
      :data="rows"
      row-key="rowId"
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

const rows = computed(() =>
  raw.value.map((r, i) => ({
    ...r,
    rowId: `${r.childId}-${r.subject}-${i}`
  }))
)

const cols = [
  { key: 'childName', label: 'Child', sortable: true },
  { key: 'subject', label: 'Subject', sortable: true },
  { key: 'score', label: 'Score', sortable: true },
  { key: 'grade', label: 'Grade', sortable: true }
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

onMounted(async () => {
  const d = await getParentPortalData()
  raw.value = d.results || []
})
</script>
