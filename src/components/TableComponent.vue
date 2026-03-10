<template>
  <div class="rounded-2xl border border-rose-100/60 bg-gradient-to-b from-white to-rose-50/10 shadow-card overflow-hidden transition-all duration-350 ease-luxury hover:shadow-card-hover hover:border-rose-200/60">
    <div v-if="title || $slots.toolbar" class="flex items-center justify-between gap-3 border-b border-rose-100/60 px-5 py-4 bg-white/50">
      <h2 class="font-display text-sm font-semibold text-wmis-text">{{ title }}</h2>
      <div class="flex items-center gap-2">
        <slot name="toolbar" />
      </div>
    </div>
    <div class="relative overflow-x-auto">
      <table class="min-w-full divide-y divide-rose-100/50 text-left text-sm">
        <thead class="bg-gradient-to-r from-rose-50/50 to-rose-50/30">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-5 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-left text-xs"
              :class="col.thClass"
            >
              {{ col.label }}
            </th>
            <th v-if="$slots.actions" class="px-5 py-3.5 text-right font-semibold text-gray-500 uppercase tracking-wider text-xs">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-rose-100/50">
          <tr
            v-for="(row, idx) in paginatedData"
            :key="rowKey ? row[rowKey] : idx"
            class="transition-colors duration-200 hover:bg-rose-50/40"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-5 py-3 text-gray-700"
              :class="col.tdClass"
            >
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                {{ row[col.key] }}
              </slot>
            </td>
            <td v-if="$slots.actions" class="px-5 py-3 text-right">
              <slot name="actions" :row="row" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="empty && !data.length" class="px-5 py-14 text-center">
      <slot name="empty">
        <p class="font-display text-sm font-medium text-gray-500">{{ empty }}</p>
        <p class="mt-1 text-xs text-gray-400">Get started by adding your first item.</p>
      </slot>
    </div>
    <div
      v-if="pagination && data.length > 0"
      class="flex items-center justify-between border-t border-rose-100/60 px-5 py-3 bg-rose-50/20"
    >
      <p class="text-xs text-gray-500">
        Showing {{ (currentPage - 1) * pageSize + 1 }}–{{ Math.min(currentPage * pageSize, data.length) }} of {{ data.length }}
      </p>
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-xl border border-rose-100/60 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-all duration-200 hover:bg-rose-50 hover:border-rose-200/60 disabled:opacity-50"
          :disabled="currentPage <= 1"
          @click="$emit('update:currentPage', currentPage - 1)"
        >
          Previous
        </button>
        <button
          type="button"
          class="rounded-xl border border-rose-100/60 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-all duration-200 hover:bg-rose-50 hover:border-rose-200/60 disabled:opacity-50"
          :disabled="currentPage >= totalPages"
          @click="$emit('update:currentPage', currentPage + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, default: '' },
  columns: { type: Array, required: true },
  data: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  empty: { type: String, default: 'No data yet' },
  pagination: { type: Boolean, default: false },
  pageSize: { type: Number, default: 10 },
  currentPage: { type: Number, default: 1 }
})

defineEmits(['update:currentPage'])

const totalPages = computed(() => Math.ceil(props.data.length / props.pageSize) || 1)
const paginatedData = computed(() => {
  if (!props.pagination) return props.data
  const start = (props.currentPage - 1) * props.pageSize
  return props.data.slice(start, start + props.pageSize)
})
</script>
