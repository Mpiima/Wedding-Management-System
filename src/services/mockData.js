import dashboard from '@/data/dashboard.json'
import students from '@/data/students.json'
import finance from '@/data/finance.json'
import exams from '@/data/exams.json'
import attendance from '@/data/attendance.json'
import invoices from '@/data/invoices.json'
import payments from '@/data/payments.json'
import academics from '@/data/academics.json'
import marks from '@/data/marks.json'
import results from '@/data/results.json'
import feeStructure from '@/data/feeStructure.json'
import portal from '@/data/portal.json'
import parentPortal from '@/data/parentPortal.json'
import canteen from '@/data/canteen.json'
import examPermits from '@/data/examPermits.json'
import superAdmin from '@/data/superAdmin.json'

function clone(x) {
  return structuredClone(x)
}

export function getDashboardData() {
  return Promise.resolve(clone(dashboard))
}

export function getStudents() {
  return Promise.resolve(clone(students.items))
}

export function getFinanceRecords() {
  return Promise.resolve(clone(finance.items))
}

export function getExams() {
  return Promise.resolve(clone(exams.items))
}

export function getAttendanceSummaries() {
  return Promise.resolve(clone(attendance.items))
}

export function getAttendanceRegister() {
  return Promise.resolve(clone(attendance.register || []))
}

export function getInvoices() {
  return Promise.resolve(clone(invoices.items))
}

export function getPayments() {
  return Promise.resolve(clone(payments.items))
}

export function getClasses() {
  return Promise.resolve(clone(academics.classes))
}

export function getSubjects() {
  return Promise.resolve(clone(academics.subjects))
}

export function getTeachers() {
  return Promise.resolve(clone(academics.teachers))
}

export function getMarksEntry() {
  return Promise.resolve(clone(marks))
}

export function getResults() {
  return Promise.resolve(clone(results.reports))
}

export function getFeeStructure() {
  return Promise.resolve(clone(feeStructure.items))
}

export function getPortalData() {
  return Promise.resolve(clone(portal))
}

export function getParentPortalData() {
  return Promise.resolve(clone(parentPortal))
}

export function getCanteenData() {
  return Promise.resolve(clone(canteen))
}

export function getExamPermitsData() {
  return Promise.resolve(clone(examPermits))
}

/** Super Admin mock bundle (schools, payments, logs, etc.). Subscription plans are not included — use GET super-admin/subscription-plans.php only. */
export function getSuperAdminData() {
  return Promise.resolve(clone(superAdmin))
}
