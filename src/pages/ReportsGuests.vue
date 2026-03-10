<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Guest Report</h1>
      <p class="text-sm text-gray-500">RSVP and attendance reports</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <StatCard label="Invited" :value="guestsStore.guests.length" />
      <StatCard label="Confirmed" :value="confirmedCount" />
      <StatCard label="Pending" :value="guestsStore.guests.length - confirmedCount - declinedCount" />
      <StatCard label="Declined" :value="declinedCount" />
    </div>

    <p v-if="guestsStore.errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ guestsStore.errorMessage }}</p>

    <TableComponent title="Guest status breakdown" :columns="columns" :data="guestsStore.guests" row-key="id" :pagination="true" :page-size="15" v-model:current-page="currentPage" empty="No guests yet">
      <template #cell-status="{ value }">
        <span
          class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
          :class="value === 'Confirmed' ? 'bg-emerald-100 text-emerald-700' : value === 'Declined' ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700'"
        >
          {{ value }}
        </span>
      </template>
    </TableComponent>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import StatCard from '@/components/StatCard.vue'
import TableComponent from '@/components/TableComponent.vue'
import { useInvitedGuestsStore } from '@/stores/invitedGuests'

const guestsStore = useInvitedGuestsStore()
const currentPage = ref(1)

const confirmedCount = computed(() => guestsStore.guests.filter((g) => g.status === 'Confirmed').length)
const declinedCount = computed(() => guestsStore.guests.filter((g) => g.status === 'Declined').length)

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'side', label: 'Side' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'status', label: 'Status' }
]

onMounted(() => guestsStore.fetchGuests().catch(() => {}))
</script>
