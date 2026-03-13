<template>
  <ModalComponent
    :model-value="modelValue"
    :title="title"
    :close-on-backdrop="false"
    @update:model-value="(val) => emit('update:modelValue', val)"
  >
    <div class="space-y-4">
      <p class="text-sm text-gray-700">
        {{ message }}
      </p>
      <div class="flex justify-end gap-2 pt-2">
        <button
          type="button"
          class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          @click="handleCancel"
        >
          {{ cancelText }}
        </button>
        <button
          type="button"
          class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white shadow-soft hover:bg-rose-600 disabled:opacity-60"
          :class="danger ? 'bg-rose-500 hover:bg-rose-600' : 'bg-emerald-500 hover:bg-emerald-600'"
          @click="handleConfirm"
        >
          {{ confirmText }}
        </button>
      </div>
    </div>
  </ModalComponent>
</template>

<script setup>
import ModalComponent from './ModalComponent.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Are you sure?' },
  message: { type: String, default: 'Please confirm this action.' },
  confirmText: { type: String, default: 'Confirm' },
  cancelText: { type: String, default: 'Cancel' },
  danger: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

function handleConfirm() {
  emit('confirm')
  emit('update:modelValue', false)
}

function handleCancel() {
  emit('cancel')
  emit('update:modelValue', false)
}
</script>

