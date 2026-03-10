<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="handleBackdropClick"
      >
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" />
        <div
          class="relative w-full max-w-lg rounded-2xl bg-gradient-to-b from-white to-rose-50/20 shadow-soft-lg border border-rose-100/80 overflow-hidden"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
        >
          <div class="flex items-center justify-between border-b border-rose-100/60 px-6 py-4 bg-white/80">
            <h2 :id="titleId" class="font-display text-lg font-semibold text-wmis-text">
              {{ title }}
            </h2>
            <button
              type="button"
              class="rounded-xl p-2 text-gray-400 transition-all duration-200 hover:bg-rose-50 hover:text-rose-600"
              aria-label="Close"
              @click="close"
            >
              <span class="text-xl leading-none">×</span>
            </button>
          </div>
          <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
            <slot />
          </div>
          <div v-if="$slots.footer" class="border-t border-rose-100/60 px-6 py-4 bg-rose-50/30">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  closeOnBackdrop: { type: Boolean, default: true }
})

const emit = defineEmits(['update:modelValue'])
const titleId = computed(() => `modal-title-${Math.random().toString(36).slice(2)}`)

function close() {
  emit('update:modelValue', false)
}

function handleBackdropClick() {
  if (props.closeOnBackdrop) close()
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s cubic-bezier(0.33, 1, 0.68, 1);
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.96);
}
</style>
