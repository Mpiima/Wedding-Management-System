<template>
  <div class="card-luxury overflow-hidden">
    <div class="border-b border-rose-100/60 px-5 py-4">
      <h3 class="font-display text-sm font-semibold text-wmis-text">Recent Activity</h3>
    </div>
    <ul class="divide-y divide-rose-100/50">
      <li
        v-for="(item, i) in items"
        :key="item.id || i"
        class="flex gap-3 px-5 py-3.5 transition-all duration-300 hover:bg-gradient-to-r hover:from-rose-50/50 hover:to-transparent"
      >
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-rose-100 to-rose-200/50 text-rose-500 text-sm">
          {{ item.icon || '•' }}
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-sm text-wmis-text">
            <span class="font-medium">{{ item.title }}</span>
            {{ item.description }}
          </p>
          <p class="mt-0.5 text-xs text-gray-500">{{ item.time }}</p>
        </div>
      </li>
    </ul>
    <div v-if="!items.length" class="px-5 py-10 text-center text-gray-400 text-sm">
      No recent activity
    </div>
  </div>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] }
})
</script>
