<template>
  <div class="space-y-6">
    <p class="text-sm text-slate-600">Streams are optional (e.g. A, B). Link them to all classes or pick specific classes.</p>

    <div class="flex flex-wrap gap-2">
      <input
        v-model="draft"
        type="text"
        class="min-w-[160px] flex-1 rounded-xl border border-sc-line px-3 py-2 text-sm"
        placeholder="Stream name + Enter"
        @keydown.enter.prevent="addStream"
      />
      <Button type="button" @click="addStream">Add stream</Button>
    </div>
    <div class="flex flex-wrap gap-2">
      <span
        v-for="s in streams"
        :key="s.id"
        class="inline-flex items-center gap-1 rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-sm text-violet-900"
      >
        {{ s.name }}
        <button type="button" class="text-violet-700" @click="$emit('remove-stream', s.id)">×</button>
      </span>
    </div>

    <div class="rounded-xl border border-sc-line bg-white p-4 shadow-sm">
      <label class="flex items-center gap-2 text-sm font-medium text-slate-800">
        <input v-model="applyAll" type="checkbox" class="rounded border-slate-300 text-brand-600" />
        Apply selected streams to all classes
      </label>
      <p v-if="!applyAll" class="mt-3 text-xs text-slate-500">Select classes below, then choose streams and link.</p>
      <div v-if="!applyAll" class="mt-2 flex max-h-40 flex-wrap gap-2 overflow-y-auto rounded-lg border border-sc-line p-2">
        <label v-for="c in classes" :key="c.id" class="flex cursor-pointer items-center gap-1.5 text-sm">
          <input v-model="pickedClasses" type="checkbox" :value="c.id" class="rounded border-slate-300" />
          {{ c.name }}
        </label>
      </div>
      <div class="mt-3 flex flex-wrap gap-2">
        <label v-for="s in streams" :key="'p' + s.id" class="flex cursor-pointer items-center gap-1 text-sm">
          <input v-model="pickedStreams" type="checkbox" :value="s.id" class="rounded border-slate-300" />
          {{ s.name }}
        </label>
      </div>
      <div class="mt-3 flex gap-2">
        <Button type="button" :disabled="!pickedStreams.length" @click="link(false)">Add links</Button>
        <Button type="button" variant="secondary" :disabled="!pickedStreams.length" @click="link(true)">Replace links</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Button from '@/components/ui/Button.vue'

defineProps({
  streams: { type: Array, default: () => [] },
  classes: { type: Array, default: () => [] }
})

const emit = defineEmits(['add-stream', 'remove-stream', 'link-streams'])

const draft = ref('')
const applyAll = ref(true)
const pickedClasses = ref([])
const pickedStreams = ref([])

function addStream() {
  const n = draft.value.trim()
  if (!n) return
  emit('add-stream', n)
  draft.value = ''
}

function link(replace) {
  emit('link-streams', {
    applyToAllClasses: applyAll.value,
    classIds: applyAll.value ? [] : [...pickedClasses.value],
    streamIds: [...pickedStreams.value],
    replace
  })
}
</script>
