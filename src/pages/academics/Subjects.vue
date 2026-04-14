<template>
  <div class="page-shell">
    <PageHeader title="Subjects" description="Curriculum subjects (mock)." />
    <DataTable title="Subjects" :columns="cols" :data="rows" row-key="id" searchable sortable />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import { getSubjects } from '@/services/mockData'

const rows = ref([])
const cols = [
  { key: 'name', label: 'Subject', sortable: true },
  { key: 'code', label: 'Code', sortable: true },
  { key: 'classes', label: 'Classes covered' }
]

onMounted(async () => {
  rows.value = await getSubjects()
})
</script>
