<template>
  <div class="page-shell max-w-[1200px]">
    <PageHeader
      title="Fee structures"
      description="Define reusable templates, then publish structures per class, year, and term. Invoices copy line items from the matching structure."
    />

    <div class="mb-4 flex gap-2 border-b border-sc-line pb-2">
      <button
        type="button"
        class="rounded-lg px-4 py-2 text-sm font-semibold"
        :class="tab === 'structures' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'"
        @click="tab = 'structures'"
      >
        Fee structures
      </button>
      <button
        type="button"
        class="rounded-lg px-4 py-2 text-sm font-semibold"
        :class="tab === 'templates' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'"
        @click="tab = 'templates'"
      >
        Templates
      </button>
    </div>

    <div v-if="loading" class="py-12 text-center text-slate-500">Loading…</div>

    <template v-else-if="tab === 'templates'">
      <div class="mb-4 flex justify-end">
        <Button type="button" @click="openTplModal()">New template</Button>
      </div>
      <div class="overflow-hidden rounded-2xl border border-sc-line bg-white shadow-soft">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-2xs uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left">Name</th>
              <th class="px-4 py-3 text-left">Description</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in templates" :key="t.id" class="border-t border-sc-line">
              <td class="px-4 py-3 font-medium">{{ t.name }}</td>
              <td class="px-4 py-3 text-slate-600">{{ t.description || '—' }}</td>
              <td class="px-4 py-3 text-right">
                <Button variant="ghost" type="button" @click="openTplModal(t.id)">Edit</Button>
                <Button variant="ghost" type="button" class="text-rose-700" @click="removeTpl(t.id)">Delete</Button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="!templates.length" class="py-10 text-center text-sm text-slate-400">No templates yet.</p>
      </div>
    </template>

    <template v-else>
      <div class="mb-4 flex flex-wrap justify-end gap-2">
        <Button type="button" variant="secondary" @click="load">Refresh</Button>
        <Button type="button" @click="openFsModal()">New fee structure</Button>
      </div>
      <div class="overflow-hidden rounded-2xl border border-sc-line bg-white shadow-soft">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-2xs uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left">Name</th>
              <th class="px-4 py-3 text-left">Classes</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in structures" :key="s.id" class="border-t border-sc-line">
              <td class="px-4 py-3 font-medium">{{ s.name }}</td>
              <td class="max-w-md truncate px-4 py-3 text-slate-600">{{ s.class_names || '—' }}</td>
              <td class="px-4 py-3 text-right">
                <Button variant="ghost" type="button" @click="openFsModal(s.id)">Edit</Button>
                <Button variant="ghost" type="button" class="text-rose-700" @click="removeFs(s.id)">Delete</Button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="!structures.length" class="py-10 text-center text-sm text-slate-400">No structures — create one for each class/year/period.</p>
      </div>
    </template>

    <Modal v-model="tplModal" :title="tplForm.id ? 'Edit template' : 'New template'">
      <FormInput v-model="tplForm.name" label="Name" required />
      <FormInput v-model="tplForm.description" label="Description" />
      <p class="mb-2 text-xs font-semibold text-slate-600">Line items</p>
      <div class="space-y-2">
        <div v-for="(it, idx) in tplForm.items" :key="idx" class="flex flex-wrap gap-2 rounded-xl border border-sc-line p-2">
          <FormInput v-model="it.label" label="Label" class="min-w-[140px] flex-1" />
          <FormInput v-model="it.amount" label="Amount" type="number" step="0.01" class="w-28" />
          <SelectInput v-model="it.itemType" label="Type" :options="itemTypeOpts" class="w-36" />
          <button type="button" class="self-end text-rose-600" @click="tplForm.items.splice(idx, 1)">Remove</button>
        </div>
        <Button variant="secondary" type="button" @click="tplForm.items.push({ label: '', amount: '', itemType: 'mandatory' })">
          Add line
        </Button>
      </div>
      <template #footer>
        <Button variant="secondary" type="button" @click="tplModal = false">Cancel</Button>
        <Button type="button" :disabled="saving" @click="saveTpl">{{ saving ? 'Saving…' : 'Save' }}</Button>
      </template>
    </Modal>

    <Modal v-model="fsModal" :title="fsForm.id ? 'Edit fee structure' : 'New fee structure'">
      <FormInput v-model="fsForm.name" label="Name" required />
      <SelectInput v-model="fsForm.academicYearId" label="Academic year" :options="yearOpts" />
      <SelectInput v-model="fsForm.studyPeriodId" label="Study period" :options="periodOpts" />
      <SelectInput v-model="fsForm.templateId" label="Copy from template (optional)" :options="tplOpts" />
      <p class="mb-2 text-xs font-semibold text-slate-600">Applies to classes</p>
      <div class="max-h-40 space-y-1 overflow-y-auto rounded-xl border border-sc-line p-2">
        <label v-for="c in classes" :key="c.id" class="flex items-center gap-2 text-sm">
          <input v-model="fsForm.classIds" type="checkbox" :value="String(c.id)" class="rounded border-slate-300 text-brand-600" />
          {{ c.level_name }} {{ c.name }}
        </label>
      </div>
      <p class="mb-2 mt-3 text-xs font-semibold text-slate-600">Fee items</p>
      <div class="space-y-2">
        <div v-for="(it, idx) in fsForm.items" :key="idx" class="flex flex-wrap gap-2 rounded-xl border border-sc-line p-2">
          <FormInput v-model="it.label" label="Label" class="min-w-[140px] flex-1" />
          <FormInput v-model="it.amount" label="Amount" type="number" step="0.01" class="w-28" />
          <SelectInput v-model="it.itemType" label="Type" :options="itemTypeOpts" class="w-36" />
          <button type="button" class="self-end text-rose-600" @click="fsForm.items.splice(idx, 1)">Remove</button>
        </div>
        <Button variant="secondary" type="button" @click="fsForm.items.push({ label: '', amount: '', itemType: 'mandatory' })">
          Add line
        </Button>
      </div>
      <template #footer>
        <Button variant="secondary" type="button" @click="fsModal = false">Cancel</Button>
        <Button type="button" :disabled="saving" @click="saveFs">{{ saving ? 'Saving…' : 'Save' }}</Button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { financeApi } from '@/services/financeApi'
import useNotificationStore from '@/stores/notificationStore'

const { successToast, errorToast } = useNotificationStore()

const loading = ref(true)
const saving = ref(false)
const tab = ref('structures')
const templates = ref([])
const structures = ref([])
const years = ref([])
const periods = ref([])
const classes = ref([])

const tplModal = ref(false)
const fsModal = ref(false)
const tplForm = reactive({ id: null, name: '', description: '', items: [{ label: '', amount: '', itemType: 'mandatory' }] })
const fsForm = reactive({
  id: null,
  name: '',
  academicYearId: '',
  studyPeriodId: '',
  templateId: '',
  classIds: [],
  items: [{ label: '', amount: '', itemType: 'mandatory' }]
})

const itemTypeOpts = [
  { value: 'mandatory', label: 'Mandatory' },
  { value: 'optional', label: 'Optional' }
]

const yearOpts = computed(() => [{ value: '', label: 'Select year' }, ...years.value.map((y) => ({ value: String(y.id), label: y.name }))])
const periodOpts = computed(() => {
  if (!fsForm.academicYearId) return [{ value: '', label: 'Select period' }]
  const y = Number(fsForm.academicYearId)
  return [
    { value: '', label: 'Select period' },
    ...periods.value.filter((p) => Number(p.academic_year_id) === y).map((p) => ({ value: String(p.id), label: p.name }))
  ]
})
const tplOpts = computed(() => [
  { value: '', label: 'None' },
  ...templates.value.map((t) => ({ value: String(t.id), label: t.name }))
])

async function load() {
  loading.value = true
  try {
    const ctx = await financeApi.context()
    years.value = ctx.data?.data?.academicYears || []
    periods.value = ctx.data?.data?.studyPeriods || []
    classes.value = ctx.data?.data?.classes || []
    const t = await financeApi.templates.list()
    templates.value = t.data?.data || []
    const s = await financeApi.structures.list()
    structures.value = s.data?.data || []
  } catch (e) {
    errorToast('Load failed', e?.response?.data?.error || e?.message)
  } finally {
    loading.value = false
  }
}

function openTplModal(id = null) {
  if (id) {
    financeApi.templates
      .get(id)
      .then(({ data }) => {
        const d = data?.data
        if (!d) return
        tplForm.id = d.id
        tplForm.name = d.name
        tplForm.description = d.description || ''
        tplForm.items = (d.items || []).length
          ? d.items.map((x) => ({
              label: x.label,
              amount: String(x.amount),
              itemType: x.item_type || 'mandatory'
            }))
          : [{ label: '', amount: '', itemType: 'mandatory' }]
        tplModal.value = true
      })
      .catch((e) => errorToast('Load', e?.response?.data?.error || e?.message))
  } else {
    tplForm.id = null
    tplForm.name = ''
    tplForm.description = ''
    tplForm.items = [{ label: '', amount: '', itemType: 'mandatory' }]
    tplModal.value = true
  }
}

async function saveTpl() {
  saving.value = true
  try {
    const items = tplForm.items
      .filter((x) => x.label.trim())
      .map((x) => ({
        label: x.label.trim(),
        amount: Number(x.amount) || 0,
        itemType: x.itemType
      }))
    const body = {
      name: tplForm.name.trim(),
      description: tplForm.description || '',
      items
    }
    if (tplForm.id) {
      body.id = tplForm.id
      await financeApi.templates.update(body)
    } else {
      await financeApi.templates.create(body)
    }
    successToast('Saved', '')
    tplModal.value = false
    await load()
  } catch (e) {
    errorToast('Save failed', e?.response?.data?.error || e?.message)
  } finally {
    saving.value = false
  }
}

async function removeTpl(id) {
  if (!confirm('Delete this template?')) return
  try {
    await financeApi.templates.delete(id)
    successToast('Deleted', '')
    await load()
  } catch (e) {
    errorToast('Delete', e?.response?.data?.error || e?.message)
  }
}

function openFsModal(id = null) {
  if (id) {
    financeApi.structures
      .get(id)
      .then(({ data }) => {
        const d = data?.data
        if (!d) return
        fsForm.id = d.id
        fsForm.name = d.name
        fsForm.academicYearId = String(d.academic_year_id)
        fsForm.studyPeriodId = String(d.study_period_id)
        fsForm.templateId = d.template_id ? String(d.template_id) : ''
        fsForm.classIds = (d.classIds || []).map(String)
        fsForm.items = (d.items || []).length
          ? d.items.map((x) => ({
              label: x.label,
              amount: String(x.amount),
              itemType: x.item_type || 'mandatory'
            }))
          : [{ label: '', amount: '', itemType: 'mandatory' }]
        fsModal.value = true
      })
      .catch((e) => errorToast('Load', e?.response?.data?.error || e?.message))
  } else {
    fsForm.id = null
    fsForm.name = ''
    fsForm.academicYearId = ''
    fsForm.studyPeriodId = ''
    fsForm.templateId = ''
    fsForm.classIds = []
    fsForm.items = [{ label: '', amount: '', itemType: 'mandatory' }]
    fsModal.value = true
  }
}

async function saveFs() {
  if (!fsForm.classIds.length) {
    errorToast('Validation', 'Select at least one class.')
    return
  }
  saving.value = true
  try {
    const items = fsForm.items
      .filter((x) => x.label.trim())
      .map((x) => ({
        label: x.label.trim(),
        amount: Number(x.amount) || 0,
        itemType: x.itemType
      }))
    const body = {
      name: fsForm.name.trim(),
      academicYearId: Number(fsForm.academicYearId),
      studyPeriodId: Number(fsForm.studyPeriodId),
      templateId: fsForm.templateId ? Number(fsForm.templateId) : null,
      classIds: fsForm.classIds.map(Number),
      items
    }
    if (fsForm.id) {
      body.id = fsForm.id
      await financeApi.structures.update(body)
    } else {
      await financeApi.structures.create(body)
    }
    successToast('Saved', '')
    fsModal.value = false
    await load()
  } catch (e) {
    errorToast('Save failed', e?.response?.data?.error || e?.message)
  } finally {
    saving.value = false
  }
}

async function removeFs(id) {
  if (!confirm('Delete this fee structure?')) return
  try {
    await financeApi.structures.delete(id)
    successToast('Deleted', '')
    await load()
  } catch (e) {
    errorToast('Delete', e?.response?.data?.error || e?.message)
  }
}

onMounted(load)
</script>
