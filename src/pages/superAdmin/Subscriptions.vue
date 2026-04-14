<template>
  <div class="page-shell">
    <PageHeader title="Subscriptions" description="Subscription plans are loaded and saved only via the PHP API (no local demo data).">
      <template #actions>
        <Button variant="secondary" :disabled="!hasPlansApi || store.loading" @click="refreshPlans">Refresh plans</Button>
        <Button :disabled="!hasPlansApi || store.loading" @click="openPlanEditor(null)">Create Plan</Button>
      </template>
    </PageHeader>

    <div
      v-if="!hasPlansApi"
      class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
    >
      Set <code class="rounded bg-amber-100 px-1.5 py-0.5 text-xs">VITE_APP_BASE_URL</code> in
      <code class="rounded bg-amber-100 px-1.5 py-0.5 text-xs">.env</code>
      to your API root (folder that contains <code class="text-xs">super-admin/subscription-plans.php</code>), then restart the dev server.
    </div>
    <div
      v-else-if="store.plansLoadError"
      class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.plansLoadError }}
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div>
        <DataTable
          title="Plans"
          :columns="planCols"
          :data="planRows"
          row-key="id"
          searchable
          sortable
          :pagination="true"
          :page-size="6"
        >
          <template #cell-priceMonthly="{ value }">
            {{ formatCurrency(value) }}
          </template>

          <template #actions="{ row }">
            <div class="flex justify-end gap-2">
              <Button variant="ghost" class="!px-2 !py-1 text-xs" @click="openPlanEditor(row)">
                Edit
              </Button>
              <Button
                variant="ghost"
                class="!px-2 !py-1 text-xs !text-rose-600 hover:!bg-rose-50"
                @click="confirmDeletePlan(row)"
              >
                Delete
              </Button>
            </div>
          </template>
        </DataTable>
      </div>

      <div>
        <DataTable
          title="Plan assignments"
          :columns="assignCols"
          :data="assignRows"
          row-key="id"
          searchable
          sortable
          :pagination="true"
          :page-size="6"
        >
          <template #cell-status="{ value }">
            <Badge :tone="statusTone(value)">{{ value }}</Badge>
          </template>
          <template #actions="{ row }">
            <div class="flex justify-end gap-2">
              <Button variant="secondary" class="!px-2 !py-1 text-xs" @click="openAssign(row)">
                Assign
              </Button>
            </div>
          </template>
        </DataTable>
      </div>
    </div>

    <Modal v-model="planModalOpen" :title="planModalTitle">
      <div class="space-y-4">
        <FormInput v-model="planForm.name" label="Plan name" placeholder="e.g., Pro" required />
        <FormInput
          v-model="planForm.priceMonthly"
          label="Price monthly (UGX)"
          placeholder="e.g., 4999"
          required
        />
        <FormInput
          v-model="planForm.featuresText"
          label="Features (comma separated)"
          placeholder="Feature A, Feature B, Feature C"
          hint="Stored as a list on the server."
        />
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="planModalOpen = false">Cancel</Button>
          <Button :disabled="!hasPlansApi || savingPlan" @click="savePlan">{{ savingPlan ? 'Saving…' : 'Save' }}</Button>
        </div>
      </template>
    </Modal>

    <Modal v-model="assignModalOpen" title="Assign plan">
      <p class="text-sm text-slate-600">
        Assign a plan to <span class="font-medium text-slate-900">{{ assignTarget?.name }}</span>.
      </p>
      <div class="mt-4 space-y-4">
        <SelectInput
          v-model="assignPlanId"
          label="Plan"
          :options="planOptions"
          placeholder="Select plan"
        />
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="assignModalOpen = false">Cancel</Button>
          <Button @click="applyAssign">Apply</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { formatCurrency } from '@/utils/formatters'
import { useSuperAdminStore } from '@/stores/superAdmin'
import useNotificationStore from '@/stores/notificationStore'
import { baseURL } from '@/config/api.js'

const store = useSuperAdminStore()
const { errorToast, successToast } = useNotificationStore()

const hasPlansApi = computed(() => Boolean(String(baseURL || '').trim()))

async function refreshPlans() {
  await store.fetchPlans()
  if (store.plansLoadError) {
    errorToast('Plans', store.plansLoadError)
  }
}

const planCols = [
  { key: 'name', label: 'Plan', sortable: true },
  { key: 'priceMonthly', label: 'Price / month', sortable: true },
  { key: 'featuresCount', label: 'Features', sortable: true }
]

const planRows = computed(() =>
  store.plans.map((p) => ({
    ...p,
    featuresCount: Array.isArray(p.features) ? p.features.length : 0
  }))
)

const assignCols = [
  { key: 'name', label: 'School', sortable: true },
  { key: 'contactEmail', label: 'Contact Email', sortable: true },
  { key: 'plan', label: 'Current Plan', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'studentsCount', label: 'Students', sortable: true }
]

const assignRows = computed(() =>
  store.schools.map((s) => ({
    ...s,
    plan: store.planName(s.planId)
  }))
)

function statusTone(s) {
  if (s === 'Active') return 'success'
  if (s === 'Suspended') return 'danger'
  if (s === 'Trial') return 'warning'
  return 'neutral'
}

const planModalOpen = ref(false)
const planForm = reactive({
  id: null,
  name: '',
  priceMonthly: '',
  featuresText: ''
})

const planModalTitle = computed(() => (planForm.id ? 'Edit plan' : 'Create plan'))

function openPlanEditor(plan) {
  if (plan) {
    planForm.id = plan.id
    planForm.name = plan.name
    planForm.priceMonthly = String(plan.priceMonthly ?? '')
    planForm.featuresText = Array.isArray(plan.features) ? plan.features.join(', ') : ''
  } else {
    planForm.id = null
    planForm.name = ''
    planForm.priceMonthly = ''
    planForm.featuresText = ''
  }
  planModalOpen.value = true
}

async function confirmDeletePlan(row) {
  const planKey = row?.id != null && String(row.id).trim() !== '' ? String(row.id).trim() : null
  if (!planKey) return
  const ok = window.confirm(`Delete plan "${row.name}"? This cannot be undone.`)
  if (!ok) return
  try {
    await store.deletePlan(planKey)
    successToast('Plan deleted', 'The subscription plan was removed.')
  } catch (e) {
    const msg =
      e?.response?.data?.error ||
      e?.response?.data?.message ||
      e?.message ||
      'Could not delete plan.'
    errorToast('Delete failed', String(msg))
  }
}

const savingPlan = ref(false)

async function savePlan() {
  const name = String(planForm.name || '').trim()
  const price = Number(planForm.priceMonthly)
  const features = String(planForm.featuresText || '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean)

  if (!name || Number.isNaN(price)) {
    errorToast('Validation Error', 'Please provide a valid plan name and price.')
    return
  }

  savingPlan.value = true
  try {
    await store.upsertPlan({
      id: planForm.id || undefined,
      name,
      priceMonthly: price,
      features
    })
    planModalOpen.value = false
    successToast('Plan Saved', 'Subscription plan saved successfully.')
  } catch (e) {
    const msg =
      e?.response?.data?.error ||
      e?.response?.data?.message ||
      e?.message ||
      'Could not save plan.'
    errorToast('Save failed', String(msg))
  } finally {
    savingPlan.value = false
  }
}

const assignModalOpen = ref(false)
const assignTarget = ref(null)
const assignPlanId = ref('')

const planOptions = computed(() => store.plans.map((p) => ({ value: p.id, label: p.name })))

function openAssign(row) {
  assignTarget.value = row
  assignPlanId.value = row.planId
  assignModalOpen.value = true
}

function applyAssign() {
  if (!assignTarget.value) return
  if (!assignPlanId.value) return
  store.assignPlan(assignTarget.value.id, assignPlanId.value)
  assignModalOpen.value = false
  successToast('Plan Assigned', 'School subscription assignment updated.')
}

onMounted(() => store.fetchAll())
</script>

