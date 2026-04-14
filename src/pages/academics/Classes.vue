<template>
  <div class="page-shell">
    <PageHeader title="Classes" description="Class groups and mentors (mock)." />
    <DataTable title="Classes" :columns="cols" :data="rows" row-key="id" searchable sortable />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import { getClasses } from '@/services/mockData'

const rows = ref([])
const cols = [
  { key: 'name', label: 'Class', sortable: true },
  { key: 'students', label: 'Students', sortable: true },
  { key: 'teacher', label: 'Mentor / teacher' }
]

onMounted(async () => {
  rows.value = await getClasses()
})
</script>
