<template>
  <div class="space-y-6">
    <p class="text-sm text-slate-600">
      Define the calendar year for reporting and terms. Only one year can be active at a time.
    </p>

    <div v-if="years.length" class="space-y-2">
      <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Existing years</h3>
      <div class="flex flex-col gap-2">
        <div
          v-for="y in years"
          :key="y.id"
          class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-sc-line bg-white px-4 py-3 shadow-sm"
        >
          <div>
            <span class="font-semibold text-slate-900">{{ y.name }}</span>
            <span class="ml-2 text-sm text-slate-500">{{ y.start_date }} → {{ y.end_date }}</span>
            <span
              v-if="Number(y.is_active) === 1"
              class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-semibold text-emerald-800"
            >
              Active
            </span>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg border border-sc-line px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
              @click="$emit('set-active', y.id)"
            >
              Set active
            </button>
            <button
              type="button"
              class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50"
              @click="$emit('delete-year', y.id)"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 p-4">
      <h3 class="mb-3 text-sm font-semibold text-slate-800">Add academic year</h3>
      <div class="grid gap-3 sm:grid-cols-2">
        <FormInput v-model="form.name" label="Name" placeholder="2025 / 2026" required />
        <div class="hidden sm:block" />
        <FormInput v-model="form.startDate" label="Start date" type="date" required />
        <FormInput v-model="form.endDate" label="End date" type="date" required />
      </div>
      <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-slate-700">
        <input v-model="form.setActive" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
        Set as active year
      </label>
      <div class="mt-4 flex flex-wrap gap-2">
        <Button type="button" @click="submit">Save year</Button>
      </div>
    </div>

    <div v-if="years.length" class="rounded-xl border border-sc-line bg-white p-4 shadow-sm">
      <h3 class="mb-2 text-sm font-semibold text-slate-800">Clone previous year</h3>
      <p class="mb-3 text-xs text-slate-500">
        Copies term names into a new year. Set new dates below. Levels, classes, and subjects stay shared.
      </p>
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <label class="mb-1 block text-xs font-medium text-slate-600">From year</label>
          <select v-model.number="cloneFrom" class="h-10 w-full rounded-xl border border-sc-line px-3 text-sm">
            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
          </select>
        </div>
        <FormInput v-model="clone.name" label="New name" required />
        <FormInput v-model="clone.startDate" label="Start date" type="date" required />
        <FormInput v-model="clone.endDate" label="End date" type="date" required />
      </div>
      <Button class="mt-3" type="button" variant="secondary" @click="doClone">Clone</Button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import FormInput from '@/components/ui/FormInput.vue'
import Button from '@/components/ui/Button.vue'

const props = defineProps({
  years: { type: Array, default: () => [] }
})

const emit = defineEmits(['save-year', 'clone-year', 'set-active', 'delete-year'])

const form = reactive({
  name: '',
  startDate: '',
  endDate: '',
  setActive: true
})

const cloneFrom = ref(null)
const clone = reactive({ name: '', startDate: '', endDate: '' })

watch(
  () => props.years,
  (list) => {
    if (list?.length && cloneFrom.value == null) {
      cloneFrom.value = list[0].id
    }
  },
  { immediate: true }
)

function submit() {
  emit('save-year', {
    name: form.name.trim(),
    startDate: form.startDate,
    endDate: form.endDate,
    isActive: form.setActive
  })
}

function doClone() {
  emit('clone-year', {
    fromYearId: cloneFrom.value,
    name: clone.name.trim(),
    startDate: clone.startDate,
    endDate: clone.endDate
  })
}
</script>
