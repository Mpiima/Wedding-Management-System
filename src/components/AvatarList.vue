<template>
  <div class="flex -space-x-2" :class="sizeClass">
    <button
      v-for="(item, i) in displayList"
      :key="item.id || i"
      type="button"
      class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-white bg-rose-100 text-rose-600 text-xs font-semibold ring-2 ring-white transition hover:z-10 hover:scale-110"
      :title="item.name"
    >
      {{ item.initials || item.name.slice(0, 2).toUpperCase() }}
    </button>
    <span
      v-if="overflowCount > 0"
      class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-white bg-gray-100 text-gray-600 text-xs font-medium ring-2 ring-white"
    >
      +{{ overflowCount }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  max: { type: Number, default: 4 },
  size: { type: String, default: 'md' }
})

const sizeClass = computed(() => ({
  'md': '',
  'sm': '[&>button]:h-7 [&>button]:w-7 [&>span]:h-7 [&>span]:w-7',
  'lg': '[&>button]:h-11 [&>button]:w-11 [&>span]:h-11 [&>span]:w-11'
}[props.size]))

const displayList = computed(() => props.items.slice(0, props.max))
const overflowCount = computed(() => Math.max(0, props.items.length - props.max))
</script>
