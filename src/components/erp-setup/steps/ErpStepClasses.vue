<template>
  <div class="space-y-6">
    <p class="text-sm text-slate-600">Create classes under each level. Quick generate for P1-P7 or S1-S4 style names.</p>

    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 p-4">
      <h3 class="mb-3 text-sm font-semibold">Quick generate</h3>
      <div class="flex flex-wrap items-end gap-3">
        <div>
          <label class="mb-1 block text-xs text-slate-600">Level</label>
          <select v-model.number="bulk.levelId" class="h-10 rounded-xl border border-sc-line px-3 text-sm">
            <option :value="0" disabled>Select level</option>
            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
          </select>
        </div>
        <FormInput v-model="bulk.prefix" class="w-24" label="Prefix" placeholder="P" />
        <FormInput v-model.number="bulk.from" class="w-20" label="From" type="number" />
        <FormInput v-model.number="bulk.to" class="w-20" label="To" type="number" />
        <Button type="button" @click="runBulk">Generate</Button>
      </div>
    </div>

    <div v-for="l in levels" :key="l.id" class="rounded-xl border border-sc-line bg-white p-4 shadow-sm">
      <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
        <h3 class="font-semibold text-slate-900">{{ l.name }}</h3>
        <div class="flex flex-wrap gap-2">
          <input
            v-model="singleName[l.id]"
            type="text"
            placeholder="Class name"
            class="rounded-lg border border-sc-line px-2 py-1.5 text-sm"
            @keydown.enter.prevent="addOne(l.id)"
          />
          <Button type="button" variant="secondary" class="!h-9" @click="addOne(l.id)">Add class</Button>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="c in classesForLevel(l.id)"
          :key="c.id"
          class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-3 py-1 text-sm text-brand-900"
        >
          {{ c.name }}
          <button type="button" class="text-brand-700" @click="$emit('rename-class', c)">edit</button>
          <button type="button" class="text-rose-600" @click="$emit('remove-class', c.id)">x</button>
        </span>
      </div>
      <p v-if="!classesForLevel(l.id).length" class="text-xs text-slate-500">No classes for this level.</p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import FormInput from '@/components/ui/FormInput.vue'
import Button from '@/components/ui/Button.vue'

const props = defineProps({
  levels: { type: Array, default: () => [] },
  classes: { type: Array, default: () => [] }
})

const emit = defineEmits(['bulk', 'add-class', 'remove-class', 'rename-class'])

const bulk = reactive({ levelId: 0, prefix: 'P', from: 1, to: 7 })
const singleName = ref({})

function classesForLevel(levelId) {
  return props.classes.filter((c) => Number(c.level_id) === Number(levelId))
}

function runBulk() {
  emit('bulk', { ...bulk })
}

function addOne(levelId) {
  const name = (singleName.value[levelId] || '').trim()
  if (!name) return
  emit('add-class', { levelId, name })
  singleName.value[levelId] = ''
}
</script>
