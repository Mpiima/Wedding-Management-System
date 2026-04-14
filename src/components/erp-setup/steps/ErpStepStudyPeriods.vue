<template>
  <div class="space-y-6">
    <p class="text-sm text-slate-600">Add terms or semesters. Drag order via arrows. One active period per academic year.</p>

    <div class="max-w-xs">
      <label class="mb-1 block text-xs font-medium text-slate-600">Academic year</label>
      <select v-model.number="localYearId" class="h-10 w-full rounded-xl border border-sc-line px-3 text-sm" @change="$emit('change-year', localYearId)">
        <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
      </select>
    </div>

    <div v-if="periods.length" class="space-y-2">
      <div
        v-for="(p, idx) in periods"
        :key="p.id"
        class="flex flex-wrap items-center gap-2 rounded-xl border border-sc-line bg-white px-3 py-2 shadow-sm"
      >
        <div class="flex gap-1">
          <button type="button" class="rounded-lg border border-sc-line px-2 py-1 text-xs" :disabled="idx === 0" @click="move(idx, -1)">↑</button>
          <button type="button" class="rounded-lg border border-sc-line px-2 py-1 text-xs" :disabled="idx === periods.length - 1" @click="move(idx, 1)">↓</button>
        </div>
        <div class="min-w-0 flex-1">
          <span class="font-medium text-slate-900">{{ p.name }}</span>
          <span class="ml-2 text-sm text-slate-500">{{ p.start_date || '—' }} → {{ p.end_date || '—' }}</span>
          <span v-if="Number(p.is_active) === 1" class="ml-2 text-2xs font-semibold text-emerald-600">Active</span>
        </div>
        <button type="button" class="text-xs font-semibold text-brand-600" @click="$emit('set-active-period', p.id)">Set active</button>
        <button type="button" class="text-xs text-slate-500" @click="internalEdit(p)">Edit</button>
        <button type="button" class="text-xs text-rose-600" @click="$emit('delete-period', p.id)">Delete</button>
      </div>
    </div>
    <p v-else class="text-sm text-slate-500">No periods yet for this year.</p>

    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 p-4">
      <h3 class="mb-3 text-sm font-semibold">{{ editingId ? 'Edit period' : 'Add period' }}</h3>
      <div class="grid gap-3 sm:grid-cols-2">
        <FormInput v-model="form.name" label="Name" placeholder="Term 1" required />
        <div class="hidden sm:block" />
        <FormInput v-model="form.startDate" label="Start date" type="date" />
        <FormInput v-model="form.endDate" label="End date" type="date" />
      </div>
      <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.setActive" type="checkbox" class="rounded border-slate-300 text-brand-600" />
        Set as active period
      </label>
      <div class="mt-3 flex gap-2">
        <Button type="button" @click="save">{{ editingId ? 'Update' : 'Add' }}</Button>
        <Button v-if="editingId" type="button" variant="secondary" @click="cancelEdit">Cancel</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import FormInput from '@/components/ui/FormInput.vue'
import Button from '@/components/ui/Button.vue'

const props = defineProps({
  years: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  academicYearId: { type: Number, default: 0 }
})

const emit = defineEmits(['change-year', 'save-period', 'set-active-period', 'delete-period', 'reorder'])

const localYearId = ref(0)
const editingId = ref(null)
const form = reactive({ name: '', startDate: '', endDate: '', setActive: false })

watch(
  () => props.academicYearId,
  (id) => {
    localYearId.value = id
  },
  { immediate: true }
)

watch(
  () => props.years,
  (y) => {
    if (y?.length && !localYearId.value) {
      localYearId.value = y[0].id
      emit('change-year', localYearId.value)
    }
  },
  { immediate: true }
)

function move(idx, dir) {
  const next = idx + dir
  if (next < 0 || next >= props.periods.length) return
  const order = props.periods.map((x) => x.id)
  const t = order[idx]
  order[idx] = order[next]
  order[next] = t
  emit('reorder', order)
}

function save() {
  emit('save-period', {
    id: editingId.value,
    academicYearId: localYearId.value,
    name: form.name.trim(),
    startDate: form.startDate || null,
    endDate: form.endDate || null,
    isActive: form.setActive
  })
  cancelEdit()
}

function cancelEdit() {
  editingId.value = null
  form.name = ''
  form.startDate = ''
  form.endDate = ''
  form.setActive = false
}

function internalEdit(p) {
  editingId.value = p.id
  form.name = p.name
  form.startDate = p.start_date || ''
  form.endDate = p.end_date || ''
  form.setActive = Number(p.is_active) === 1
}
</script>
