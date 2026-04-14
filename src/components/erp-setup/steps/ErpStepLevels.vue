<template>
  <div class="space-y-4">
    <p class="text-sm text-slate-600">Add education levels (e.g. Nursery, Primary, Secondary). Used to group classes.</p>
    <div class="flex flex-wrap gap-2">
      <input
        v-model="draft"
        type="text"
        placeholder="Level name + Enter"
        class="min-w-[200px] flex-1 rounded-xl border border-sc-line px-3 py-2 text-sm"
        @keydown.enter.prevent="add"
      />
      <Button type="button" @click="add">Add</Button>
    </div>
    <div class="flex flex-wrap gap-2">
      <span
        v-for="l in levels"
        :key="l.id"
        class="inline-flex items-center gap-1 rounded-full border border-sc-line bg-white px-3 py-1.5 text-sm font-medium text-slate-800 shadow-sm"
      >
        {{ l.name }}
        <button type="button" class="text-rose-500 hover:text-rose-700" aria-label="Remove" @click="$emit('remove', l.id)">×</button>
      </span>
    </div>
    <p v-if="!levels.length" class="text-sm text-slate-500">No levels yet.</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Button from '@/components/ui/Button.vue'

defineProps({
  levels: { type: Array, default: () => [] }
})

const emit = defineEmits(['add', 'remove'])

const draft = ref('')

function add() {
  const n = draft.value.trim()
  if (!n) return
  emit('add', n)
  draft.value = ''
}
</script>
