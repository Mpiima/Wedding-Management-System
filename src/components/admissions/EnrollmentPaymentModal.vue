<template>
  <Modal
    :model-value="modelValue"
    :title="titleText"
    max-width-class="max-w-2xl"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="space-y-4">
      <p v-if="applicant" class="text-sm text-slate-600">
        Enrolling <strong>{{ applicant.first_name }} {{ applicant.last_name }}</strong>
      </p>

      <div v-if="!hasYearPeriodLists" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
        Configure at least one academic year and study period in Academic setup before enrolling.
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <SelectInput
          v-model="form.academicYearId"
          label="Academic year"
          :options="yearOptions"
          placeholder="Select year"
          @update:model-value="onYearChange"
        />
        <SelectInput
          v-model="form.studyPeriodId"
          label="Study period"
          :options="periodOptions"
          placeholder="Select period"
        />
      </div>

      <EnrollmentFields
        v-model="form"
        :classes="contextClasses"
        :streams="contextStreams"
        :year-label="yearLabel"
        :period-label="periodLabel"
      />

      <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fees (this period)</h4>
        <p v-if="feeLoading" class="mt-2 text-sm text-slate-500">Calculating…</p>
        <p v-else-if="feeMessage" class="mt-2 text-sm text-amber-800">{{ feeMessage }}</p>
        <template v-else>
          <ul v-if="feeLines.length" class="mt-2 space-y-1 text-sm text-slate-700">
            <li v-for="(ln, i) in feeLines" :key="i" class="flex justify-between gap-4">
              <span>{{ ln.label }}</span>
              <span class="tabular-nums">{{ formatMoney(ln.amount) }}</span>
            </li>
          </ul>
          <p class="mt-3 flex justify-between border-t border-slate-200 pt-2 text-sm font-semibold text-slate-900">
            <span>Total required</span>
            <span class="tabular-nums">{{ formatMoney(feeTotal) }}</span>
          </p>
        </template>
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <FormInput
          v-model.number="form.amountPaid"
          type="number"
          min="0"
          step="0.01"
          label="Amount paid now"
        />
        <SelectInput
          v-model="form.paymentMethod"
          label="Payment method"
          :options="payMethodOptions"
          placeholder="Method"
        />
      </div>
      <FormInput v-model="form.paymentReference" label="Reference / transaction ID" />

      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.forceCapacity" type="checkbox" class="rounded border-slate-300 text-brand-600" />
        Override class capacity
      </label>
    </div>

    <template #footer>
      <div class="flex flex-wrap justify-end gap-2">
        <Button type="button" variant="secondary" :disabled="submitting" @click="$emit('update:modelValue', false)">
          Cancel
        </Button>
        <Button type="button" :disabled="!canSubmit || submitting" @click="submit">
          {{ submitting ? 'Processing…' : 'Enroll & record payment' }}
        </Button>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import EnrollmentFields from '@/components/admissions/EnrollmentFields.vue'
import { admissionsApi } from '@/services/admissionsApi'
import useNotificationStore from '@/stores/notificationStore'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  applicant: { type: Object, default: null }
})

const emit = defineEmits(['update:modelValue', 'success'])
const { successToast, errorToast } = useNotificationStore()

const ctx = ref(null)
const submitting = ref(false)
const feeLoading = ref(false)
const feeTotal = ref(0)
const feeLines = ref([])
const feeMessage = ref('')

const form = ref({
  academicYearId: '',
  studyPeriodId: '',
  classId: '',
  streamId: '',
  forceCapacity: false,
  amountPaid: '',
  paymentMethod: 'cash',
  paymentReference: ''
})

const titleText = computed(() => 'Enroll & record payment')

const hasYearPeriodLists = computed(
  () => !!(ctx.value?.academicYears?.length && ctx.value?.studyPeriods?.length)
)

const yearOptions = computed(() =>
  (ctx.value?.academicYears || []).map((y) => ({
    value: String(y.id),
    label: `${y.name}${Number(y.is_active) ? ' · active' : ''}`
  }))
)

const periodOptions = computed(() => {
  const yid = String(form.value.academicYearId || '')
  if (!yid) return []
  return (ctx.value?.studyPeriods || [])
    .filter((p) => String(p.academic_year_id) === yid)
    .map((p) => ({
      value: String(p.id),
      label: `${p.name}${Number(p.is_active) ? ' · active' : ''}`
    }))
})

const contextClasses = computed(() => ctx.value?.classes || [])
const contextStreams = computed(() => ctx.value?.streams || [])

const yearLabel = computed(() => {
  const id = form.value.academicYearId
  const y = (ctx.value?.academicYears || []).find((x) => String(x.id) === String(id))
  return y?.name || ''
})

const periodLabel = computed(() => {
  const id = form.value.studyPeriodId
  const p = (ctx.value?.studyPeriods || []).find((x) => String(x.id) === String(id))
  return p?.name || ''
})

const payMethodOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'mobile_money', label: 'Mobile money' },
  { value: 'bank', label: 'Bank' },
  { value: 'card', label: 'Card' },
  { value: 'other', label: 'Other' }
]

function idOk(v) {
  if (v === '' || v == null) return false
  const n = Number(v)
  return !Number.isNaN(n) && n > 0
}

const canSubmit = computed(() => {
  if (!props.applicant?.id) return false
  return idOk(form.value.academicYearId) && idOk(form.value.studyPeriodId) && idOk(form.value.classId)
})

function onYearChange() {
  const yid = String(form.value.academicYearId || '')
  const periods = (ctx.value?.studyPeriods || []).filter((p) => String(p.academic_year_id) === yid)
  const ok = periods.some((p) => String(p.id) === String(form.value.studyPeriodId))
  if (!ok) form.value.studyPeriodId = ''
}

function formatMoney(n) {
  const x = Number(n)
  if (Number.isNaN(x)) return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'GHS', minimumFractionDigits: 2 }).format(x)
}

async function loadContext() {
  const { data } = await admissionsApi.context()
  ctx.value = data?.data || null
  const y = ctx.value?.academicYear?.id
  const p = ctx.value?.studyPeriod?.id
  form.value.academicYearId = y != null && y !== '' ? String(y) : ''
  form.value.studyPeriodId = p != null && p !== '' ? String(p) : ''
}

async function refreshFeePreview() {
  feeMessage.value = ''
  feeLines.value = []
  feeTotal.value = 0
  if (!idOk(form.value.classId) || !idOk(form.value.academicYearId) || !idOk(form.value.studyPeriodId)) {
    return
  }
  feeLoading.value = true
  try {
    const { data } = await admissionsApi.feePreview({
      classId: form.value.classId,
      academicYearId: form.value.academicYearId,
      studyPeriodId: form.value.studyPeriodId
    })
    const d = data?.data
    if (d?.message) {
      feeMessage.value = d.message
    }
    feeLines.value = d?.lines || []
    feeTotal.value = Number(d?.total || 0)
  } catch {
    feeMessage.value = 'Could not load fee preview.'
  } finally {
    feeLoading.value = false
  }
}

watch(
  () => [form.value.classId, form.value.academicYearId, form.value.studyPeriodId],
  () => {
    refreshFeePreview()
  }
)

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    form.value = {
      academicYearId: '',
      studyPeriodId: '',
      classId: '',
      streamId: '',
      forceCapacity: false,
      amountPaid: '',
      paymentMethod: 'cash',
      paymentReference: ''
    }
    feeLines.value = []
    feeTotal.value = 0
    feeMessage.value = ''
    await loadContext()
    await refreshFeePreview()
  }
)

async function submit() {
  if (!canSubmit.value || !props.applicant?.id) return
  submitting.value = true
  try {
    const amt = form.value.amountPaid === '' ? 0 : Number(form.value.amountPaid)
    const res = await admissionsApi.enroll({
      applicantId: Number(props.applicant.id),
      academicYearId: Number(form.value.academicYearId),
      studyPeriodId: Number(form.value.studyPeriodId),
      classId: Number(form.value.classId),
      streamId: form.value.streamId ? Number(form.value.streamId) : undefined,
      forceCapacity: form.value.forceCapacity,
      amountPaid: amt > 0 ? amt : undefined,
      paymentMethod: form.value.paymentMethod,
      paymentReference: form.value.paymentReference || undefined
    })
    const pay = res.data?.data?.payment
    if (pay && pay.ok === false && pay.message) {
      successToast('Enrolled', pay.message)
    } else {
      successToast('Enrolled', 'Student placed and invoice updated.')
    }
    emit('update:modelValue', false)
    emit('success')
  } catch (e) {
    errorToast('Enrollment', e?.response?.data?.error || e?.message || 'Failed')
  } finally {
    submitting.value = false
  }
}
</script>
