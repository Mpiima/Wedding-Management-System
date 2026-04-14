/**
 * School ERP Academic Setup — PHP endpoints under /api/erp/*.php
 * Laravel-style equivalents: Resource controllers for AcademicYear, StudyPeriod, Level,
 * SchoolClass, Stream, Subject; plus POST set-active-year, set-active-period, subjectlinks,
 * linkstreams, cloneyear, erp-wizard, context.
 * Base: VITE_APP_BASE_URL (e.g. …/api)
 */
import api from '@/config/api'

const p = (path) => `erp/${path}`

export const erpApi = {
  academicYears: () => api.get(p('academic-years.php')),
  createAcademicYear: (body) => api.post(p('academic-years.php'), body),
  updateAcademicYear: (body) => api.put(p('academic-years.php'), body),
  deleteAcademicYear: (id) => api.delete(`${p('academic-years.php')}?id=${id}`),
  setActiveYear: (id) => api.post(p('set-active-year.php'), { id }),

  studyPeriods: (academicYearId) =>
    api.get(`${p('study-periods.php')}?academicYearId=${academicYearId}`),
  createStudyPeriod: (body) => api.post(p('study-periods.php'), body),
  updateStudyPeriod: (body) => api.put(p('study-periods.php'), body),
  deleteStudyPeriod: (id) => api.delete(`${p('study-periods.php')}?id=${id}`),
  reorderStudyPeriods: (academicYearId, order) =>
    api.post(p('study-periods.php'), { action: 'reorder', academicYearId, order }),
  setActivePeriod: (id) => api.post(p('set-active-period.php'), { id }),

  levels: () => api.get(p('levels.php')),
  createLevel: (name) => api.post(p('levels.php'), { name }),
  updateLevel: (id, name) => api.put(p('levels.php'), { id, name }),
  deleteLevel: (id) => api.delete(`${p('levels.php')}?id=${id}`),

  classes: () => api.get(p('school-classes.php')),
  createClass: (body) => api.post(p('school-classes.php'), body),
  bulkClasses: (body) => api.post(p('school-classes.php'), { bulk: true, ...body }),
  updateClass: (body) => api.put(p('school-classes.php'), body),
  deleteClass: (id) => api.delete(`${p('school-classes.php')}?id=${id}`),

  streams: () => api.get(p('streams.php')),
  createStream: (name) => api.post(p('streams.php'), { name }),
  deleteStream: (id) => api.delete(`${p('streams.php')}?id=${id}`),
  linkStreams: (body) => api.post(p('linkstreams.php'), body),
  classStreamIds: (classId) => api.get(`${p('linkstreams.php')}?classId=${classId}`),

  subjects: () => api.get(p('subjects.php')),
  createSubject: (name) => api.post(p('subjects.php'), { name }),
  createSubjectsBulk: (names) => api.post(p('subjects.php'), { names }),
  deleteSubject: (id) => api.delete(`${p('subjects.php')}?id=${id}`),
  assignSubjects: (classIds, subjectIds) =>
    api.post(p('subjectlinks.php'), { classIds, subjectIds }),

  cloneYear: (body) => api.post(p('cloneyear.php'), body),
  wizardState: () => api.get(p('erp-wizard.php')),
  saveWizardState: (body) => api.post(p('erp-wizard.php'), body),
  context: () => api.get(p('context.php')),

  examTypes: {
    list: (params = {}) => {
      const q = new URLSearchParams()
      if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
      if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
      const s = q.toString()
      return api.get(`${p('exam-types.php')}${s ? `?${s}` : ''}`)
    },
    create: (body) => api.post(p('exam-types.php'), body),
    update: (body) => api.put(p('exam-types.php'), body),
    delete: (id) => api.delete(`${p('exam-types.php')}?id=${id}`)
  },

  classSubjects: (classId) => api.get(`${p('class-subjects.php')}?classId=${classId}`),

  schoolUsers: () => api.get(p('school-users.php')),

  examSchedules: {
    list: (params = {}) => {
      const q = new URLSearchParams()
      if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
      if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
      const s = q.toString()
      return api.get(`${p('exam-schedules.php')}${s ? `?${s}` : ''}`)
    },
    get: (id) => api.get(`${p('exam-schedules.php')}?id=${id}`),
    create: (body) => api.post(p('exam-schedules.php'), body),
    update: (body) => api.put(p('exam-schedules.php'), body),
    delete: (id) => api.delete(`${p('exam-schedules.php')}?id=${id}`)
  },

  examMarks: {
    list: (params = {}) => {
      const q = new URLSearchParams()
      if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
      if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
      if (params.examScheduleId) q.set('examScheduleId', String(params.examScheduleId))
      if (params.subjectId) q.set('subjectId', String(params.subjectId))
      return api.get(`${p('exam-marks.php')}?${q}`)
    },
    saveRow: (body) => api.post(p('exam-marks.php'), body)
  },

  scoreSheet: (params = {}) => {
    const q = new URLSearchParams()
    if (params.studentId) q.set('studentId', String(params.studentId))
    if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
    if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
    return api.get(`${p('score-sheet.php')}?${q}`)
  },

  attendance: {
    session: (params = {}) => {
      const q = new URLSearchParams({ action: 'session' })
      if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
      if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
      if (params.classId) q.set('classId', String(params.classId))
      if (params.date) q.set('date', String(params.date))
      if (params.streamId) q.set('streamId', String(params.streamId))
      return api.get(`${p('attendance.php')}?${q}`)
    },
    save: (body) => api.post(p('attendance.php'), { action: 'save', ...body }),
    history: (params = {}) => {
      const q = new URLSearchParams({ action: 'history' })
      if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
      if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
      if (params.classId) q.set('classId', String(params.classId))
      if (params.dateFrom) q.set('dateFrom', String(params.dateFrom))
      if (params.dateTo) q.set('dateTo', String(params.dateTo))
      if (params.streamId) q.set('streamId', String(params.streamId))
      return api.get(`${p('attendance.php')}?${q}`)
    },
    studentProfile: (params = {}) => {
      const q = new URLSearchParams({ action: 'student_profile' })
      if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
      if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
      if (params.studentId) q.set('studentId', String(params.studentId))
      if (params.dateFrom) q.set('dateFrom', String(params.dateFrom))
      if (params.dateTo) q.set('dateTo', String(params.dateTo))
      return api.get(`${p('attendance.php')}?${q}`)
    },
    dashboard: (params = {}) => {
      const q = new URLSearchParams({ action: 'dashboard' })
      if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
      if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
      if (params.date) q.set('date', String(params.date))
      return api.get(`${p('attendance.php')}?${q}`)
    },
    insights: (params = {}) => {
      const q = new URLSearchParams({ action: 'insights' })
      if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
      if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
      if (params.lookbackDays) q.set('lookbackDays', String(params.lookbackDays))
      return api.get(`${p('attendance.php')}?${q}`)
    },
    notifyAbsents: (body = {}) => api.post(p('attendance.php'), { action: 'notify_absents', ...body })
  },

  schoolAttendance: {
    list: (params = {}) => {
      const q = new URLSearchParams()
      if (params.academicYearId) q.set('academicYearId', String(params.academicYearId))
      if (params.studyPeriodId) q.set('studyPeriodId', String(params.studyPeriodId))
      if (params.date) q.set('date', String(params.date))
      const s = q.toString()
      return api.get(`${p('school-attendance.php')}${s ? `?${s}` : ''}`)
    },
    save: (body) => api.post(p('school-attendance.php'), body)
  }
}
