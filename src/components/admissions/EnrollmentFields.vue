<template>
  <div class="space-y-3">
    <p v-if="yearLabel || periodLabel" class="text-xs text-slate-600">
      <span v-if="yearLabel">Year: <strong>{{ yearLabel }}</strong></span>
      <span v-if="periodLabel" class="ml-3">Period: <strong>{{ periodLabel }}</strong></span>
    </p>
    <SelectInput
      :model-value="modelValue.classId"
      label="Class"
      :options="classSelectOptions"
      placeholder="Select class"
      @update:model-value="patch('classId', coerceId($event))"
    />
    <SelectInput
      :model-value="modelValue.streamId"
      label="Stream (optional)"
      :options="streamSelectOptions"
      placeholder="No stream"
      @update:model-value="patch('streamId', coerceId($event))"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectInput from '@/components/ui/SelectInput.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  },
  classes: { type: Array, default: () => [] },
  streams: { type: Array, default: () => [] },
  yearLabel: { type: String, default: '' },
  periodLabel: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const classSelectOptions = computed(() =>
  props.classes.map((c) => ({
    value: String(c.id),
    label: `${c.level_name ? c.level_name + ' · ' : ''}${c.name} (${c.capacity_remaining ?? '?'}/${c.capacity_max ?? '?'})`
  }))
)

const streamSelectOptions = computed(() =>
  props.streams.map((s) => ({ value: String(s.id), label: s.name }))
)

/** Native <select> emits strings; API accepts numeric ids */
function coerceId(v) {
  if (v === '' || v == null) return ''
  const n = Number(v)
  return Number.isNaN(n) ? v : n
}

function patch(key, val) {
  const v = props.modelValue
  emit('update:modelValue', {
    ...v,
    academicYearId: v.academicYearId,
    studyPeriodId: v.studyPeriodId,
    classId: key === 'classId' ? val : v.classId,
    streamId: key === 'streamId' ? val : v.streamId,
    forceCapacity: v.forceCapacity
  })
}
</script>
