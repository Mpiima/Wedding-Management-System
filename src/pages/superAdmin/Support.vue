<template>
  <div class="page-shell">
    <PageHeader title="Support" description="Monitor tenant activity and login history (mock).">
      <template #actions>
        <Button variant="secondary" @click="refresh">Refresh</Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <DataTable
        title="Activity logs"
        :columns="activityCols"
        :data="activityRows"
        row-key="id"
        searchable
        sortable
        :pagination="true"
        :page-size="6"
      >
      </DataTable>

      <DataTable
        title="Login history"
        :columns="loginCols"
        :data="loginRows"
        row-key="id"
        searchable
        sortable
        :pagination="true"
        :page-size="6"
      >
        <template #cell-status="{ value }">
          <Badge :tone="loginTone(value)">{{ value }}</Badge>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import { useSuperAdminStore } from '@/stores/superAdmin'

const store = useSuperAdminStore()

const activityCols = [
  { key: 'time', label: 'Time', sortable: true },
  { key: 'actor', label: 'Actor', sortable: true },
  { key: 'action', label: 'Action', sortable: true },
  { key: 'detail', label: 'Detail' }
]

const loginCols = [
  { key: 'time', label: 'Time', sortable: true },
  { key: 'user', label: 'User', sortable: true },
  { key: 'ip', label: 'IP', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
]

const activityRows = computed(() => store.activityLogs || [])
const loginRows = computed(() => store.loginHistory || [])

function loginTone(s) {
  if (s === 'Success') return 'success'
  if (s === 'Failed') return 'danger'
  if (s === 'Pending') return 'warning'
  return 'neutral'
}

function refresh() {
  return store.fetchAll()
}

onMounted(refresh)
</script>

