<template>
  <div class="page-shell">
    <PageHeader title="Analytics" description="Operational analytics across the SaaS platform (mock).">
      <template #actions>
        <Button variant="secondary" @click="refresh">Refresh</Button>
      </template>
    </PageHeader>

    <div class="mt-2 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <LineAreaChart
        title="System usage"
        subtitle="Weekly activity (mock units)"
        :labels="systemUsage.labels"
        :values="systemUsage.values"
      />
      <BarChart
        title="Active schools"
        subtitle="Tenants reporting usage"
        :labels="activeSchools.labels"
        :values="activeSchools.values"
      />
    </div>

    <div class="mt-6">
      <BarChart
        title="Feature usage"
        subtitle="Adoption by feature (mock)"
        :labels="featureUsage.labels"
        :values="featureUsage.values"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import LineAreaChart from '@/components/charts/LineAreaChart.vue'
import BarChart from '@/components/charts/BarChart.vue'
import { useSuperAdminStore } from '@/stores/superAdmin'

const store = useSuperAdminStore()

const analytics = computed(() => store.analytics || {})

const systemUsage = computed(() => analytics.value.systemUsage || { labels: [], values: [] })
const activeSchools = computed(() => analytics.value.activeSchools || { labels: [], values: [] })
const featureUsage = computed(() => analytics.value.featureUsage || { labels: [], values: [] })

function refresh() {
  return store.fetchAll()
}

onMounted(refresh)
</script>

