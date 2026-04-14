<template>
  <div class="space-y-6">
    <p class="text-sm text-slate-600">Maintain subjects, apply templates, then assign to classes in bulk.</p>

    <div class="flex flex-wrap gap-2">
      <Button type="button" variant="secondary" @click="$emit('apply-template', 'primary')">Apply primary template</Button>
      <Button type="button" variant="secondary" @click="$emit('apply-template', 'secondary')">Apply secondary template</Button>
    </div>

    <div class="flex flex-wrap gap-2">
      <input
        v-model="draft"
        type="text"
        class="min-w-[200px] flex-1 rounded-xl border border-sc-line px-3 py-2 text-sm"
        placeholder="Subject name + Enter"
        @keydown.enter.prevent="addOne"
      />
      <Button type="button" @click="addOne">Add subject</Button>
    </div>

    <div class="flex flex-wrap gap-2">
      <span
        v-for="s in subjects"
        :key="s.id"
        class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-sm text-amber-950"
      >
        {{ s.name }}
        <button type="button" class="text-amber-800" @click="$emit('remove-subject', s.id)">×</button>
      </span>
    </div>

    <div class="rounded-xl border border-sc-line bg-white p-4 shadow-sm">
      <h3 class="mb-2 text-sm font-semibold text-slate-900">Assign to classes</h3>
      <p class="mb-2 text-xs text-slate-500">Pick classes and subjects; new pairings are added (existing kept).</p>
      <div class="mb-3 flex max-h-36 flex-wrap gap-2 overflow-y-auto rounded-lg border border-sc-line p-2">
        <label v-for="c in classes" :key="c.id" class="flex cursor-pointer items-center gap-1.5 text-sm">
          <input v-model="selClasses" type="checkbox" :value="c.id" class="rounded border-slate-300" />
          {{ c.name }}
        </label>
      </div>
      <div class="mb-3 flex max-h-36 flex-wrap gap-2 overflow-y-auto rounded-lg border border-sc-line p-2">
        <label v-for="s in subjects" :key="'sub' + s.id" class="flex cursor-pointer items-center gap-1.5 text-sm">
          <input v-model="selSubjects" type="checkbox" :value="s.id" class="rounded border-slate-300" />
          {{ s.name }}
        </label>
      </div>
      <Button type="button" :disabled="!selClasses.length || !selSubjects.length" @click="assign">Assign selected</Button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Button from '@/components/ui/Button.vue'

defineProps({
  subjects: { type: Array, default: () => [] },
  classes: { type: Array, default: () => [] }
})

const emit = defineEmits(['add-subject', 'remove-subject', 'apply-template', 'assign'])

const draft = ref('')
const selClasses = ref([])
const selSubjects = ref([])

function addOne() {
  const n = draft.value.trim()
  if (!n) return
  emit('add-subject', n)
  draft.value = ''
}

function assign() {
  emit('assign', { classIds: [...selClasses.value], subjectIds: [...selSubjects.value] })
}
</script>
