<template>
  <div class="page-shell">
    <PageHeader title="Schools" description="Manage school onboarding requests, approval/rejection, and tenant lifecycle.">
      <template #actions>
        <Button variant="secondary" @click="refresh">Refresh</Button>
      </template>
    </PageHeader>

    <DataTable
      title="All schools"
      :columns="cols"
      :data="rows"
      row-key="id"
      searchable
      sortable
      :pagination="true"
      :page-size="8"
    >
      <template #cell-status="{ value }">
        <Badge :tone="statusTone(value)">{{ value }}</Badge>
      </template>

      <template #cell-plan="{ value }">
        <span class="font-medium text-slate-900">{{ value }}</span>
      </template>

      <template #cell-curriculum="{ value }">
        <span class="text-slate-700">{{ value }}</span>
      </template>

      <template #actions="{ row }">
        <div class="flex flex-wrap items-center justify-end gap-2">
          <Button variant="ghost" class="!px-2 !py-1 text-xs" @click="openView(row)">
            View
          </Button>
          <Button
            variant="secondary"
            class="!px-2 !py-1 text-xs"
            :disabled="row.status !== 'Pending'"
            @click="openDecision('Approved', row)"
          >
            Approve
          </Button>
          <Button
            variant="secondary"
            class="!px-2 !py-1 text-xs"
            :disabled="row.status !== 'Pending'"
            @click="openDecision('Rejected', row)"
          >
            Reject
          </Button>
          <Button variant="secondary" class="!px-2 !py-1 text-xs" @click="openUpgrade(row)">
            Edit Plan
          </Button>
          <Button variant="ghost" class="!px-2 !py-1 text-xs !text-rose-600 hover:!bg-rose-50" @click="deleteSchool(row)">
            Delete
          </Button>
        </div>
      </template>
    </DataTable>

    <Modal v-model="viewOpen" title="School details">
      <div class="space-y-3 text-sm text-slate-700">
        <div><span class="font-medium text-slate-900">Name:</span> {{ targetSchool?.name }}</div>
        <div>
          <span class="font-medium text-slate-900">Contact email:</span> {{ targetSchool?.contactEmail }}
        </div>
        <div>
          <span class="font-medium text-slate-900">Plan:</span> {{ planName(targetSchool?.planId) }}
        </div>
        <div>
          <span class="font-medium text-slate-900">Curriculum:</span> {{ targetSchool?.curriculum || 'Local Based Curriculum' }}
        </div>
        <div>
          <span class="font-medium text-slate-900">Status:</span>
          <Badge class="ml-2" :tone="statusTone(targetSchool?.status)">{{ targetSchool?.status }}</Badge>
        </div>
        <div>
          <span class="font-medium text-slate-900">Students count:</span> {{ targetSchool?.studentsCount }}
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="viewOpen = false">Close</Button>
        </div>
      </template>
    </Modal>

    <Modal v-model="decisionOpen" :title="decisionTitle">
      <p class="text-sm text-slate-600">
        This will set <span class="font-medium text-slate-900">{{ targetSchool?.name }}</span> status to
        <span class="font-medium text-slate-900">{{ decisionStatus }}</span>.
      </p>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="decisionOpen = false">Cancel</Button>
          <Button @click="applyDecision">Confirm</Button>
        </div>
      </template>
    </Modal>

    <Modal v-model="upgradeOpen" title="Upgrade Plan">
      <p class="text-sm text-slate-600">
        Choose a plan for <span class="font-medium text-slate-900">{{ targetSchool?.name }}</span>.
      </p>
      <div class="mt-4 space-y-4">
        <SelectInput
          v-model="selectedPlanId"
          label="Plan"
          :options="planOptions"
          placeholder="Select plan"
        />
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" @click="upgradeOpen = false">Cancel</Button>
          <Button @click="applyUpgrade">Apply</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { useSuperAdminStore } from '@/stores/superAdmin'
import useNotificationStore from '@/stores/notificationStore'

const store = useSuperAdminStore()
const { successToast, errorToast } = useNotificationStore()

const loading = computed(() => store.loading)

const cols = [
  { key: 'name', label: 'School Name', sortable: true },
  { key: 'contactEmail', label: 'Contact Email', sortable: true },
  { key: 'plan', label: 'Plan', sortable: true },
  { key: 'curriculum', label: 'Curriculum', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'studentsCount', label: 'Students Count', sortable: true }
]

function statusTone(s) {
  if (s === 'Approved' || s === 'Active') return 'success'
  if (s === 'Rejected' || s === 'Suspended') return 'danger'
  if (s === 'Pending' || s === 'Trial') return 'warning'
  return 'neutral'
}

const rows = computed(() =>
  store.schools.map((s) => ({
    ...s,
    plan: store.planName(s.planId)
  }))
)

function planName(planId) {
  return store.planName(planId)
}

function refresh() {
  return store.fetchAll()
}

onMounted(refresh)

const viewOpen = ref(false)
const decisionOpen = ref(false)
const upgradeOpen = ref(false)

const targetSchool = ref(null)
const decisionStatus = ref('Suspended')
const selectedPlanId = ref('')

const decisionTitle = computed(() => {
  if (!targetSchool.value) return 'Confirm'
  if (decisionStatus.value === 'Approved') return 'Approve school'
  return 'Reject school'
})

const planOptions = computed(() => store.plans.map((p) => ({ value: p.id, label: p.name })))

function openView(row) {
  targetSchool.value = row
  viewOpen.value = true
}

function openDecision(status, row) {
  targetSchool.value = row
  decisionStatus.value = status
  decisionOpen.value = true
}

function openUpgrade(row) {
  targetSchool.value = row
  selectedPlanId.value = row.planId
  upgradeOpen.value = true
}

async function applyDecision() {
  if (!targetSchool.value) return
  try {
    if (decisionStatus.value === 'Approved') {
      await store.approveSchool(targetSchool.value.id)
      successToast('School approved', 'School account has been approved.')
    } else {
      await store.rejectSchool(targetSchool.value.id)
      successToast('School rejected', 'School account has been rejected.')
    }
    decisionOpen.value = false
  } catch (e) {
    errorToast('Action failed', e?.response?.data?.error || e?.message || 'Could not update school status.')
  }
}

async function applyUpgrade() {
  if (!targetSchool.value) return
  if (!selectedPlanId.value) return
  try {
    await store.updateSchool({ id: targetSchool.value.id, planId: selectedPlanId.value })
    upgradeOpen.value = false
    successToast('Plan updated', 'School plan has been updated.')
  } catch (e) {
    errorToast('Update failed', e?.response?.data?.error || e?.message || 'Could not update school.')
  }
}

async function deleteSchool(row) {
  if (!row?.id) return
  const ok = window.confirm(`Delete ${row.name}? This removes onboarding and login account.`)
  if (!ok) return
  try {
    await store.deleteSchoolById(row.id)
    successToast('School deleted', 'School onboarding and account were removed.')
  } catch (e) {
    errorToast('Delete failed', e?.response?.data?.error || e?.message || 'Could not delete school.')
  }
}
</script>

