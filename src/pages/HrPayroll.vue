<template>
  <div class="page-shell max-w-3xl">
    <PageHeader :title="activeTitle" :description="activeDescription" />
    <div class="card-surface text-sm text-slate-600">
      <p>Connect your HR module to manage staff records, work attendance, payroll runs, and leave approvals.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'

const route = useRoute()

const sectionMeta = {
  '/hr-payroll/staff': ['Staff', 'Manage all staff profiles and assignments.'],
  '/hr-payroll/none-staff': ['None Staff', 'Track non-teaching and support staff records.'],
  '/hr-payroll/teachers': ['Teachers', 'Teacher records, roles, and class allocations.'],
  '/hr-payroll/work-attendance': ['Work Attendance', 'Capture daily employee attendance and shifts.'],
  '/hr-payroll/payroll': ['PayRoll', 'Prepare payroll batches and salary processing.'],
  '/hr-payroll/leave-management': ['Leave Management', 'Approve leave requests and balances.']
}

const activeTitle = computed(() => sectionMeta[route.path]?.[0] || 'HR & Payroll')
const activeDescription = computed(() => sectionMeta[route.path]?.[1] || 'Staff records and payroll operations.')
</script>
