<template>
  <div class="page-shell">
    <PageHeader title="Exams" description="Scheduled assessments (mock)." />
    <DataTable
      title="Exam schedule"
      :columns="cols"
      :data="rows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="6"
      v-model:current-page="page"
    >
      <template #cell-status="{ value }">
        <Badge :tone="examStatusTone(value)">{{ value }}</Badge>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import { getExams } from '@/services/mockData'

const rows = ref([])
const page = ref(1)
const cols = [
  { key: 'name', label: 'Exam', sortable: true },
  { key: 'grade', label: 'Grade', sortable: true },
  { key: 'date', label: 'Date', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
]

function examStatusTone(s) {
  if (s === 'Completed') return 'success'
  if (s === 'Scheduled') return 'info'
  if (s === 'Draft') return 'neutral'
  return 'neutral'
}

onMounted(async () => {
  rows.value = await getExams()
})
</script>
