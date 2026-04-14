import api from '@/config/api'

/** School admin home dashboard (students, enrolments, finance, admissions, activity). */
export const dashboardApi = {
  summary: () => api.get('dashboard/summary.php')
}
