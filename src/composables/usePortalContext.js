import { ref, computed, watch, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { studentsApi } from '@/services/studentsApi'
import { publicUploadUrl } from '@/utils/formatters'

/**
 * Resolves which student the portal shows: ?studentId= (staff preview) or logged-in user's student_id.
 */
export function usePortalContext() {
  const route = useRoute()
  const auth = useAuthStore()

  const studentId = computed(() => {
    const q = route.query.studentId
    if (q != null && String(q).trim() !== '') return String(q).trim()
    const sid = auth.profile?.student_id
    if (sid != null && sid !== '' && !Number.isNaN(Number(sid))) return String(Number(sid))
    return ''
  })

  const loading = ref(false)
  const error = ref('')
  const ctx = ref(null)

  async function load() {
    const id = studentId.value
    if (!id) {
      ctx.value = null
      loading.value = false
      error.value =
        'No student context. Open the portal from Students → Portal, or ask your school to link your login to a student record.'
      return
    }
    loading.value = true
    error.value = ''
    try {
      const { data } = await studentsApi.portalContext({ studentId: id })
      const payload = data?.data
      ctx.value = payload || null
      if (!payload?.student) {
        error.value = 'Student record not found.'
      }
    } catch (e) {
      ctx.value = null
      error.value = e?.response?.data?.error || e?.message || 'Failed to load student'
    } finally {
      loading.value = false
    }
  }

  watch(studentId, load, { immediate: true })

  const student = computed(() => ctx.value?.student ?? null)
  const enrollment = computed(() => ctx.value?.enrollment ?? null)
  const meta = computed(() => ctx.value?.meta ?? {})

  const displayName = computed(() => {
    const s = student.value
    if (!s) return ''
    return `${s.first_name || ''} ${s.last_name || ''}`.trim() || 'Student'
  })

  const shortName = computed(() => {
    const s = student.value
    if (!s) return ''
    return String(s.first_name || displayName.value.split(/\s+/)[0] || 'Student')
  })

  const photoUrl = computed(() => {
    const p = student.value?.photo_path
    return p ? publicUploadUrl(p) : ''
  })

  const programLabel = computed(() => {
    const e = enrollment.value
    if (!e) return '—'
    const c = e.class_name || ''
    const st = e.stream_name || ''
    if (c && st) return `${c} · ${st}`
    return c || st || '—'
  })

  const academicYearLabel = computed(() => meta.value?.academicYearName || '—')
  const studyPeriodLabel = computed(() => meta.value?.studyPeriodName || '—')
  const finance = computed(() => ctx.value?.finance ?? {})

  // reactive() so nested refs/computed unwrap in templates when provided via inject()
  return reactive({
    studentId,
    loading,
    error,
    ctx,
    student,
    enrollment,
    meta,
    displayName,
    shortName,
    photoUrl,
    programLabel,
    academicYearLabel,
    studyPeriodLabel,
    finance,
    load
  })
}
