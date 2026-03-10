<template>
  <div class="grid gap-3" :class="gridClass">
    <button
      v-for="(item, i) in items"
      :key="item.id || i"
      type="button"
      class="group relative aspect-square overflow-hidden rounded-xl bg-gray-100 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
      @click="$emit('select', item)"
    >
      <div
        v-if="item.url"
        class="h-full w-full bg-cover bg-center transition group-hover:scale-105"
        :style="{ backgroundImage: `url(${item.url})` }"
      />
      <div
        v-else
        class="flex h-full w-full items-center justify-center text-4xl text-gray-300 group-hover:bg-rose-50/50"
      >
        🖼
      </div>
      <div
        v-if="item.caption"
        class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-2 text-left text-xs text-white"
      >
        {{ item.caption }}
      </div>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  columns: { type: Number, default: 4 }
})

const gridClass = computed(() => {
  const cols = { 2: 'grid-cols-2', 3: 'grid-cols-3', 4: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4', 5: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5', 6: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6' }
  return cols[props.columns] || cols[4]
})

defineEmits(['select'])
</script>
