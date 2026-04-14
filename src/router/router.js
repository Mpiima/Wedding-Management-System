import { createRouter, createWebHistory } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import StudentPortalLayout from '@/layouts/StudentPortalLayout.vue'
import ParentPortalLayout from '@/layouts/ParentPortalLayout.vue'
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue'
import { useAuthStore } from '@/stores/auth'

function lazy(name) {
  return () => import(`@/pages/${name}.vue`)
}

function lazyPortal(name) {
  return () => import(`@/pages/portal/${name}.vue`)
}

function lazyPortalPlaceholder() {
  return () => import('@/pages/portal/PortalPlaceholder.vue')
}

function lazyParentPortal(name) {
  return () => import(`@/pages/parentPortal/${name}.vue`)
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'Landing',
      component: lazy('Landing'),
      meta: { public: true }
    },
    {
      path: '/login',
      name: 'Login',
      component: lazy('Login'),
      meta: { public: true }
    },
    {
      path: '/request-demo',
      name: 'RequestDemo',
      component: lazy('RequestDemo'),
      meta: { public: true }
    },
    {
      path: '/register',
      name: 'Register',
      component: lazy('Register'),
      meta: { public: true }
    },
    {
      path: '/',
      component: AppLayout,
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/dashboard' },
        { path: 'dashboard', name: 'Dashboard', component: lazy('Dashboard') },
        { path: 'students', name: 'Students', component: lazy('Students') },
        { path: 'admissions', redirect: '/admissions/applicants' },
        {
          path: 'admissions/applicants',
          name: 'AdmissionsApplicants',
          component: () => import('@/pages/admissions/Applicants.vue')
        },
        {
          path: 'admissions/enrollments',
          name: 'AdmissionsEnrollments',
          component: () => import('@/pages/admissions/Enrollments.vue')
        },
        { path: 'students/admissions', redirect: '/admissions/applicants' },
        { path: 'students/admissions/board', name: 'AdmissionsBoard', component: lazy('Admissions') },
        { path: 'academics/classes', name: 'Classes', component: () => import('@/pages/academics/Classes.vue') },
        { path: 'academics/subjects', name: 'Subjects', component: () => import('@/pages/academics/Subjects.vue') },
        { path: 'academics/teachers', name: 'Teachers', component: () => import('@/pages/academics/Teachers.vue') },
        {
          path: 'academics/erp-setup',
          name: 'ErpSetupWizard',
          component: () => import('@/pages/ErpSetupWizard.vue')
        },
        { path: 'examinations/exam-types', name: 'ExamTypes', component: () => import('@/pages/examinations/ExamTypes.vue') },
        {
          path: 'examinations/exam-schedules',
          name: 'ExamSchedules',
          component: () => import('@/pages/examinations/ExamSchedules.vue')
        },
        { path: 'examinations/exams', name: 'ExamsList', component: () => import('@/pages/examinations/Exams.vue') },
        { path: 'examinations/marks-entry', name: 'MarksEntry', component: () => import('@/pages/examinations/MarksEntry.vue') },
        {
          path: 'examinations/score-sheet',
          name: 'ScoreSheet',
          component: () => import('@/pages/examinations/ScoreSheet.vue')
        },
        { path: 'examinations/results', name: 'Results', component: () => import('@/pages/examinations/Results.vue') },
        { path: 'finance', redirect: '/finance/dashboard' },
        {
          path: 'finance/dashboard',
          name: 'FinanceDashboard',
          component: () => import('@/pages/finance/FinanceDashboard.vue')
        },
        {
          path: 'finance/student-fees',
          name: 'StudentFees',
          component: () => import('@/pages/finance/StudentFees.vue')
        },
        { path: 'finance/fee-structure', name: 'FeeStructure', component: () => import('@/pages/finance/FeeStructure.vue') },
        { path: 'finance/invoices', name: 'Invoices', component: () => import('@/pages/finance/Invoices.vue') },
        { path: 'finance/payments', name: 'Payments', component: () => import('@/pages/finance/Payments.vue') },
        { path: 'attendance', name: 'Attendance', component: lazy('Attendance') },
        {
          path: 'attendance/school-general',
          name: 'SchoolGeneralAttendance',
          component: () => import('@/pages/SchoolGeneralAttendance.vue')
        },
        { path: 'hr-payroll', redirect: '/hr-payroll/staff' },
        { path: 'hr-payroll/staff', name: 'HrPayrollStaff', component: lazy('HrPayroll') },
        { path: 'hr-payroll/none-staff', name: 'HrPayrollNoneStaff', component: lazy('HrPayroll') },
        { path: 'hr-payroll/teachers', name: 'HrPayrollTeachers', component: lazy('HrPayroll') },
        { path: 'hr-payroll/work-attendance', name: 'HrPayrollWorkAttendance', component: lazy('HrPayroll') },
        { path: 'hr-payroll/payroll', name: 'HrPayrollPayroll', component: lazy('HrPayroll') },
        { path: 'hr-payroll/leave-management', name: 'HrPayrollLeaveManagement', component: lazy('HrPayroll') },
        { path: 'reports', name: 'Reports', component: lazy('Reports') },
        { path: 'communication', name: 'Communication', component: lazy('Communication') },
        { path: 'settings', name: 'Settings', component: lazy('Settings') },
        { path: 'school/setup', name: 'SchoolSetup', component: lazy('SchoolSetup') },
        { path: 'tenants', name: 'Tenants', component: lazy('Tenants') },
        { path: 'users', name: 'Users', component: lazy('Users') },
        { path: 'canteen/dashboard', name: 'CanteenDashboard', component: () => import('@/pages/canteen/Dashboard.vue') },
        { path: 'canteen/items', name: 'CanteenItems', component: () => import('@/pages/canteen/Items.vue') },
        { path: 'canteen/transactions', name: 'CanteenTransactions', component: () => import('@/pages/canteen/Transactions.vue') },
        { path: 'examinations/exam-permits', name: 'ExamPermitsList', component: () => import('@/pages/examinations/ExamPermits.vue') },
        {
          path: 'examinations/exam-permits/print/:permitId',
          name: 'ExamPermitPrint',
          component: () => import('@/pages/examinations/ExamPermitPrint.vue')
        }
      ]
    },
    {
      path: '/portal',
      component: StudentPortalLayout,
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/portal/dashboard' },
        { path: 'dashboard', name: 'PortalDashboard', component: lazyPortal('Dashboard') },
        /* Finance clearance */
        { path: 'finance/prn', name: 'PortalFinancePrn', component: lazyPortalPlaceholder() },
        { path: 'fees', name: 'PortalFees', component: lazyPortal('Fees') },
        { path: 'finance/requirements', name: 'PortalFinanceRequirements', component: lazyPortalPlaceholder() },
        { path: 'finance/structure', name: 'PortalFinanceStructure', component: lazyPortalPlaceholder() },
        { path: 'finance/invoices', name: 'PortalFinanceInvoices', component: lazyPortalPlaceholder() },
        { path: 'finance/payment-plan', name: 'PortalFinancePaymentPlan', component: lazyPortalPlaceholder() },
        /* Admission & registration */
        { path: 'admissions/apply', name: 'PortalAdmissionsApply', component: lazyPortalPlaceholder() },
        { path: 'admissions/history', name: 'PortalAdmissionsHistory', component: lazyPortalPlaceholder() },
        { path: 'admissions/enrollment-history', name: 'PortalAdmissionsEnrollmentHistory', component: lazyPortalPlaceholder() },
        /* My programme — more specific paths first */
        { path: 'results/provisional', name: 'PortalResultsProvisional', component: lazyPortalPlaceholder() },
        { path: 'results', name: 'PortalResults', component: lazyPortal('Results') },
        { path: 'timetable', name: 'PortalTimetable', component: lazyPortal('Timetable') },
        { path: 'exam-permit', name: 'PortalExamPermit', component: lazyPortalPlaceholder() },
        { path: 'meal-card', name: 'PortalMealCard', component: lazyPortalPlaceholder() },
        /* Canteen */
        { path: 'canteen/expenditure', name: 'PortalCanteenExpenditure', component: lazyPortalPlaceholder() },
        { path: 'canteen/account', name: 'PortalCanteenAccount', component: lazyPortalPlaceholder() },
        /* Other */
        { path: 'bio-data', name: 'PortalBioData', component: lazyPortalPlaceholder() },
        { path: 'notifications', name: 'PortalNotifications', component: lazyPortalPlaceholder() },
        { path: 'calendar', name: 'PortalCalendar', component: lazyPortalPlaceholder() },
        { path: 'attendance', name: 'PortalAttendance', component: lazyPortal('Attendance') },
        { path: 'discipline', name: 'PortalDiscipline', component: lazyPortalPlaceholder() },
        { path: 'password', name: 'PortalChangePassword', component: lazyPortal('ChangePassword') }
      ]
    },
    {
      path: '/parent',
      component: ParentPortalLayout,
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/parent/dashboard' },
        { path: 'dashboard', name: 'ParentDashboard', component: lazyParentPortal('Dashboard') },
        { path: 'children', name: 'ParentChildren', component: lazyParentPortal('Children') },
        { path: 'results', name: 'ParentResults', component: lazyParentPortal('Results') },
        { path: 'attendance', name: 'ParentAttendance', component: lazyParentPortal('Attendance') },
        { path: 'fees', name: 'ParentFees', component: lazyParentPortal('Fees') },
        { path: 'timetable', name: 'ParentTimetable', component: lazyParentPortal('Timetable') },
        { path: 'messages', name: 'ParentMessages', component: lazyParentPortal('Messages') }
      ]
    },
    {
      path: '/super-admin',
      component: SuperAdminLayout,
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/super-admin/dashboard' },
        { path: 'dashboard', name: 'SuperAdminDashboard', component: () => import('@/pages/superAdmin/Dashboard.vue') },
        { path: 'schools', name: 'SuperAdminSchools', component: () => import('@/pages/superAdmin/Schools.vue') },
        { path: 'subscriptions', name: 'SuperAdminSubscriptions', component: () => import('@/pages/superAdmin/Subscriptions.vue') },
        {
          path: 'roles-permissions',
          name: 'SuperAdminRolesPermissions',
          component: () => import('@/pages/RolesPermissions.vue')
        },
        { path: 'payments', name: 'SuperAdminPayments', component: () => import('@/pages/superAdmin/Payments.vue') },
        { path: 'analytics', name: 'SuperAdminAnalytics', component: () => import('@/pages/superAdmin/Analytics.vue') },
        { path: 'support', name: 'SuperAdminSupport', component: () => import('@/pages/superAdmin/Support.vue') },
        { path: 'settings', name: 'SuperAdminSettings', component: () => import('@/pages/superAdmin/Settings.vue') }
      ]
    }
  ]
})

router.beforeEach((to, from, next) => {
  let profile = localStorage.getItem('userProfile') || localStorage.getItem('sc360_user')
  let token = localStorage.getItem('token') || localStorage.getItem('sc360_token')

  const authStore = useAuthStore()

  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (profile) {
      let user = null
      try {
        user = JSON.parse(profile)
      } catch {
        user = null
      }

      if (user !== null) {
        authStore.profile = user
        authStore.token = token
        authStore.permissions = Array.isArray(user?.permissions) ? user.permissions : []
        const isSuperAdmin = Number(user?.tenant_id) === 0 || String(user?.role || '').toLowerCase().includes('super')
        const needsSetup = !isSuperAdmin && user?.setup_completed === false
        if (needsSetup && to.path !== '/school/setup') {
          next({ path: '/school/setup' })
          return
        }
        next()
      } else {
        next({ name: 'Login' })
      }
    } else {
      next({ name: 'Login' })
    }
  } else {
    if (profile === null) {
      next()
    } else if (to.name === 'Login') {
      let user = null
      try {
        user = JSON.parse(profile)
      } catch {
        user = null
      }
      const isSuperAdmin = Number(user?.tenant_id) === 0 || String(user?.role || '').toLowerCase().includes('super')
      const needsSetup = !isSuperAdmin && user?.setup_completed === false
      next(needsSetup ? { path: '/school/setup' } : { name: 'Dashboard' })
    } else {
      next()
    }
  }
})

const DEFAULT_TITLED = 'SCH PRO 360'

router.afterEach(() => {
  document.title = localStorage.getItem('landingPageTitle') || DEFAULT_TITLED
})

export default router

