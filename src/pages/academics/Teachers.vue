<template>
  <div class="page-shell">
    <PageHeader title="Teachers" description="Teaching staff directory (mock)." />
    <DataTable title="Teachers" :columns="cols" :data="rows" row-key="id" searchable sortable />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import { getTeachers } from '@/services/mockData'

const rows = ref([])
const cols = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'role', label: 'Role' },
  { key: 'subjects', label: 'Subjects' }
]

onMounted(async () => {
  rows.value = await getTeachers()
})
</script>
