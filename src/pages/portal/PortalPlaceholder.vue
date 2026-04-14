<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-xl font-semibold text-slate-900">{{ info.title }}</h1>
      <p v-if="info.description" class="mt-2 text-sm leading-relaxed text-slate-600">{{ info.description }}</p>
    </div>
    <div
      class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-5 py-10 text-center text-sm text-slate-500"
    >
      {{ info.emptyHint || 'Your school will connect live data to this section when available.' }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

/** Copy keyed by route name — keeps router definitions minimal */
const PAGES = {
  PortalFinancePrn: {
    title: 'Generate PRN',
    description: 'Create or retrieve your Personal Reference Number (PRN) for finance clearance.',
    emptyHint: 'PRN generation will appear here once your school enables it.'
  },
  PortalFinanceRequirements: {
    title: 'School Requirements',
    description: 'Items and documents required for clearance and enrolment.',
    emptyHint: 'No requirements have been published yet.'
  },
  PortalFinanceStructure: {
    title: 'Fees structure',
    description: 'Approved fees and charges for your class and term.',
    emptyHint: 'Fee lines will load from your school’s billing setup.'
  },
  PortalFinanceInvoices: {
    title: 'My invoice history',
    description: 'Past and current invoices issued to you.',
    emptyHint: 'Invoices will list here when billing is active for your account.'
  },
  PortalFinancePaymentPlan: {
    title: 'Payment plan',
    description: 'Instalments and scheduled payments for your fees.',
    emptyHint: 'Payment plans will show when assigned by the bursar.'
  },
  PortalAdmissionsApply: {
    title: 'Apply or register',
    description: 'Start a new application or complete registration steps.',
    emptyHint: 'Use your school’s admissions workflow when it is opened for your intake.'
  },
  PortalAdmissionsHistory: {
    title: 'Admission history',
    description: 'Status and milestones for your admission file.',
    emptyHint: 'Admission events will appear here as they are recorded.'
  },
  PortalAdmissionsEnrollmentHistory: {
    title: 'Enrollment history',
    description: 'Terms and classes you have been enrolled in.',
    emptyHint: 'Enrollment records will sync from your school’s academic data.'
  },
  PortalResultsProvisional: {
    title: 'My provisional results',
    description: 'Unconfirmed results pending final approval.',
    emptyHint: 'Provisional slips will display when published for your cohort.'
  },
  PortalExamPermit: {
    title: 'Exam permit',
    description: 'Permits and eligibility for scheduled examinations.',
    emptyHint: 'Download or view permits when your school issues them.'
  },
  PortalMealCard: {
    title: 'Meal card',
    description: 'Meal plan and cafeteria access for the current term.',
    emptyHint: 'Meal entitlements will show when catering is linked to your profile.'
  },
  PortalCanteenExpenditure: {
    title: 'Expenditure history',
    description: 'Recent canteen purchases and balances.',
    emptyHint: 'Transactions will list when the canteen module is in use.'
  },
  PortalCanteenAccount: {
    title: 'Canteen account',
    description: 'Wallet or account balance for school catering.',
    emptyHint: 'Account details will appear when your school enables canteen billing.'
  },
  PortalBioData: {
    title: 'Bio data',
    description: 'Personal and contact details on file with the school.',
    emptyHint: 'For a full profile view, use the dashboard; updates may require the school office.'
  },
  PortalNotifications: {
    title: 'My notifications',
    description: 'Alerts and messages from your school.',
    emptyHint: 'No notifications yet.'
  },
  PortalCalendar: {
    title: 'Academic calendar',
    description: 'Term dates, holidays, and key academic events.',
    emptyHint: 'Calendar events will show when your school publishes them.'
  },
  PortalDiscipline: {
    title: 'Discipline report',
    description: 'Recorded conduct notes and disciplinary actions, if any.',
    emptyHint: 'No records to display, or access is restricted by school policy.'
  }
}

const info = computed(() => {
  const name = route.name ? String(route.name) : ''
  return (
    PAGES[name] || {
      title: 'Page',
      description: '',
      emptyHint: 'Content will be available soon.'
    }
  )
})
</script>
