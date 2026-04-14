<template>
  <div
    class="group/table overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)] transition-shadow duration-300 hover:shadow-[0_8px_30px_rgba(15,23,42,0.08)]"
  >
    <!-- Toolbar: search, filters, custom -->
    <div
      v-if="searchable || filterDefs.length > 0 || $slots.toolbar || title"
      class="flex flex-col gap-5 border-b border-slate-100 bg-gradient-to-b from-slate-50/90 to-white px-5 py-5 sm:px-8 sm:py-5"
    >
      <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 v-if="title" class="text-base font-semibold tracking-tight text-slate-900">{{ title }}</h2>
        <div class="flex flex-1 flex-wrap items-center justify-end gap-2.5 sm:gap-3">
          <div v-if="searchable" class="relative min-w-[220px] max-w-md flex-1">
            <input
              v-model="searchQuery"
              type="search"
              :placeholder="searchPlaceholder"
              class="h-11 w-full rounded-xl border border-slate-200/90 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition-all duration-200 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
            />
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
          </div>
          <div v-for="f in filterDefs" :key="f.key" class="min-w-[9.5rem]">
            <select
              v-model="filterState[f.key]"
              class="h-11 w-full cursor-pointer rounded-xl border border-slate-200/90 bg-white px-3.5 text-sm font-medium text-slate-800 shadow-sm transition focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
              @change="onFilterChange"
            >
              <option v-for="opt in f.options" :key="String(opt.value)" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <slot name="toolbar" />
          </div>
        </div>
      </div>
      <div v-if="selectable && selectedIds.length > 0" class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
        <span class="text-xs font-medium text-slate-600">{{ selectedIds.length }} selected</span>
        <slot name="bulk-actions" :ids="selectedIds" :clear="clearSelection" />
      </div>
    </div>

    <div
      v-if="displayRows.length"
      :class="[
        'relative overflow-x-auto',
        stickyHeader && 'max-h-[min(72vh,880px)] overflow-y-auto overscroll-contain'
      ]"
    >
      <table class="min-w-full border-collapse text-left">
        <thead
          :class="[
            stickyHeader
              ? 'sticky top-0 z-20 border-b border-slate-200/90 bg-white/95 shadow-[0_1px_0_0_rgb(241_245_249)] backdrop-blur-sm supports-[backdrop-filter]:bg-white/75'
              : 'border-b border-slate-100/90 bg-slate-50/50'
          ]"
        >
          <tr class="border-b border-slate-100/90">
            <th v-if="selectable" :class="selectableThPad">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30"
                :checked="allPageSelected"
                @change="toggleSelectAllPage"
              />
            </th>
            <th
              v-for="col in columns"
              :key="col.key"
              :class="[
                thPad,
                'text-left text-2xs font-semibold uppercase tracking-wider text-slate-500 first:pl-6 last:pr-6 sm:first:pl-8 sm:last:pr-8',
                col.thClass,
                col.sortable && sortable && 'cursor-pointer select-none hover:bg-slate-50/90'
              ]"
              @click="col.sortable && sortable ? toggleSort(col.key) : undefined"
            >
              <span class="inline-flex items-center gap-1">
                {{ col.label }}
                <span v-if="col.sortable && sortable" class="text-slate-400">
                  <svg
                    v-if="sortKey !== col.key"
                    class="h-3.5 w-3.5 opacity-40"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                  </svg>
                  <svg
                    v-else-if="sortOrder === 'asc'"
                    class="h-3.5 w-3.5 text-brand-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                  </svg>
                  <svg v-else class="h-3.5 w-3.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </span>
              </span>
            </th>
            <th
              v-if="$slots.actions"
              :class="[
                thPad,
                'w-14 min-w-[3.25rem] px-3 text-right text-2xs font-semibold uppercase tracking-wider text-slate-400 last:pr-5 sm:last:pr-7'
              ]"
            >
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100/90">
          <tr
            v-for="(row, idx) in displayRows"
            :key="rowKey ? row[rowKey] : idx"
            class="interactive-row transition-colors duration-150 hover:bg-slate-50/95 active:bg-slate-100/50"
            :class="[
              selectable && isSelected(row) && 'bg-brand-50/60',
              row.rowClass
            ]"
          >
            <td v-if="selectable" :class="['w-12 align-middle', selectableCellPad]">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30"
                :checked="isSelected(row)"
                @change="toggleRow(row)"
              />
            </td>
            <td
              v-for="(col, cIdx) in columns"
              :key="col.key"
              :class="[
                cellPad,
                'align-middle text-sm leading-snug text-slate-700 first:pl-6 last:pr-6 sm:first:pl-8 sm:last:pr-8',
                col.tdClass,
                cIdx === 0 && !col.noDefaultWeight && 'font-medium text-slate-900'
              ]"
            >
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                {{ row[col.key] }}
              </slot>
            </td>
            <td v-if="$slots.actions" class="relative w-14 min-w-[3.25rem] overflow-visible px-3 text-right align-middle last:pr-5 sm:last:pr-7">
              <slot name="actions" :row="row" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="px-8 py-20 text-center sm:px-10">
      <slot name="empty">
        <div class="mx-auto max-w-sm">
          <div
            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
          >
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
          </div>
          <p class="text-base font-semibold text-slate-800">{{ emptyText }}</p>
          <p class="mt-2 text-sm text-slate-500">Try adjusting search or filters.</p>
        </div>
      </slot>
    </div>

    <div
      v-if="pagination && totalFiltered > 0"
      class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-8"
    >
      <p class="text-xs font-medium text-slate-500">
        Showing {{ rangeStart }}–{{ rangeEnd }} of {{ totalFiltered }}
      </p>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="tap-highlight-none rounded-lg border border-sc-line bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition-all duration-200 ease-out hover:-translate-y-px hover:bg-slate-50 disabled:opacity-50"
          :disabled="currentPage <= 1"
          @click="goPage(currentPage - 1)"
        >
          Previous
        </button>
        <span class="text-xs text-slate-500">Page {{ currentPage }} / {{ totalPages }}</span>
        <button
          type="button"
          class="tap-highlight-none rounded-lg border border-sc-line bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition-all duration-200 ease-out hover:-translate-y-px hover:bg-slate-50 disabled:opacity-50"
          :disabled="currentPage >= totalPages"
          @click="goPage(currentPage + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, reactive, onMounted } from 'vue'

const props = defineProps({
  title: { type: String, default: '' },
  columns: { type: Array, required: true },
  data: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  emptyText: { type: String, default: 'No records yet' },
  pagination: { type: Boolean, default: false },
  pageSize: { type: Number, default: 10 },
  currentPage: { type: Number, default: 1 },
  /** Enable search box */
  searchable: { type: Boolean, default: false },
  searchPlaceholder: { type: String, default: 'Search…' },
  /** Column keys to include in search (default: all columns) */
  searchKeys: { type: Array, default: null },
  /** [{ key, label, options: [{ value, label }] }] */
  filters: { type: Array, default: () => [] },
  /** Show sort UI on columns with sortable: true */
  sortable: { type: Boolean, default: false },
  /** Row selection + bulk actions slot */
  selectable: { type: Boolean, default: false },
  /** v-model for selected row ids */
  selectedIds: { type: Array, default: () => [] },
  /** Sticky header when scrolling vertically */
  stickyHeader: { type: Boolean, default: false },
  /** Taller rows and padding */
  comfortable: { type: Boolean, default: false }
})

const thPad = computed(() => (props.comfortable ? 'px-5 py-4 sm:px-6' : 'px-5 py-3.5 sm:px-6'))

const selectableThPad = computed(() =>
  props.comfortable ? 'w-12 px-3 py-4 first:pl-5 sm:pl-6' : 'w-12 px-3 py-3.5 first:pl-5 sm:pl-6'
)

const cellPad = computed(() => (props.comfortable ? 'px-5 py-5 sm:px-6' : 'px-5 py-4 sm:px-6'))

const selectableCellPad = computed(() =>
  props.comfortable ? 'px-3 py-5 first:pl-5 sm:pl-6' : 'px-3 py-4 first:pl-5 sm:pl-6'
)

const emit = defineEmits(['update:currentPage', 'update:selectedIds', 'sort'])

const searchQuery = ref('')
const sortKey = ref('')
const sortOrder = ref('asc')
const filterState = reactive({})
const filterDefs = computed(() => props.filters || [])

onMounted(() => {
  filterDefs.value.forEach((f) => {
    if (filterState[f.key] === undefined) {
      const first = f.options?.[0]
      filterState[f.key] = first ? first.value : ''
    }
  })
})

watch(
  () => props.filters,
  (list) => {
    list?.forEach((f) => {
      if (filterState[f.key] === undefined && f.options?.length) {
        filterState[f.key] = f.options[0].value
      }
    })
  },
  { deep: true, immediate: true }
)

function onFilterChange() {
  if (props.pagination) emit('update:currentPage', 1)
}

const searchKeysResolved = computed(() => {
  if (props.searchKeys?.length) return props.searchKeys
  return props.columns.map((c) => c.key)
})

const filteredData = computed(() => {
  let rows = [...props.data]
  const q = searchQuery.value.trim().toLowerCase()
  if (props.searchable && q) {
    rows = rows.filter((row) =>
      searchKeysResolved.value.some((k) => String(row[k] ?? '').toLowerCase().includes(q))
    )
  }
  filterDefs.value.forEach((f) => {
    const val = filterState[f.key]
    if (val === '' || val === undefined || val === null) return
    rows = rows.filter((row) => String(row[f.key]) === String(val))
  })
  return rows
})

const sortedData = computed(() => {
  if (!props.sortable || !sortKey.value) return filteredData.value
  const key = sortKey.value
  const order = sortOrder.value
  const copy = [...filteredData.value]
  copy.sort((a, b) => {
    const va = a[key]
    const vb = b[key]
    const na = typeof va === 'number' ? va : String(va ?? '').toLowerCase()
    const nb = typeof vb === 'number' ? vb : String(vb ?? '').toLowerCase()
    if (na < nb) return order === 'asc' ? -1 : 1
    if (na > nb) return order === 'asc' ? 1 : -1
    return 0
  })
  return copy
})

const totalFiltered = computed(() => sortedData.value.length)

const totalPages = computed(() => Math.max(1, Math.ceil(totalFiltered.value / props.pageSize)))

const displayRows = computed(() => {
  if (!props.pagination) return sortedData.value
  const start = (props.currentPage - 1) * props.pageSize
  return sortedData.value.slice(start, start + props.pageSize)
})

const rangeStart = computed(() => {
  if (!totalFiltered.value) return 0
  return (props.currentPage - 1) * props.pageSize + 1
})

const rangeEnd = computed(() => {
  if (!props.pagination) return totalFiltered.value
  return Math.min(props.currentPage * props.pageSize, totalFiltered.value)
})

function goPage(p) {
  const next = Math.min(Math.max(1, p), totalPages.value)
  emit('update:currentPage', next)
}

function toggleSort(key) {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
  emit('sort', { key: sortKey.value, order: sortOrder.value })
  if (props.pagination) emit('update:currentPage', 1)
}

watch(searchQuery, () => {
  if (props.pagination) emit('update:currentPage', 1)
})

function rowId(row) {
  return row[props.rowKey]
}

function isSelected(row) {
  return props.selectedIds.includes(rowId(row))
}

function toggleRow(row) {
  const id = rowId(row)
  const next = [...props.selectedIds]
  const i = next.indexOf(id)
  if (i >= 0) next.splice(i, 1)
  else next.push(id)
  emit('update:selectedIds', next)
}

function clearSelection() {
  emit('update:selectedIds', [])
}

const allPageSelected = computed(() => {
  if (!displayRows.value.length) return false
  return displayRows.value.every((r) => props.selectedIds.includes(rowId(r)))
})

function toggleSelectAllPage() {
  const ids = displayRows.value.map(rowId)
  if (allPageSelected.value) {
    const set = new Set(props.selectedIds)
    ids.forEach((id) => set.delete(id))
    emit('update:selectedIds', [...set])
  } else {
    const set = new Set([...props.selectedIds, ...ids])
    emit('update:selectedIds', [...set])
  }
}
</script>
