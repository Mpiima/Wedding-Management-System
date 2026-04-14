<template>
  <div class="mx-auto max-w-6xl pb-12">
    <div v-if="wizardDone" class="card-surface text-center">
      <div class="text-4xl" aria-hidden="true">🎉</div>
      <h1 class="mt-2 text-xl font-semibold text-slate-900">Setup complete</h1>
      <p class="mt-2 text-sm text-slate-600">You can revisit this wizard anytime to edit academic structure.</p>
      <div class="mt-6 flex flex-wrap justify-center gap-3">
        <Button type="button" @click="wizardDone = false">Continue editing</Button>
        <Button type="button" variant="secondary" @click="$router.push('/dashboard')">Go to dashboard</Button>
      </div>
    </div>

    <template v-else>
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">School ERP setup</h1>
        <p class="mt-1 text-sm text-slate-600">Guided setup for years, terms, levels, classes, streams, and subjects.</p>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200">
          <div
            class="h-full rounded-full bg-brand-600 transition-all duration-500 ease-out"
            :style="{ width: progressPct + '%' }"
          />
        </div>
        <p class="mt-1 text-right text-xs font-medium text-slate-500">{{ progressPct }}%</p>
      </div>

      <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
        <aside class="lg:w-56 lg:shrink-0">
          <nav class="sticky top-4 flex flex-row gap-1 overflow-x-auto pb-2 lg:flex-col lg:overflow-visible lg:pb-0">
            <button
              v-for="(s, i) in steps"
              :key="s.id"
              type="button"
              class="flex min-w-[140px] items-center gap-2 rounded-xl border px-3 py-2.5 text-left text-sm font-medium transition-colors lg:min-w-0"
              :class="
                currentStep === i + 1
                  ? 'border-brand-300 bg-brand-50 text-brand-900'
                  : 'border-transparent bg-white text-slate-600 hover:border-sc-line hover:bg-slate-50'
              "
              @click="goToStep(i + 1)"
            >
              <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                :class="currentStep === i + 1 ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-700'"
              >{{ i + 1 }}</span>
              <span class="leading-tight">{{ s.label }}</span>
            </button>
          </nav>
        </aside>

        <div class="card-surface min-w-0 flex-1">
          <h2 class="mb-4 text-lg font-semibold text-slate-900">{{ steps[currentStep - 1].label }}</h2>

          <ErpStepAcademicYear
            v-if="currentStep === 1"
            :years="years"
            @save-year="onSaveYear"
            @clone-year="onCloneYear"
            @set-active="onSetActiveYear"
            @delete-year="onDeleteYear"
          />
          <ErpStepStudyPeriods
            v-else-if="currentStep === 2"
            :years="years"
            :periods="periods"
            :academic-year-id="periodYearId"
            @change-year="onPeriodYearChange"
            @save-period="onSavePeriod"
            @set-active-period="onSetActivePeriod"
            @delete-period="onDeletePeriod"
            @reorder="onReorderPeriods"
          />
          <ErpStepLevels v-else-if="currentStep === 3" :levels="levels" @add="onAddLevel" @remove="onRemoveLevel" />
          <ErpStepClasses
            v-else-if="currentStep === 4"
            :levels="levels"
            :classes="classes"
            @bulk="onBulkClasses"
            @add-class="onAddClass"
            @remove-class="onRemoveClass"
            @rename-class="openRenameClass"
          />
          <ErpStepStreams
            v-else-if="currentStep === 5"
            :streams="streams"
            :classes="classes"
            @add-stream="onAddStream"
            @remove-stream="onRemoveStream"
            @link-streams="onLinkStreams"
          />
          <ErpStepSubjects
            v-else-if="currentStep === 6"
            :subjects="subjects"
            :classes="classes"
            @add-subject="onAddSubject"
            @remove-subject="onRemoveSubject"
            @apply-template="onApplyTemplate"
            @assign="onAssignSubjects"
          />

          <div class="mt-8 flex flex-wrap justify-between gap-3 border-t border-sc-line pt-6">
            <Button type="button" variant="secondary" :disabled="currentStep <= 1" @click="goBack">Back</Button>
            <div class="flex gap-2">
              <Button v-if="currentStep < 6" type="button" @click="goNext">Next</Button>
              <Button v-else type="button" @click="finishWizard">Finish setup</Button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <Modal v-model="renameOpen" title="Rename class">
      <FormInput v-model="renameName" label="Class name" />
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" type="button" @click="renameOpen = false">Cancel</Button>
          <Button type="button" @click="saveRenameClass">Save</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import ErpStepAcademicYear from '@/components/erp-setup/steps/ErpStepAcademicYear.vue'
import ErpStepStudyPeriods from '@/components/erp-setup/steps/ErpStepStudyPeriods.vue'
import ErpStepLevels from '@/components/erp-setup/steps/ErpStepLevels.vue'
import ErpStepClasses from '@/components/erp-setup/steps/ErpStepClasses.vue'
import ErpStepStreams from '@/components/erp-setup/steps/ErpStepStreams.vue'
import ErpStepSubjects from '@/components/erp-setup/steps/ErpStepSubjects.vue'
import { erpApi } from '@/services/erpSetupApi'
import { PRIMARY_SUBJECTS, SECONDARY_SUBJECTS } from '@/data/erpSubjectTemplates'
import useNotificationStore from '@/stores/notificationStore'

const { successToast, errorToast } = useNotificationStore()

const steps = [
  { id: 'year', label: 'Academic year' },
  { id: 'periods', label: 'Study periods' },
  { id: 'levels', label: 'Levels' },
  { id: 'classes', label: 'Classes' },
  { id: 'streams', label: 'Streams' },
  { id: 'subjects', label: 'Subjects' }
]

const currentStep = ref(1)
const wizardDone = ref(false)

const years = ref([])
const periods = ref([])
const periodYearId = ref(0)
const levels = ref([])
const classes = ref([])
const streams = ref([])
const subjects = ref([])

const renameOpen = ref(false)
const renameName = ref('')
const renameTarget = ref(null)

const progressPct = computed(() => Math.round((currentStep.value / 6) * 100))

async function loadYears() {
  const { data } = await erpApi.academicYears()
  years.value = data?.data || []
  if (!periodYearId.value && years.value.length) {
    const active = years.value.find((y) => Number(y.is_active) === 1)
    periodYearId.value = active ? active.id : years.value[0].id
  }
}

async function loadPeriods() {
  if (!periodYearId.value) {
    periods.value = []
    return
  }
  const { data } = await erpApi.studyPeriods(periodYearId.value)
  periods.value = data?.data || []
}

async function loadLevels() {
  const { data } = await erpApi.levels()
  levels.value = data?.data || []
}

async function loadClasses() {
  const { data } = await erpApi.classes()
  classes.value = data?.data || []
}

async function loadStreams() {
  const { data } = await erpApi.streams()
  streams.value = data?.data || []
}

async function loadSubjects() {
  const { data } = await erpApi.subjects()
  subjects.value = data?.data || []
}

async function loadWizard() {
  try {
    const { data } = await erpApi.wizardState()
    const d = data?.data
    if (d?.completed) {
      wizardDone.value = true
    }
    if (d?.currentStep >= 1 && d?.currentStep <= 6) {
      currentStep.value = d.currentStep
    }
  } catch {
    /* ignore */
  }
}

async function persistWizardStep() {
  try {
    await erpApi.saveWizardState({ currentStep: currentStep.value })
  } catch {
    /* ignore */
  }
}

onMounted(async () => {
  try {
    await loadWizard()
    await loadYears()
    await loadPeriods()
    await loadLevels()
    await loadClasses()
    await loadStreams()
    await loadSubjects()
  } catch (e) {
    errorToast('ERP setup', e?.response?.data?.error || e?.message || 'Could not load data.')
  }
})

function goToStep(n) {
  if (n >= 1 && n <= 6) {
    currentStep.value = n
    persistWizardStep()
  }
}

async function goNext() {
  if (currentStep.value < 6) {
    currentStep.value++
    await persistWizardStep()
  }
}

async function goBack() {
  if (currentStep.value > 1) {
    currentStep.value--
    await persistWizardStep()
  }
}

async function finishWizard() {
  try {
    await erpApi.saveWizardState({ currentStep: 6, completed: true })
    wizardDone.value = true
    successToast('Done', 'Academic ERP setup saved.')
  } catch (e) {
    errorToast('Finish', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onSaveYear(payload) {
  try {
    await erpApi.createAcademicYear(payload)
    successToast('Saved', 'Academic year added.')
    await loadYears()
    await loadPeriods()
  } catch (e) {
    errorToast('Year', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onCloneYear(payload) {
  try {
    await erpApi.cloneYear(payload)
    successToast('Cloned', 'New year created with terms.')
    await loadYears()
  } catch (e) {
    errorToast('Clone', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onSetActiveYear(id) {
  try {
    await erpApi.setActiveYear(id)
    successToast('Active year', 'Updated.')
    await loadYears()
  } catch (e) {
    errorToast('Active', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onDeleteYear(id) {
  if (!window.confirm('Delete this academic year and its terms?')) return
  try {
    await erpApi.deleteAcademicYear(id)
    await loadYears()
    await loadPeriods()
  } catch (e) {
    errorToast('Delete', e?.response?.data?.error || e?.message || 'Failed')
  }
}

function onPeriodYearChange(id) {
  periodYearId.value = id
  loadPeriods()
}

async function onSavePeriod(p) {
  try {
    if (p.id) {
      await erpApi.updateStudyPeriod({
        id: p.id,
        name: p.name,
        startDate: p.startDate || null,
        endDate: p.endDate || null,
        isActive: p.isActive
      })
    } else {
      await erpApi.createStudyPeriod({
        academicYearId: p.academicYearId,
        name: p.name,
        startDate: p.startDate || null,
        endDate: p.endDate || null,
        isActive: p.isActive
      })
    }
    await loadPeriods()
    successToast('Period', 'Saved.')
  } catch (e) {
    errorToast('Period', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onSetActivePeriod(id) {
  try {
    await erpApi.setActivePeriod(id)
    await loadPeriods()
  } catch (e) {
    errorToast('Period', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onDeletePeriod(id) {
  if (!window.confirm('Delete this period?')) return
  try {
    await erpApi.deleteStudyPeriod(id)
    await loadPeriods()
  } catch (e) {
    errorToast('Delete', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onReorderPeriods(order) {
  try {
    await erpApi.reorderStudyPeriods(periodYearId.value, order)
    await loadPeriods()
  } catch (e) {
    errorToast('Reorder', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onAddLevel(name) {
  try {
    await erpApi.createLevel(name)
    await loadLevels()
  } catch (e) {
    errorToast('Level', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onRemoveLevel(id) {
  if (!window.confirm('Remove this level?')) return
  try {
    await erpApi.deleteLevel(id)
    await loadLevels()
    await loadClasses()
  } catch (e) {
    errorToast('Level', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onBulkClasses(b) {
  if (!b.levelId) {
    errorToast('Level', 'Pick a level.')
    return
  }
  try {
    await erpApi.bulkClasses(b)
    await loadClasses()
    successToast('Classes', 'Generated.')
  } catch (e) {
    errorToast('Classes', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onAddClass({ levelId, name }) {
  try {
    await erpApi.createClass({ levelId, name })
    await loadClasses()
  } catch (e) {
    errorToast('Class', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onRemoveClass(id) {
  try {
    await erpApi.deleteClass(id)
    await loadClasses()
  } catch (e) {
    errorToast('Class', e?.response?.data?.error || e?.message || 'Failed')
  }
}

function openRenameClass(c) {
  renameTarget.value = c
  renameName.value = c.name
  renameOpen.value = true
}

async function saveRenameClass() {
  if (!renameTarget.value) return
  try {
    await erpApi.updateClass({ id: renameTarget.value.id, name: renameName.value.trim() })
    renameOpen.value = false
    await loadClasses()
  } catch (e) {
    errorToast('Rename', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onAddStream(name) {
  try {
    await erpApi.createStream(name)
    await loadStreams()
  } catch (e) {
    errorToast('Stream', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onRemoveStream(id) {
  try {
    await erpApi.deleteStream(id)
    await loadStreams()
  } catch (e) {
    errorToast('Stream', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onLinkStreams(body) {
  try {
    await erpApi.linkStreams(body)
    successToast('Streams', 'Linked.')
  } catch (e) {
    errorToast('Streams', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onAddSubject(name) {
  try {
    await erpApi.createSubject(name)
    await loadSubjects()
  } catch (e) {
    errorToast('Subject', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onRemoveSubject(id) {
  try {
    await erpApi.deleteSubject(id)
    await loadSubjects()
  } catch (e) {
    errorToast('Subject', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onApplyTemplate(kind) {
  const list = kind === 'primary' ? PRIMARY_SUBJECTS : SECONDARY_SUBJECTS
  try {
    await erpApi.createSubjectsBulk(list)
    await loadSubjects()
    successToast('Template', 'Subjects added (duplicates skipped).')
  } catch (e) {
    errorToast('Template', e?.response?.data?.error || e?.message || 'Failed')
  }
}

async function onAssignSubjects({ classIds, subjectIds }) {
  try {
    await erpApi.assignSubjects(classIds, subjectIds)
    successToast('Assign', 'Subject links updated.')
  } catch (e) {
    errorToast('Assign', e?.response?.data?.error || e?.message || 'Failed')
  }
}
</script>
