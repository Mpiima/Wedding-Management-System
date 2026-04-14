<template>
  <div ref="root" class="relative flex justify-end">
    <button
      type="button"
      class="tap-highlight-none rounded-xl p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/25"
      :aria-expanded="open"
      aria-haspopup="true"
      aria-label="Row actions"
      @click.stop="open = !open"
    >
      <EllipsisVerticalIcon class="h-5 w-5" />
    </button>
    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="open"
        class="absolute right-0 top-full z-50 mt-1.5 min-w-[12rem] overflow-hidden rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg shadow-slate-200/50 ring-1 ring-slate-900/5"
        role="menu"
        @click.stop
      >
        <slot :close="close" />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'

const open = ref(false)
const root = ref(null)

function close() {
  open.value = false
}

function onDocClick(e) {
  if (!open.value) return
  const el = root.value
  if (el && !el.contains(e.target)) open.value = false
}

watch(open, (v) => {
  document.removeEventListener('click', onDocClick)
  if (v) {
    requestAnimationFrame(() => document.addEventListener('click', onDocClick))
  }
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
})
</script>
