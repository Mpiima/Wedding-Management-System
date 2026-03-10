<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-wmis-text">Invited Guests</h1>
        <p class="text-sm text-gray-500">Guest list, RSVP tracking, and table assignments</p>
      </div>
      <div class="flex gap-2">
        <input
          v-model="search"
          type="search"
          placeholder="Search guests..."
          class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
        />
        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white shadow-soft hover:bg-rose-600">+ Add guest</button>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <StatCard label="Invited" :value="guests.length" />
      <StatCard label="Confirmed" :value="confirmedCount" />
      <StatCard label="Pending" :value="guests.length - confirmedCount" />
    </div>

    <TableComponent
      title="Invited guests"
      :columns="columns"
      :data="filteredGuests"
      row-key="id"
      :pagination="true"
      :page-size="10"
      v-model:current-page="currentPage"
    >
      <template #cell-status="{ value }">
        <span
          class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
          :class="value === 'Confirmed' ? 'bg-emerald-100 text-emerald-700' : value === 'Declined' ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700'"
        >
          {{ value }}
        </span>
      </template>
      <template #actions>
        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-medium">Edit</button>
      </template>
    </TableComponent>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import StatCard from '@/components/StatCard.vue'
import TableComponent from '@/components/TableComponent.vue'

const search = ref('')
const currentPage = ref(1)

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'side', label: 'Side' },
  { key: 'email', label: 'Email' },
  { key: 'status', label: 'Status' }
]

const guests = ref([
  { id: 1, name: 'Emma Stone', side: 'Bride', email: 'emma@example.com', status: 'Confirmed' },
  { id: 2, name: 'Liam Johnson', side: 'Groom', email: 'liam@example.com', status: 'Pending' },
  { id: 3, name: 'Ava Green', side: 'Both', email: 'ava@example.com', status: 'Declined' }
])

const confirmedCount = computed(() => guests.value.filter(g => g.status === 'Confirmed').length)
const filteredGuests = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return guests.value
  return guests.value.filter(g => g.name.toLowerCase().includes(q) || (g.email && g.email.toLowerCase().includes(q)))
})
</script>
