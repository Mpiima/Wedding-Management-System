import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getSuperAdminData } from '@/services/mockData'
import api, { baseURL } from '@/config/api.js'

const STORAGE_KEY = 'sc360_super_admin_v1'

function safeParse(json) {
  try {
    return JSON.parse(json)
  } catch {
    return null
  }
}

function apiErrorMessage(e) {
  const d = e?.response?.data
  if (typeof d === 'string') return d
  if (d?.error) return typeof d.error === 'string' ? d.error : JSON.stringify(d.error)
  if (d?.message) return typeof d.message === 'string' ? d.message : JSON.stringify(d.message)
  return e?.message || 'Request failed'
}

export const useSuperAdminStore = defineStore('superAdmin', () => {
  const loading = ref(false)
  const error = ref(null)
  /** Plans-specific: missing API URL or GET /subscription-plans failed (never from mock JSON). */
  const plansLoadError = ref(null)

  const schools = ref([])
  const plans = ref([])
  const payments = ref([])
  const activityLogs = ref([])
  const loginHistory = ref([])
  const settings = ref({})
  const analytics = ref({})

  const schoolsById = computed(() => new Map(schools.value.map((s) => [s.id, s])))
  const plansById = computed(() => new Map(plans.value.map((p) => [String(p.id), p])))

  function persist() {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        schools: schools.value,
        payments: payments.value,
        activityLogs: activityLogs.value,
        loginHistory: loginHistory.value,
        settings: settings.value
      })
    )
  }

  /** Remove legacy `plans` key from older localStorage (plans are API-only now). */
  function migrateRemovePlansFromStorage() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY)
      if (!raw) return
      const o = JSON.parse(raw)
      if (o && Object.prototype.hasOwnProperty.call(o, 'plans')) {
        delete o.plans
        localStorage.setItem(STORAGE_KEY, JSON.stringify(o))
      }
    } catch {
      /* ignore */
    }
  }

  /**
   * Subscription plans — API only (GET super-admin/subscription-plans.php). No mock / no JSON file.
   */
  async function fetchPlans() {
    plans.value = []
    plansLoadError.value = null
    if (!baseURL) {
      plansLoadError.value =
        'Set VITE_APP_BASE_URL to your API root (e.g. http://localhost/.../api) to load subscription plans.'
      return
    }
    try {
      const { data: body } = await api.get('super-admin/subscription-plans.php')
      const list = body?.data
      plans.value = Array.isArray(list) ? list : []
    } catch (e) {
      plans.value = []
      plansLoadError.value = apiErrorMessage(e)
    }
  }

  async function fetchAll() {
    loading.value = true
    error.value = null
    plansLoadError.value = null

    try {
      migrateRemovePlansFromStorage()
      const stored = safeParse(localStorage.getItem(STORAGE_KEY))
      if (stored?.schools) {
        schools.value = Array.isArray(stored.schools) ? stored.schools : []
        payments.value = Array.isArray(stored.payments) ? stored.payments : []
        activityLogs.value = Array.isArray(stored.activityLogs) ? stored.activityLogs : []
        loginHistory.value = Array.isArray(stored.loginHistory) ? stored.loginHistory : []
        settings.value = stored.settings || {}
        analytics.value = stored.analytics || {}
      } else {
        const data = await getSuperAdminData()
        schools.value = Array.isArray(data?.schools) ? data.schools : []
        payments.value = Array.isArray(data?.payments) ? data.payments : []
        activityLogs.value = Array.isArray(data?.support?.activityLogs) ? data.support.activityLogs : []
        loginHistory.value = Array.isArray(data?.support?.loginHistory) ? data.support.loginHistory : []
        settings.value = data?.settings || {}
        analytics.value = data?.analytics || {}
        persist()
      }

      await fetchPlans()
      await fetchSchools()
    } catch (e) {
      error.value = e?.message || 'Failed to load super admin data'
    } finally {
      loading.value = false
    }
  }

  async function fetchSchools() {
    if (!baseURL) return
    try {
      const { data: body } = await api.get('super-admin/schools.php')
      schools.value = Array.isArray(body?.data) ? body.data : []
      persist()
    } catch (e) {
      error.value = apiErrorMessage(e)
      throw e
    }
  }

  function planName(planId) {
    return plansById.value.get(String(planId))?.name || planId
  }

  function schoolName(schoolId) {
    return schoolsById.value.get(schoolId)?.name || schoolId
  }

  /**
   * Create or update a plan via POST / PUT (no localStorage / no mock).
   */
  async function upsertPlan(plan) {
    if (!baseURL) {
      throw new Error('Set VITE_APP_BASE_URL to your API root (e.g. …/api) to manage subscription plans.')
    }
    const name = String(plan?.name || '').trim()
    const priceMonthly = Number(plan?.priceMonthly ?? 0)
    const features = Array.isArray(plan?.features) ? plan.features : []

    if (!name || Number.isNaN(priceMonthly)) {
      throw new Error('Valid plan name and price are required.')
    }

    const id = plan?.id != null && String(plan.id).trim() !== '' ? String(plan.id).trim() : null

    if (id) {
      await api.put('super-admin/subscription-plans.php', {
        id,
        name,
        priceMonthly,
        features
      })
    } else {
      await api.post('super-admin/subscription-plans.php', {
        name,
        priceMonthly,
        features
      })
    }

    await fetchPlans()
  }

  function setSchoolStatus(schoolId, status) {
    const id = String(schoolId)
    schools.value = schools.value.map((s) => (s.id === id ? { ...s, status } : s))

    activityLogs.value = [
      {
        id: `log-${Date.now()}`,
        time: new Date().toLocaleString(),
        actor: 'SuperAdmin',
        action: 'SCHOOL_STATUS_UPDATED',
        detail: `Updated ${schoolName(id)} status to ${status}`
      },
      ...activityLogs.value
    ]

    persist()
  }

  async function approveSchool(id) {
    await api.post('super-admin/schools.php', { id, action: 'approve' })
    await fetchSchools()
  }

  async function rejectSchool(id, reason = '') {
    await api.post('super-admin/schools.php', { id, action: 'reject', reason })
    await fetchSchools()
  }

  async function updateSchool(payload) {
    await api.put('super-admin/schools.php', payload)
    await fetchSchools()
  }

  async function deleteSchoolById(id) {
    await api.delete(`super-admin/schools.php?id=${encodeURIComponent(String(id))}`)
    await fetchSchools()
  }

  async function deletePlan(planId) {
    const raw = planId == null ? '' : String(planId).trim()
    if (!raw) return

    if (!baseURL) {
      throw new Error('Set VITE_APP_BASE_URL to your API root to delete subscription plans.')
    }

    const path = `super-admin/subscription-plans.php?id=${encodeURIComponent(raw)}`
    await api.delete(path)
    await fetchPlans()
  }

  function assignPlan(schoolId, planId) {
    const schId = String(schoolId)
    const pId = String(planId)
    schools.value = schools.value.map((s) => (s.id === schId ? { ...s, planId: pId } : s))

    activityLogs.value = [
      {
        id: `log-${Date.now()}`,
        time: new Date().toLocaleString(),
        actor: 'SuperAdmin',
        action: 'PLAN_ASSIGNED',
        detail: `Assigned ${planName(pId)} to ${schoolName(schId)}`
      },
      ...activityLogs.value
    ]

    persist()
  }

  function updateSettings(patch) {
    settings.value = { ...settings.value, ...patch }
    persist()
  }

  return {
    loading,
    error,
    plansLoadError,
    schools,
    plans,
    payments,
    activityLogs,
    loginHistory,
    settings,
    analytics,
    schoolsById,
    plansById,
    planName,
    schoolName,
    fetchAll,
    fetchSchools,
    fetchPlans,
    upsertPlan,
    deletePlan,
    approveSchool,
    rejectSchool,
    updateSchool,
    deleteSchoolById,
    setSchoolStatus,
    assignPlan,
    updateSettings
  }
})
