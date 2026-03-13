<template>
  <div class="flex flex-wrap items-center gap-3">
    <div class="relative flex-1 min-w-[160px] max-w-xs">
      <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
      </span>
      <input
        :value="searchQuery"
        type="search"
        placeholder="Search..."
        class="block w-full rounded-xl border border-rose-100 bg-white py-2 pl-9 pr-3 text-sm placeholder-gray-400 focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20"
        @input="$emit('update:searchQuery', $event.target.value)"
      />
    </div>
    <slot name="filters" />
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 disabled:opacity-50"
        :disabled="exportDisabled"
        @click="$emit('exportExcel')"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        Excel
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 disabled:opacity-50"
        :disabled="exportDisabled"
        @click="$emit('exportPdf')"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
        PDF
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  searchQuery: { type: String, default: '' },
  exportDisabled: { type: Boolean, default: false }
})
defineEmits(['update:searchQuery', 'exportExcel', 'exportPdf'])
</script>
