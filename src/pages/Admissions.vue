<template>
  <div class="page-shell max-w-[1680px]">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <PageHeader
        title="Admissions & enrollment"
        description="Pipeline from application to enrollment — drag cards between stages or open a profile for full details."
      />
      <div class="flex flex-wrap gap-2">
        <Button type="button" @click="openNewModal">
          New application
        </Button>
        <Button type="button" variant="secondary" :disabled="loading" @click="refreshAll">
          Refresh
        </Button>
      </div>
    </div>

    <!-- Summary stats -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-7">
      <div
        v-for="s in statCards"
        :key="s.key"
        class="rounded-2xl border border-sc-line bg-white px-4 py-3 shadow-soft transition-shadow hover:shadow-card"
      >
        <div class="flex items-center gap-2">
          <span
            class="h-2 w-2 shrink-0 rounded-full"
            :class="columnMeta[s.key]?.dot || 'bg-slate-300'"
            aria-hidden="true"
          />
          <p class="text-2xs font-semibold uppercase tracking-wide text-slate-500">{{ s.label }}</p>
        </div>
        <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ stats[s.key] ?? 0 }}</p>
      </div>
    </div>

    <!-- Bulk actions -->
    <div
      class="mt-4 flex flex-col gap-3 rounded-2xl border border-sc-line bg-white/90 px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex flex-wrap items-center gap-3">
        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
          <input v-model="bulkMode" type="checkbox" class="rounded border-slate-300 text-brand-600" />
          Bulk actions
        </label>
        <div v-if="bulkMode" class="flex flex-wrap items-center gap-2">
          <span class="text-2xs font-medium uppercase tracking-wide text-slate-400">Stage</span>
          <select
            v-model="bulkStage"
            class="h-9 rounded-lg border border-sc-line bg-white px-3 text-sm font-medium text-slate-800 shadow-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
          >
            <option v-for="opt in bulkStageOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <Button type="button" variant="ghost" :disabled="!cardsByStatus(bulkStage).length" @click="selectAllInBulkColumn">
            Select all ({{ cardsByStatus(bulkStage).length }})
          </Button>
        </div>
      </div>
      <p v-if="bulkMode" class="text-xs text-slate-500">
        Checkboxes appear only in the selected column. Drag-and-drop still works for any card.
      </p>
    </div>

    <!-- Bulk bar -->
    <div
      v-if="bulkMode && selectedIds.size"
      class="sticky top-2 z-20 flex flex-col gap-3 rounded-xl border border-brand-200 bg-gradient-to-r from-brand-50 to-white px-4 py-3 shadow-card md:flex-row md:items-center md:justify-between"
    >
      <span class="text-sm font-semibold text-brand-900">{{ selectedIds.size }} selected · {{ bulkStageLabel }}</span>
      <div class="flex flex-wrap gap-2">
        <Button type="button" variant="secondary" :disabled="bulkWorking" @click="clearSelection">Clear</Button>
        <template v-if="bulkStage === 'application'">
          <Button type="button" variant="secondary" :disabled="bulkWorking" @click="bulkMoveSelected('review')">To review</Button>
          <Button type="button" variant="secondary" :disabled="bulkWorking" @click="bulkMoveSelected('waitlist')">To waitlist</Button>
        </template>
        <template v-else-if="bulkStage === 'review'">
          <Button type="button" :disabled="bulkWorking" @click="bulkAcceptSelected">Accept</Button>
          <Button type="button" variant="secondary" :disabled="bulkWorking" @click="bulkMoveSelected('waitlist')">To waitlist</Button>
          <Button type="button" variant="secondary" :disabled="bulkWorking" @click="bulkMoveSelected('application')">To applications</Button>
        </template>
        <template v-else-if="bulkStage === 'accepted'">
          <Button type="button" :disabled="bulkWorking" @click="openBulkEnroll = true">Bulk enroll</Button>
        </template>
        <template v-else-if="bulkStage === 'waitlist'">
          <Button type="button" :disabled="bulkWorking" @click="bulkAcceptSelected">Accept</Button>
          <Button type="button" variant="secondary" :disabled="bulkWorking" @click="bulkMoveSelected('review')">To review</Button>
        </template>
        <Button type="button" variant="secondary" :disabled="bulkWorking" @click="openBulkReject">Reject…</Button>
      </div>
    </div>

    <!-- Kanban -->
    <div class="-mx-2 flex gap-3 overflow-x-auto pb-4 pt-1 md:mx-0">
      <div
        v-for="col in columns"
        :key="col.status"
        class="flex w-[min(100%,320px)] shrink-0 flex-col rounded-2xl border border-sc-line p-0.5 shadow-inner transition-all duration-200 md:w-72"
        :class="[
          columnMeta[col.status]?.column || 'bg-slate-50/90',
          isDragging && dragOverStatus === col.status ? 'ring-2 ring-brand-400/50 ring-offset-2 ring-offset-slate-50' : '',
          col.status === 'enrolled' ? 'opacity-[0.97]' : ''
        ]"
        @dragover.prevent="onColDragOver($event, col)"
        @drop.prevent="onColDrop($event, col)"
      >
        <div
          class="mb-1.5 flex items-center justify-between rounded-t-[0.9rem] px-3 py-2"
          :class="columnMeta[col.status]?.header || 'bg-white/80'"
        >
          <div class="flex min-w-0 items-center gap-2">
            <span
              class="h-2 w-2 shrink-0 rounded-full"
              :class="columnMeta[col.status]?.dot || 'bg-slate-400'"
              aria-hidden="true"
            />
            <span class="truncate text-sm font-semibold text-slate-800">{{ col.title }}</span>
            <span
              v-if="col.status === 'enrolled'"
              class="shrink-0 rounded bg-slate-200/80 px-1.5 py-0.5 text-2xs font-semibold text-slate-600"
              title="Use Enroll in profile to place students here"
            >
              via enroll
            </span>
          </div>
          <span
            class="rounded-full px-2 py-0.5 text-xs font-bold tabular-nums"
            :class="columnMeta[col.status]?.count || 'bg-white text-slate-700'"
          >
            {{ cardsByStatus(col.status).length }}
          </span>
        </div>
        <div class="min-h-[220px] flex-1 space-y-2 rounded-b-[0.85rem] bg-white/70 p-2">
          <div
            v-for="a in cardsByStatus(col.status)"
            :key="a.id"
            draggable="true"
            class="group cursor-grab rounded-xl border border-sc-line/90 bg-white p-3 shadow-soft transition-all duration-200 hover:border-brand-200/80 hover:shadow-card active:cursor-grabbing"
            @dragstart="onDragStart($event, a)"
            @dragend="onDragEnd"
            @click="openDrawer(a.id)"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="truncate font-semibold text-slate-900">{{ a.first_name }} {{ a.last_name }}</p>
                <p class="truncate text-xs text-slate-500">{{ a.application_number }}</p>
                <p v-if="a.applying_class_name" class="mt-1 truncate text-2xs text-slate-500">
                  Applying: {{ a.applying_class_name }}
                </p>
              </div>
              <label v-if="bulkMode && col.status === bulkStage" class="shrink-0" @click.stop>
                <input
                  type="checkbox"
                  class="rounded border-slate-300 text-brand-600"
                  :checked="selectedIds.has(a.id)"
                  @change="toggleSelect(a.id)"
                />
              </label>
            </div>
            <div v-if="a.ready_for_decision" class="mt-2 inline-block rounded-md bg-amber-100 px-2 py-0.5 text-2xs font-semibold text-amber-900">
              Ready for decision
            </div>
            <div class="mt-2 flex flex-wrap gap-1">
              <button
                v-for="act in quickActions(a, col.status)"
                :key="act.key"
                type="button"
                class="rounded-lg border border-sc-line px-2 py-0.5 text-2xs font-semibold text-slate-700 opacity-90 transition-opacity hover:bg-slate-50 group-hover:opacity-100"
                @click.stop="runQuick(act, a)"
              >
                {{ act.label }}
              </button>
            </div>
          </div>
          <p v-if="!cardsByStatus(col.status).length" class="py-10 text-center text-xs text-slate-400">
            Drop cards here or add an application
          </p>
        </div>
      </div>
    </div>

    <!-- New application modal -->
    <Modal v-model="showNewModal" :title="newFormMode === 'quick' ? 'Quick application' : 'Full application'">
      <div class="mb-4 flex gap-2">
        <button
          type="button"
          class="rounded-lg px-3 py-1.5 text-sm font-semibold"
          :class="newFormMode === 'quick' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'"
          @click="newFormMode = 'quick'"
        >
          Quick
        </button>
        <button
          type="button"
          class="rounded-lg px-3 py-1.5 text-sm font-semibold"
          :class="newFormMode === 'full' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'"
          @click="newFormMode = 'full'"
        >
          Full
        </button>
      </div>
      <div class="grid max-h-[60vh] gap-3 overflow-y-auto sm:grid-cols-2">
        <FormInput v-model="newForm.firstName" label="First name" required />
        <FormInput v-model="newForm.lastName" label="Last name" required />
        <SelectInput v-model="newForm.gender" label="Gender" :options="genderOpts" placeholder="Select" />
        <FormInput v-model="newForm.dob" label="Date of birth" type="date" />
        <FormInput v-model="newForm.parentName" label="Parent / guardian name" />
        <FormInput v-model="newForm.parentPhone" label="Parent phone" required @blur="checkDup" />
        <FormInput v-model="newForm.parentEmail" label="Parent email" type="email" />
        <SelectInput
          v-model.number="newForm.applyingClassId"
          label="Applying class"
          :options="classOptions"
          placeholder="Optional"
        />
        <template v-if="newFormMode === 'full'">
          <div class="sm:col-span-2">
            <FormInput v-model="newForm.previousSchool" label="Previous school" />
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
            <textarea v-model="newForm.address" rows="2" class="w-full rounded-xl border border-sc-line px-3 py-2 text-sm" />
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Medical info (optional)</label>
            <textarea v-model="newForm.medicalInfo" rows="2" class="w-full rounded-xl border border-sc-line px-3 py-2 text-sm" />
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
            <textarea v-model="newForm.applicantNotes" rows="2" class="w-full rounded-xl border border-sc-line px-3 py-2 text-sm" />
          </div>
        </template>
      </div>
      <p v-if="dupWarning" class="mt-2 text-sm text-amber-700">{{ dupWarning }}</p>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" type="button" @click="showNewModal = false">Cancel</Button>
          <Button type="button" :disabled="savingNew" @click="submitNew">{{ savingNew ? 'Saving…' : 'Submit' }}</Button>
        </div>
      </template>
    </Modal>

    <!-- Reject modal -->
    <Modal v-model="showRejectModal" :title="rejectModalTitle">
      <FormInput v-model="rejectReason" label="Reason" required />
      <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
        <input v-model="rejectNotify" type="checkbox" class="rounded border-slate-300" />
        Flag parent notification (logged)
      </label>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" type="button" @click="showRejectModal = false">Cancel</Button>
          <Button type="button" @click="confirmReject">Reject</Button>
        </div>
      </template>
    </Modal>

    <!-- Bulk enroll modal -->
    <Modal v-model="openBulkEnroll" title="Bulk enroll">
      <p class="mb-3 text-sm text-slate-600">
        Assign the same class and stream to all selected accepted applicants ({{ selectedIds.size }}).
      </p>
      <div
        v-if="!hasYearPeriodLists"
        class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
      >
        Add academic years and study periods under Academic setup first.
      </div>
      <div class="mb-3 space-y-3">
        <SelectInput
          v-model="enrollForm.academicYearId"
          label="Academic year"
          :options="enrollYearOptions"
          placeholder="Select year"
          @update:model-value="onBulkEnrollYearChange"
        />
        <SelectInput
          v-model="enrollForm.studyPeriodId"
          label="Study period"
          :options="bulkEnrollPeriodOptions"
          placeholder="Select period"
        />
      </div>
      <EnrollmentFields v-model="enrollForm" :classes="contextClasses" :streams="contextStreams" :year-label="yearLabel" :period-label="periodLabel" />
      <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
        <input v-model="enrollForm.forceCapacity" type="checkbox" class="rounded border-slate-300 text-brand-600" />
        Allow over-capacity (override)
      </label>
      <p v-if="!bulkEnrollValid && hasYearPeriodLists" class="mt-2 text-xs text-slate-500">Select year, period, and class.</p>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button variant="secondary" type="button" :disabled="bulkWorking" @click="openBulkEnroll = false">Cancel</Button>
          <Button type="button" :disabled="bulkWorking || !bulkEnrollValid" @click="submitBulkEnroll">
            {{ bulkWorking ? 'Working…' : 'Enroll all' }}
          </Button>
        </div>
      </template>
    </Modal>

    <!-- Profile drawer -->
    <Teleport to="body">
      <Transition name="drawer">
        <div
          v-if="drawerOpen && drawerApplicant"
          class="fixed inset-0 z-50 flex justify-end"
          @keydown.esc="closeDrawer"
        >
          <div class="absolute inset-0 bg-slate-900/40" @click="closeDrawer" />
          <div
            class="relative flex h-full w-full max-w-lg flex-col border-l border-sc-line bg-white shadow-2xl"
            role="dialog"
          >
            <div class="flex items-center justify-between border-b border-sc-line px-5 py-4">
              <div>
                <h2 class="text-lg font-semibold text-slate-900">
                  {{ drawerApplicant.first_name }} {{ drawerApplicant.last_name }}
                </h2>
                <p class="text-sm text-slate-500">{{ drawerApplicant.application_number }}</p>
              </div>
              <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="closeDrawer">×</button>
            </div>
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-6">
              <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Pipeline</h3>
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold capitalize"
                    :class="pipelineBadgeClass(drawerApplicant.pipeline_status)"
                  >
                    {{ drawerApplicant.pipeline_status }}
                  </span>
                  <span v-if="drawerApplicant.student_id" class="text-2xs font-medium text-slate-500">
                    Student #{{ drawerApplicant.student_id }}
                  </span>
                </div>
                <p v-if="drawerApplicant.admission_number" class="mt-2 text-sm text-slate-600">
                  Admission no. {{ drawerApplicant.admission_number }}
                </p>
              </section>

              <!-- Enrollment: surfaced early -->
              <section
                v-if="drawerApplicant.pipeline_status === 'enrolled'"
                class="rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/90 to-white p-4 shadow-soft"
              >
                <div class="flex items-start gap-3">
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-lg" aria-hidden="true">✓</div>
                  <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-emerald-900">Enrolled</h3>
                    <p class="mt-1 text-xs text-emerald-800/90">
                      This applicant is on the roll for the selected class. Update class or fees from the student record if your ERP exposes it.
                    </p>
                    <p v-if="drawerApplicant.student_id" class="mt-2 text-sm font-medium text-slate-800">
                      Linked student ID: {{ drawerApplicant.student_id }}
                    </p>
                  </div>
                </div>
              </section>

              <section
                v-else-if="drawerApplicant.pipeline_status === 'accepted' || drawerApplicant.pipeline_status === 'waitlist'"
                class="rounded-2xl border-2 border-brand-200/70 bg-gradient-to-b from-brand-50/80 to-white p-4 shadow-card"
              >
                <div class="mb-3 flex items-center justify-between gap-2">
                  <div>
                    <h3 class="text-sm font-semibold text-slate-900">Enrollment</h3>
                    <p class="mt-0.5 text-xs text-slate-600">
                      Select academic year, study period, and class. Active year/period from ERP are prefilled when set.
                    </p>
                  </div>
                  <span
                    v-if="enrollSingleValid"
                    class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold uppercase tracking-wide text-emerald-800"
                  >
                    Ready
                  </span>
                </div>
                <div
                  v-if="!hasYearPeriodLists"
                  class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
                >
                  Add at least one academic year and study period under Academic setup (ERP) before enrolling.
                </div>
                <div class="space-y-3">
                  <SelectInput
                    v-model="enrollSingle.academicYearId"
                    label="Academic year"
                    :options="enrollYearOptions"
                    placeholder="Select year"
                    @update:model-value="onEnrollYearChange"
                  />
                  <SelectInput
                    v-model="enrollSingle.studyPeriodId"
                    label="Study period"
                    :options="enrollPeriodOptions"
                    placeholder="Select period"
                  />
                </div>
                <EnrollmentFields
                  v-model="enrollSingle"
                  :classes="contextClasses"
                  :streams="contextStreams"
                  :year-label="yearLabel"
                  :period-label="periodLabel"
                />
                <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                  <input v-model="enrollSingle.forceCapacity" type="checkbox" class="rounded border-slate-300 text-brand-600" />
                  Override class capacity (use sparingly)
                </label>
                <p v-if="!enrollSingleValid && hasYearPeriodLists" class="mt-2 text-xs text-slate-500">
                  Choose year, period, and class to enable the button.
                </p>
                <Button class="mt-4 w-full sm:w-auto" type="button" :disabled="!enrollSingleValid || enrollSubmitting" @click="submitEnrollSingle">
                  {{ enrollSubmitting ? 'Enrolling…' : 'Complete enrollment' }}
                </Button>
              </section>

              <section class="space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Basic</h3>
                <FormInput v-model="editForm.firstName" label="First name" @update:model-value="scheduleSave" />
                <FormInput v-model="editForm.lastName" label="Last name" @update:model-value="scheduleSave" />
                <SelectInput v-model="editForm.gender" label="Gender" :options="genderOpts" @update:model-value="scheduleSave" />
                <FormInput v-model="editForm.dob" label="DOB" type="date" @update:model-value="scheduleSave" />
              </section>

              <section class="space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Parent / guardian</h3>
                <FormInput v-model="editForm.parentName" label="Name" @update:model-value="scheduleSave" />
                <FormInput v-model="editForm.parentPhone" label="Phone" @update:model-value="scheduleSave" />
                <FormInput v-model="editForm.parentEmail" label="Email" type="email" @update:model-value="scheduleSave" />
              </section>

              <section class="space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Academic intent</h3>
                <SelectInput
                  v-model.number="editForm.applyingClassId"
                  label="Applying class"
                  :options="classOptions"
                  placeholder="—"
                  @update:model-value="scheduleSave"
                />
                <FormInput v-model="editForm.previousSchool" label="Previous school" @update:model-value="scheduleSave" />
                <label class="block text-sm font-medium text-slate-700">Address</label>
                <textarea v-model="editForm.address" rows="2" class="w-full rounded-xl border border-sc-line px-3 py-2 text-sm" @input="scheduleSave" />
                <label class="block text-sm font-medium text-slate-700">Medical</label>
                <textarea v-model="editForm.medicalInfo" rows="2" class="w-full rounded-xl border border-sc-line px-3 py-2 text-sm" @input="scheduleSave" />
              </section>

              <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Review</h3>
                <label class="flex items-center gap-2 text-sm">
                  <input v-model="editForm.readyForDecision" type="checkbox" class="rounded border-slate-300" @change="scheduleSave" />
                  Ready for decision
                </label>
                <FormInput v-model="editForm.flaggedIssues" label="Flagged issues" class="mt-2" @update:model-value="scheduleSave" />
                <label class="mt-2 block text-sm font-medium text-slate-700">Internal notes</label>
                <textarea v-model="editForm.internalNotes" rows="3" class="w-full rounded-xl border border-sc-line px-3 py-2 text-sm" @input="scheduleSave" />
              </section>

              <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Interview</h3>
                <FormInput v-model="editForm.interviewAt" label="Scheduled" type="datetime-local" @update:model-value="scheduleSave" />
                <SelectInput
                  v-model="editForm.interviewStatus"
                  label="Status"
                  :options="interviewOpts"
                  @update:model-value="scheduleSave"
                />
                <FormInput v-model="editForm.interviewNotes" label="Notes" @update:model-value="scheduleSave" />
              </section>

              <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Documents</h3>
                <div class="flex flex-wrap gap-2">
                  <SelectInput v-model="docUploadType" label="Type" :options="docTypeOpts" class="min-w-[140px]" />
                  <input ref="fileInput" type="file" class="text-sm" @change="onFilePick" />
                </div>
                <ul class="mt-2 space-y-1 text-sm">
                  <li v-for="d in drawerApplicant.documents || []" :key="d.id" class="flex justify-between gap-2">
                    <span class="truncate">{{ d.doc_type }} — {{ d.original_name }}</span>
                    <a
                      :href="fileUrl(d.file_path)"
                      target="_blank"
                      rel="noopener"
                      class="shrink-0 text-brand-600 hover:underline"
                    >Open</a>
                  </li>
                </ul>
              </section>

              <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Internal comments</h3>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                  <div v-for="c in drawerApplicant.comments || []" :key="c.id" class="rounded-lg bg-slate-50 px-3 py-2 text-sm">
                    <p class="text-2xs text-slate-500">{{ c.created_by }} · {{ formatDt(c.created_at) }}</p>
                    <p class="text-slate-800">{{ c.body }}</p>
                  </div>
                </div>
                <div class="mt-2 flex gap-2">
                  <input v-model="commentDraft" type="text" class="flex-1 rounded-xl border border-sc-line px-3 py-2 text-sm" placeholder="Add comment…" @keydown.enter.prevent="postComment" />
                  <Button type="button" @click="postComment">Add</Button>
                </div>
              </section>

              <section>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Status history</h3>
                <ul class="space-y-2 border-l-2 border-slate-200 pl-4">
                  <li v-for="h in drawerApplicant.status_history || []" :key="h.id" class="relative text-sm">
                    <span class="absolute -left-[21px] top-1.5 h-2 w-2 rounded-full bg-brand-500" />
                    <p class="font-medium text-slate-800">{{ h.from_status || '—' }} → {{ h.to_status }}</p>
                    <p class="text-2xs text-slate-500">{{ h.actor }} · {{ formatDt(h.created_at) }}</p>
                    <p v-if="h.note" class="text-xs text-slate-600">{{ h.note }}</p>
                  </li>
                </ul>
              </section>
            </div>
            <div class="border-t border-sc-line px-5 py-4 flex flex-wrap gap-2">
              <Button v-if="drawerApplicant.pipeline_status !== 'rejected'" variant="secondary" type="button" @click="openRejectFromDrawer">Reject…</Button>
              <Button v-if="drawerApplicant.pipeline_status === 'application'" type="button" @click="moveFromDrawer('review')">Move to review</Button>
              <Button
                v-if="['application', 'review', 'waitlist'].includes(drawerApplicant.pipeline_status)"
                type="button"
                @click="acceptFromDrawer"
              >
                Accept
              </Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import EnrollmentFields from '@/components/admissions/EnrollmentFields.vue'
import { admissionsApi, uploadsPublicUrl } from '@/services/admissionsApi'
import useNotificationStore from '@/stores/notificationStore'

const { successToast, errorToast } = useNotificationStore()

const statCards = [
  { key: 'application', label: 'Applications' },
  { key: 'review', label: 'Review' },
  { key: 'accepted', label: 'Accepted' },
  { key: 'waitlist', label: 'Waitlist' },
  { key: 'enrolled', label: 'Enrolled' },
  { key: 'rejected', label: 'Rejected' },
  { key: 'total', label: 'Total' }
]

const columns = [
  { status: 'application', title: 'Applications' },
  { status: 'review', title: 'Review' },
  { status: 'accepted', title: 'Accepted' },
  { status: 'waitlist', title: 'Waitlist' },
  { status: 'enrolled', title: 'Enrolled' },
  { status: 'rejected', title: 'Rejected' }
]

/** Visual accents for stats dots + kanban columns */
const columnMeta = {
  application: {
    dot: 'bg-slate-400',
    column: 'bg-slate-50/90 border-slate-200/60',
    header: 'bg-slate-100/80',
    count: 'bg-slate-100/90 text-slate-800'
  },
  review: {
    dot: 'bg-amber-400',
    column: 'bg-amber-50/40 border-amber-100',
    header: 'bg-amber-50/90',
    count: 'bg-amber-100 text-amber-900'
  },
  accepted: {
    dot: 'bg-emerald-500',
    column: 'bg-emerald-50/50 border-emerald-100/80',
    header: 'bg-emerald-50/70',
    count: 'bg-emerald-100 text-emerald-900'
  },
  waitlist: {
    dot: 'bg-violet-500',
    column: 'bg-violet-50/50 border-violet-100',
    header: 'bg-violet-50/70',
    count: 'bg-violet-100 text-violet-900'
  },
  enrolled: {
    dot: 'bg-brand-500',
    column: 'bg-brand-50/40 border-brand-100/70',
    header: 'bg-brand-50/60',
    count: 'bg-brand-100 text-brand-900'
  },
  rejected: {
    dot: 'bg-rose-400',
    column: 'bg-rose-50/50 border-rose-100',
    header: 'bg-rose-50/70',
    count: 'bg-rose-100 text-rose-900'
  },
  total: { dot: 'bg-slate-400' }
}

function pipelineBadgeClass(status) {
  const m = {
    application: 'bg-slate-100 text-slate-800 ring-1 ring-slate-200/80',
    review: 'bg-amber-100 text-amber-900 ring-1 ring-amber-200/80',
    accepted: 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-200/80',
    waitlist: 'bg-violet-100 text-violet-900 ring-1 ring-violet-200/80',
    enrolled: 'bg-brand-100 text-brand-900 ring-1 ring-brand-200/80',
    rejected: 'bg-rose-100 text-rose-900 ring-1 ring-rose-200/80'
  }
  return m[status] || 'bg-slate-100 text-slate-800'
}

const bulkStage = ref('accepted')
const bulkStageOptions = [
  { value: 'application', label: 'Applications' },
  { value: 'review', label: 'Review' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'waitlist', label: 'Waitlist' }
]
const bulkStageLabel = computed(() => bulkStageOptions.find((o) => o.value === bulkStage.value)?.label || '…')

const bulkWorking = ref(false)
const enrollSubmitting = ref(false)
const isDragging = ref(false)
const dragOverStatus = ref(null)
const rejectBulkIds = ref([])

/** True when ERP has at least one year and one period to show in dropdowns */
const hasYearPeriodLists = computed(
  () => !!(ctx.value?.academicYears?.length && ctx.value?.studyPeriods?.length)
)

/** Use ref (not reactive) so v-model on EnrollmentFields replaces the whole object reliably */
const enrollSingle = ref({
  academicYearId: '',
  studyPeriodId: '',
  classId: '',
  streamId: '',
  forceCapacity: false
})

const enrollYearOptions = computed(() =>
  (ctx.value?.academicYears || []).map((y) => ({
    value: String(y.id),
    label: `${y.name}${Number(y.is_active) ? ' · active' : ''}`
  }))
)

const enrollPeriodOptions = computed(() => {
  const yearId = String(enrollSingle.value.academicYearId || '')
  if (!yearId) return []
  const periods = (ctx.value?.studyPeriods || []).filter((p) => String(p.academic_year_id) === yearId)
  return periods.map((p) => ({
    value: String(p.id),
    label: `${p.name}${Number(p.is_active) ? ' · active' : ''}`
  }))
})

const bulkEnrollPeriodOptions = computed(() => {
  const yearId = String(enrollForm.academicYearId || '')
  if (!yearId) return []
  const periods = (ctx.value?.studyPeriods || []).filter((p) => String(p.academic_year_id) === yearId)
  return periods.map((p) => ({
    value: String(p.id),
    label: `${p.name}${Number(p.is_active) ? ' · active' : ''}`
  }))
})

function onEnrollYearChange() {
  const yid = String(enrollSingle.value.academicYearId || '')
  const periods = (ctx.value?.studyPeriods || []).filter((p) => String(p.academic_year_id) === yid)
  const ok = periods.some((p) => String(p.id) === String(enrollSingle.value.studyPeriodId))
  if (!ok) enrollSingle.value.studyPeriodId = ''
}

function onBulkEnrollYearChange() {
  const yid = String(enrollForm.academicYearId || '')
  const periods = (ctx.value?.studyPeriods || []).filter((p) => String(p.academic_year_id) === yid)
  const ok = periods.some((p) => String(p.id) === String(enrollForm.studyPeriodId))
  if (!ok) enrollForm.studyPeriodId = ''
}

const rejectModalTitle = computed(() =>
  rejectBulkIds.value.length ? `Reject ${rejectBulkIds.value.length} applications` : 'Reject application'
)

const genderOpts = [
  { value: '', label: '—' },
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' }
]

const interviewOpts = [
  { value: 'none', label: 'None' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'completed', label: 'Completed' },
  { value: 'no_show', label: 'No show' }
]

const docTypeOpts = [
  { value: 'birth_certificate', label: 'Birth certificate' },
  { value: 'report_card', label: 'Report card' },
  { value: 'id', label: 'ID' },
  { value: 'other', label: 'Other' }
]

const loading = ref(true)
const applicants = ref([])
const stats = ref({})
const ctx = ref(null)
const bulkMode = ref(false)
const selectedIds = ref(new Set())
const showNewModal = ref(false)
const newFormMode = ref('quick')
const savingNew = ref(false)
const dupWarning = ref('')
const showRejectModal = ref(false)
const rejectTargetId = ref(null)
const rejectReason = ref('')
const rejectNotify = ref(false)
const openBulkEnroll = ref(false)

const drawerOpen = ref(false)
const drawerId = ref(null)
const drawerApplicant = ref(null)
const commentDraft = ref('')
const docUploadType = ref('other')
const fileInput = ref(null)

let saveTimer = null

const newForm = reactive({
  firstName: '',
  lastName: '',
  gender: '',
  dob: '',
  parentName: '',
  parentPhone: '',
  parentEmail: '',
  applyingClassId: '',
  previousSchool: '',
  address: '',
  medicalInfo: '',
  applicantNotes: ''
})

const editForm = reactive({
  firstName: '',
  lastName: '',
  gender: '',
  dob: '',
  parentName: '',
  parentPhone: '',
  parentEmail: '',
  applyingClassId: '',
  previousSchool: '',
  address: '',
  medicalInfo: '',
  readyForDecision: false,
  flaggedIssues: '',
  internalNotes: '',
  interviewAt: '',
  interviewStatus: 'none',
  interviewNotes: ''
})

const enrollForm = reactive({
  academicYearId: '',
  studyPeriodId: '',
  classId: '',
  streamId: '',
  forceCapacity: false
})

function idOk(v) {
  if (v === '' || v == null) return false
  const n = Number(v)
  return !Number.isNaN(n) && n > 0
}

const enrollSingleValid = computed(() => {
  const e = enrollSingle.value
  return idOk(e.academicYearId) && idOk(e.studyPeriodId) && idOk(e.classId)
})

const bulkEnrollValid = computed(
  () =>
    idOk(enrollForm.academicYearId) && idOk(enrollForm.studyPeriodId) && idOk(enrollForm.classId)
)

const contextClasses = computed(() => ctx.value?.classes || [])
const contextStreams = computed(() => ctx.value?.streams || [])

const classOptions = computed(() => [
  { value: '', label: '—' },
  ...contextClasses.value.map((c) => ({
    value: c.id,
    label: `${c.level_name ? c.level_name + ' · ' : ''}${c.name}`
  }))
])

const yearLabel = computed(() => ctx.value?.academicYear?.name || '—')
const periodLabel = computed(() => ctx.value?.studyPeriod?.name || '—')

function cardsByStatus(status) {
  return applicants.value.filter((a) => a.pipeline_status === status)
}

function fileUrl(path) {
  return uploadsPublicUrl(path)
}

function formatDt(s) {
  if (!s) return ''
  try {
    return new Date(s).toLocaleString()
  } catch {
    return s
  }
}

let dragApplicant = null

function onDragStart(e, a) {
  dragApplicant = a
  isDragging.value = true
  dragOverStatus.value = null
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData('text/plain', String(a.id))
}

function onDragEnd() {
  isDragging.value = false
  dragOverStatus.value = null
  dragApplicant = null
}

function onColDragOver(e, col) {
  if (isDragging.value) {
    dragOverStatus.value = col.status
  }
  if (col.status === 'enrolled') {
    e.dataTransfer.dropEffect = 'none'
    return
  }
  e.dataTransfer.dropEffect = 'move'
}

async function onColDrop(e, col) {
  const id = dragApplicant?.id ?? parseInt(e.dataTransfer.getData('text/plain'), 10)
  dragApplicant = null
  isDragging.value = false
  dragOverStatus.value = null
  if (!id || col.status === 'enrolled') {
    if (col.status === 'enrolled') {
      errorToast('Enrollment', 'Use the profile panel “Enroll” to assign class and complete enrollment.')
    }
    return
  }
  const a = applicants.value.find((x) => x.id === id)
  if (!a) return
  if (a.pipeline_status === col.status) return
  try {
    await admissionsApi.move({ id, pipelineStatus: col.status, note: `Moved to ${col.title}` })
    successToast('Updated', 'Pipeline updated.')
    await refreshAll()
  } catch (err) {
    errorToast('Move failed', err?.response?.data?.error || err?.message)
  }
}

function quickActions(a, colStatus) {
  const acts = []
  if (colStatus === 'application') acts.push({ key: 'review', label: '→ Review' })
  if (colStatus === 'review') acts.push({ key: 'accepted', label: 'Accept' })
  if (['application', 'review'].includes(colStatus)) acts.push({ key: 'waitlist', label: 'Waitlist' })
  if (colStatus === 'accepted') acts.push({ key: 'enroll_hint', label: 'Open to enroll' })
  return acts
}

async function runQuick(act, a) {
  if (act.key === 'enroll_hint') {
    openDrawer(a.id)
    return
  }
  try {
    if (act.key === 'accepted') {
      await admissionsApi.accept(a.id)
    } else {
      await admissionsApi.move({ id: a.id, pipelineStatus: act.key })
    }
    successToast('OK', 'Updated')
    await refreshAll()
  } catch (e) {
    errorToast('Action failed', e?.response?.data?.error || e?.message)
  }
}

function toggleSelect(id) {
  const next = new Set(selectedIds.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  selectedIds.value = next
}

function clearSelection() {
  selectedIds.value = new Set()
}

function selectAllInBulkColumn() {
  const ids = cardsByStatus(bulkStage.value).map((a) => a.id)
  selectedIds.value = new Set(ids)
}

async function bulkMoveSelected(targetStatus) {
  const ids = [...selectedIds.value]
  if (!ids.length) return
  bulkWorking.value = true
  try {
    for (const id of ids) {
      await admissionsApi.move({
        id,
        pipelineStatus: targetStatus,
        note: `Bulk move to ${targetStatus}`
      })
    }
    successToast('Bulk move', `${ids.length} updated`)
    clearSelection()
    await refreshAll()
  } catch (e) {
    errorToast('Bulk move', e?.response?.data?.error || e?.message)
  } finally {
    bulkWorking.value = false
  }
}

async function bulkAcceptSelected() {
  const ids = [...selectedIds.value]
  if (!ids.length) return
  bulkWorking.value = true
  try {
    for (const id of ids) {
      await admissionsApi.accept(id)
    }
    successToast('Bulk accept', `${ids.length} accepted`)
    clearSelection()
    await refreshAll()
  } catch (e) {
    errorToast('Bulk accept', e?.response?.data?.error || e?.message)
  } finally {
    bulkWorking.value = false
  }
}

function openBulkReject() {
  if (!selectedIds.value.size) return
  rejectTargetId.value = null
  rejectBulkIds.value = [...selectedIds.value]
  rejectReason.value = ''
  showRejectModal.value = true
}

async function refreshAll() {
  loading.value = true
  try {
    const [listRes, statsRes, ctxRes] = await Promise.all([
      admissionsApi.list(),
      admissionsApi.stats(),
      admissionsApi.context()
    ])
    applicants.value = listRes.data?.data || []
    stats.value = statsRes.data?.data || {}
    ctx.value = ctxRes.data?.data || null
    const y = ctx.value?.academicYear?.id
    const p = ctx.value?.studyPeriod?.id
    enrollForm.academicYearId = y != null && y !== '' ? String(y) : ''
    enrollForm.studyPeriodId = p != null && p !== '' ? String(p) : ''
    enrollSingle.value.academicYearId = y != null && y !== '' ? String(y) : ''
    enrollSingle.value.studyPeriodId = p != null && p !== '' ? String(p) : ''
  } catch (e) {
    errorToast('Load failed', e?.response?.data?.error || e?.message)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  refreshAll()
})

watch(bulkMode, (v) => {
  if (!v) clearSelection()
})

watch(bulkStage, () => {
  clearSelection()
})

function openNewModal() {
  dupWarning.value = ''
  showNewModal.value = true
}

async function checkDup() {
  if (!newForm.firstName || !newForm.lastName || !newForm.parentPhone) return
  try {
    const { data } = await admissionsApi.duplicateCheck(newForm.firstName, newForm.lastName, newForm.parentPhone)
    dupWarning.value = data?.data?.duplicate ? 'Possible duplicate application exists.' : ''
  } catch {
    dupWarning.value = ''
  }
}

async function submitNew() {
  savingNew.value = true
  try {
    await admissionsApi.create({
      firstName: newForm.firstName,
      lastName: newForm.lastName,
      gender: newForm.gender,
      dob: newForm.dob,
      parentName: newForm.parentName,
      parentPhone: newForm.parentPhone,
      parentEmail: newForm.parentEmail,
      applyingClassId: newForm.applyingClassId || undefined,
      previousSchool: newFormMode.value === 'full' ? newForm.previousSchool : undefined,
      address: newFormMode.value === 'full' ? newForm.address : undefined,
      medicalInfo: newFormMode.value === 'full' ? newForm.medicalInfo : undefined,
      applicantNotes: newFormMode.value === 'full' ? newForm.applicantNotes : undefined
    })
    successToast('Created', 'Application saved.')
    showNewModal.value = false
    await refreshAll()
  } catch (e) {
    errorToast('Error', e?.response?.data?.error || e?.message)
  } finally {
    savingNew.value = false
  }
}

async function openDrawer(id) {
  drawerId.value = id
  drawerOpen.value = true
  try {
    const { data } = await admissionsApi.get(id)
    drawerApplicant.value = data?.data
    mapToEditForm(drawerApplicant.value)
  } catch (e) {
    errorToast('Load', e?.response?.data?.error || e?.message)
  }
}

function mapToEditForm(a) {
  if (!a) return
  editForm.firstName = a.first_name || ''
  editForm.lastName = a.last_name || ''
  editForm.gender = a.gender || ''
  editForm.dob = a.dob ? String(a.dob).slice(0, 10) : ''
  editForm.parentName = a.parent_name || ''
  editForm.parentPhone = a.parent_phone || ''
  editForm.parentEmail = a.parent_email || ''
  editForm.applyingClassId = a.applying_class_id || ''
  editForm.previousSchool = a.previous_school || ''
  editForm.address = a.address || ''
  editForm.medicalInfo = a.medical_info || ''
  editForm.readyForDecision = !!Number(a.ready_for_decision)
  editForm.flaggedIssues = a.flagged_issues || ''
  editForm.internalNotes = a.internal_notes || ''
  editForm.interviewAt = a.interview_at ? a.interview_at.slice(0, 16) : ''
  editForm.interviewStatus = a.interview_status || 'none'
  editForm.interviewNotes = a.interview_notes || ''
  const y = ctx.value?.academicYear?.id
  const p = ctx.value?.studyPeriod?.id
  enrollSingle.value.academicYearId = y != null && y !== '' ? String(y) : ''
  enrollSingle.value.studyPeriodId = p != null && p !== '' ? String(p) : ''
  enrollSingle.value.classId = ''
  enrollSingle.value.streamId = ''
  enrollSingle.value.forceCapacity = false
}

function closeDrawer() {
  drawerOpen.value = false
  drawerApplicant.value = null
}

function scheduleSave() {
  clearTimeout(saveTimer)
  saveTimer = setTimeout(saveDrawer, 750)
}

async function saveDrawer() {
  if (!drawerApplicant.value?.id) return
  try {
    await admissionsApi.update({
      id: drawerApplicant.value.id,
      firstName: editForm.firstName,
      lastName: editForm.lastName,
      gender: editForm.gender,
      dob: editForm.dob,
      parentName: editForm.parentName,
      parentPhone: editForm.parentPhone,
      parentEmail: editForm.parentEmail,
      applyingClassId: editForm.applyingClassId || null,
      previousSchool: editForm.previousSchool,
      address: editForm.address,
      medicalInfo: editForm.medicalInfo,
      readyForDecision: editForm.readyForDecision,
      flaggedIssues: editForm.flaggedIssues,
      internalNotes: editForm.internalNotes,
      interviewAt: editForm.interviewAt || null,
      interviewStatus: editForm.interviewStatus,
      interviewNotes: editForm.interviewNotes
    })
    await refreshAll()
    const { data } = await admissionsApi.get(drawerApplicant.value.id)
    drawerApplicant.value = data?.data
  } catch (e) {
    errorToast('Save', e?.response?.data?.error || e?.message)
  }
}

async function postComment() {
  const t = commentDraft.value.trim()
  if (!t || !drawerApplicant.value?.id) return
  try {
    await admissionsApi.addComment({ applicantId: drawerApplicant.value.id, body: t })
    commentDraft.value = ''
    const { data } = await admissionsApi.get(drawerApplicant.value.id)
    drawerApplicant.value = data?.data
    successToast('Comment', 'Saved.')
  } catch (e) {
    errorToast('Comment', e?.response?.data?.error || e?.message)
  }
}

async function onFilePick(e) {
  const f = e.target.files?.[0]
  if (!f || !drawerApplicant.value?.id) return
  try {
    await admissionsApi.uploadDocument(drawerApplicant.value.id, docUploadType.value, f)
    const { data } = await admissionsApi.get(drawerApplicant.value.id)
    drawerApplicant.value = data?.data
    successToast('Upload', 'Document added.')
    e.target.value = ''
  } catch (err) {
    errorToast('Upload', err?.response?.data?.error || err?.message)
  }
}

function openRejectFromDrawer() {
  rejectBulkIds.value = []
  rejectTargetId.value = drawerApplicant.value?.id
  rejectReason.value = ''
  showRejectModal.value = true
}

async function confirmReject() {
  if (!rejectReason.value.trim()) return
  const bulk = rejectBulkIds.value.length
  if (!bulk && !rejectTargetId.value) return
  try {
    if (bulk) {
      for (const id of rejectBulkIds.value) {
        await admissionsApi.reject({ id, reason: rejectReason.value, notifyParent: rejectNotify.value })
      }
      rejectBulkIds.value = []
      clearSelection()
      showRejectModal.value = false
      await refreshAll()
      successToast('Rejected', `${bulk} applications updated`)
    } else {
      await admissionsApi.reject({
        id: rejectTargetId.value,
        reason: rejectReason.value,
        notifyParent: rejectNotify.value
      })
      showRejectModal.value = false
      closeDrawer()
      await refreshAll()
      successToast('Rejected', 'Recorded.')
    }
  } catch (e) {
    errorToast('Reject', e?.response?.data?.error || e?.message)
  }
}

async function moveFromDrawer(status) {
  if (!drawerApplicant.value) return
  try {
    await admissionsApi.move({ id: drawerApplicant.value.id, pipelineStatus: status })
    successToast('Updated', '')
    await refreshAll()
    const { data } = await admissionsApi.get(drawerApplicant.value.id)
    drawerApplicant.value = data?.data
    mapToEditForm(drawerApplicant.value)
  } catch (e) {
    errorToast('Move', e?.response?.data?.error || e?.message)
  }
}

async function acceptFromDrawer() {
  if (!drawerApplicant.value) return
  try {
    await admissionsApi.accept(drawerApplicant.value.id)
    successToast('Accepted', 'Student record prepared.')
    await refreshAll()
    const { data } = await admissionsApi.get(drawerApplicant.value.id)
    drawerApplicant.value = data?.data
    mapToEditForm(drawerApplicant.value)
  } catch (e) {
    errorToast('Accept', e?.response?.data?.error || e?.message)
  }
}

async function submitEnrollSingle() {
  if (!drawerApplicant.value || !enrollSingleValid.value) {
    errorToast('Enrollment', 'Select class, year, and period.')
    return
  }
  enrollSubmitting.value = true
  try {
    const e = enrollSingle.value
    await admissionsApi.enroll({
      applicantId: Number(drawerApplicant.value.id),
      academicYearId: Number(e.academicYearId),
      studyPeriodId: Number(e.studyPeriodId),
      classId: Number(e.classId),
      streamId: e.streamId ? Number(e.streamId) : undefined,
      forceCapacity: e.forceCapacity
    })
    successToast('Enrolled', 'Student placed in class for this period.')
    await refreshAll()
    const { data } = await admissionsApi.get(drawerApplicant.value.id)
    drawerApplicant.value = data?.data
    mapToEditForm(drawerApplicant.value)
  } catch (e) {
    errorToast('Enroll', e?.response?.data?.error || e?.message)
  } finally {
    enrollSubmitting.value = false
  }
}

async function submitBulkEnroll() {
  const ids = [...selectedIds.value]
  if (!ids.length || !bulkEnrollValid.value) return
  bulkWorking.value = true
  try {
    const { data } = await admissionsApi.bulkEnroll({
      applicantIds: ids,
      academicYearId: Number(enrollForm.academicYearId),
      studyPeriodId: Number(enrollForm.studyPeriodId),
      classId: Number(enrollForm.classId),
      streamId: enrollForm.streamId ? Number(enrollForm.streamId) : undefined,
      forceCapacity: enrollForm.forceCapacity
    })
    const r = data?.data
    const ok = r?.ok ?? 0
    const errs = r?.errors || []
    if (errs.length) {
      const sample = errs.slice(0, 3).map((e) => `#${e.applicantId}: ${e.error}`).join(' · ')
      successToast(
        'Bulk enroll (partial)',
        `${ok} enrolled, ${errs.length} failed. ${sample}${errs.length > 3 ? '…' : ''}`
      )
    } else {
      successToast('Bulk enroll', `${ok} student(s) enrolled.`)
    }
    openBulkEnroll.value = false
    clearSelection()
    await refreshAll()
  } catch (e) {
    errorToast('Bulk enroll', e?.response?.data?.error || e?.message)
  } finally {
    bulkWorking.value = false
  }
}
</script>
