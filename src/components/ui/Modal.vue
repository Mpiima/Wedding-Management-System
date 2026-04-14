<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="modal"
        role="presentation"
      >
        <!-- Layer 1: dimmer — single flat tone (no gradients); click to dismiss -->
        <div
          class="modal__backdrop"
          aria-hidden="true"
          @click="handleBackdropClick"
        />

        <!-- Layer 2: dialog surface — stops click from reaching backdrop -->
        <div
          class="modal__dialog"
          :class="maxWidthClass"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          tabindex="-1"
          @click.stop
        >
          <header class="modal__header">
            <h2 :id="titleId" class="modal__title">
              {{ title }}
            </h2>
            <button type="button" class="modal__close" aria-label="Close dialog" @click="close">
              <span class="modal__close-icon" aria-hidden="true">×</span>
            </button>
          </header>

          <div class="modal__body">
            <slot />
          </div>

          <footer v-if="$slots.footer" class="modal__footer">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { watch, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  closeOnBackdrop: { type: Boolean, default: true },
  closeOnEscape: { type: Boolean, default: true },
  /** Tailwind max-width class, e.g. max-w-lg, max-w-2xl */
  maxWidthClass: { type: String, default: 'max-w-lg' }
})

const emit = defineEmits(['update:modelValue'])

/** Stable per instance (not computed) — avoids id churn and matches a11y best practice */
const titleId = `modal-title-${Math.random().toString(36).slice(2, 11)}`

function close() {
  emit('update:modelValue', false)
}

function handleBackdropClick() {
  if (props.closeOnBackdrop) close()
}

function onDocumentKeydown(e) {
  if (!props.modelValue) return
  if (e.key === 'Escape' && props.closeOnEscape) {
    e.preventDefault()
    close()
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = open ? 'hidden' : ''
    if (open) {
      document.addEventListener('keydown', onDocumentKeydown)
    } else {
      document.removeEventListener('keydown', onDocumentKeydown)
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  if (typeof document === 'undefined') return
  document.removeEventListener('keydown', onDocumentKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
/* ---------------------------------------------------------------------------
   Modal — layout & tokens (enterprise-style: flat overlay + elevated surface)
   --------------------------------------------------------------------------- */
.modal {
  --modal-overlay: rgba(15, 23, 42, 0.55);
  --modal-blur: 6px;
  --modal-surface: #ffffff;
  --modal-border: rgba(15, 23, 42, 0.1);
  --modal-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.2), 0 0 0 1px rgba(15, 23, 42, 0.05);

  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

@media (min-width: 640px) {
  .modal {
    padding: 1.5rem;
  }
}

.modal__backdrop {
  position: absolute;
  inset: 0;
  z-index: 0;
  background-color: var(--modal-overlay);
  backdrop-filter: blur(var(--modal-blur));
  -webkit-backdrop-filter: blur(var(--modal-blur));
}

.modal__dialog {
  position: relative;
  z-index: 1;
  display: flex;
  width: 100%;
  max-height: min(90vh, 880px);
  flex-direction: column;
  overflow: hidden;
  border-radius: 0.75rem;
  border: 1px solid var(--modal-border);
  background-color: var(--modal-surface);
  box-shadow: var(--modal-shadow);
}

.modal__header {
  display: flex;
  flex-shrink: 0;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  padding: 1rem 1.25rem;
}

@media (min-width: 640px) {
  .modal__header {
    padding: 1.125rem 1.5rem;
  }
}

.modal__title {
  margin: 0;
  font-size: 1.0625rem;
  font-weight: 600;
  line-height: 1.35;
  letter-spacing: -0.01em;
  color: rgb(15 23 42);
}

.modal__close {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  margin: -0.25rem -0.25rem 0 0;
  min-width: 2.25rem;
  min-height: 2.25rem;
  border: none;
  border-radius: 0.5rem;
  background: transparent;
  color: rgb(100 116 139);
  cursor: pointer;
  transition:
    background-color 0.15s ease,
    color 0.15s ease;
}

.modal__close:hover {
  background-color: rgb(241 245 249);
  color: rgb(15 23 42);
}

.modal__close:focus-visible {
  outline: 2px solid rgb(44 170 226);
  outline-offset: 2px;
}

.modal__close-icon {
  font-size: 1.375rem;
  line-height: 1;
  font-weight: 400;
}

.modal__body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  padding: 1.25rem 1.25rem 1.5rem;
  -webkit-overflow-scrolling: touch;
}

@media (min-width: 640px) {
  .modal__body {
    padding: 1.5rem;
  }
}

.modal__footer {
  flex-shrink: 0;
  border-top: 1px solid rgba(15, 23, 42, 0.08);
  background-color: rgb(248 250 252);
  padding: 1rem 1.25rem;
}

@media (min-width: 640px) {
  .modal__footer {
    padding: 1rem 1.5rem;
  }
}

/* Transition: root fades; dialog lifts slightly (no heavy scale) */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-active .modal__dialog,
.modal-leave-active .modal__dialog {
  transition:
    opacity 0.2s ease,
    transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal__dialog,
.modal-leave-to .modal__dialog {
  opacity: 0;
  transform: translateY(10px);
}
</style>
