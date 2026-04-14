<template>
  <div class="page-shell">
    <div class="no-print mb-6 flex items-center justify-between gap-3">
      <Button variant="secondary" @click="router.back()">Back</Button>
      <Button @click="window.print()">Print</Button>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-500">
      <svg class="h-8 w-8 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <p class="mt-3 text-sm">Loading…</p>
    </div>

    <div v-else-if="!permit" class="rounded-2xl border border-sc-border bg-white p-8 text-center text-slate-600">
      Permit not found.
    </div>

    <div v-else class="rounded-2xl border border-sc-border bg-white p-10 shadow-card">
      <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
          <div
            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-soft"
          >
            SP
          </div>
          <div class="min-w-0">
            <p class="ui-label">SchPro360</p>
            <h1 class="text-xl font-semibold text-slate-900">Exam Permit</h1>
            <p class="mt-1 text-sm text-slate-600">
              Permit ID: <span class="font-medium text-slate-900">{{ permit.id }}</span>
            </p>
          </div>
        </div>

        <div class="text-right">
          <p class="ui-label">Permit status</p>
          <div
            class="mt-2 inline-flex items-center justify-center rounded-2xl px-8 py-4 text-4xl font-extrabold leading-none tracking-tight"
            :class="statusBigClass"
          >
            {{ permit.permitStatus }}
          </div>
        </div>
      </div>

      <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-sc-border bg-white p-6">
          <p class="ui-label">Student details</p>
          <div class="mt-4 space-y-2 text-sm text-slate-700">
            <div>
              <span class="font-medium text-slate-900">Name:</span> {{ permit.studentName }}
            </div>
            <div>
              <span class="font-medium text-slate-900">Admission No:</span> {{ permit.admissionNo }}
            </div>
            <div>
              <span class="font-medium text-slate-900">Class:</span> {{ permit.studentClass }}
            </div>
            <div>
              <span class="font-medium text-slate-900">Fee status:</span> {{ permit.feeStatus }}
            </div>
          </div>
        </section>

        <section class="rounded-2xl border border-sc-border bg-white p-6">
          <p class="ui-label">Exam details</p>
          <div class="mt-4 space-y-2 text-sm text-slate-700">
            <div>
              <span class="font-medium text-slate-900">Exam:</span> {{ exam?.name || '—' }}
            </div>
            <div>
              <span class="font-medium text-slate-900">Date:</span> {{ exam?.date || '—' }}
            </div>
            <div v-if="exam?.startTime">
              <span class="font-medium text-slate-900">Start time:</span> {{ exam.startTime }}
            </div>
            <div v-if="exam?.location">
              <span class="font-medium text-slate-900">Location:</span> {{ exam.location }}
            </div>
          </div>
        </section>
      </div>

      <div class="mt-8 border-t border-sc-border pt-5 text-sm text-slate-600">
        <p>
          Generated on: <span class="font-medium text-slate-900">{{ generatedAt }}</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from '@/components/ui/Button.vue'
import { useExamPermitsStore } from '@/stores/examPermits'

const route = useRoute()
const router = useRouter()
const permitsStore = useExamPermitsStore()

const permitId = computed(() => route.params.permitId)

onMounted(async () => {
  await permitsStore.fetchAll()
})

const loading = computed(() => permitsStore.loading)
const permit = computed(() => permitsStore.getPermitById(permitId.value))
const exam = computed(() => permitsStore.exam)

const generatedAt = ref(new Date().toLocaleString())

const statusBigClass = computed(() => {
  const s = permit.value?.permitStatus
  const map = {
    Eligible: 'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-600/15',
    Blocked: 'bg-rose-50 text-rose-900 ring-1 ring-rose-600/15',
    Pending: 'bg-amber-50 text-amber-900 ring-1 ring-amber-600/15'
  }
  return map[s] || 'bg-slate-50 text-slate-800 ring-1 ring-slate-600/10'
})
</script>

<style scoped>
@media print {
  :global(body) {
    background: #ffffff !important;
  }

  :global(aside) {
    display: none !important;
  }

  :global(header.sticky) {
    display: none !important;
  }

  :global(main) {
    padding: 0 !important;
    margin: 0 !important;
  }

  :global(.no-print) {
    display: none !important;
  }
}
</style>

