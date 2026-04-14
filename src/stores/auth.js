import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/config/api.js'

export const useAuthStore = defineStore('auth', () => {
  const loginResponse = ref(null)
  const registrationResponse = ref(null)
  const errorMessage = ref(null)
  const token = ref(localStorage.getItem('token') || localStorage.getItem('sc360_token'))
  const userNumber = ref(null)
  const profile = ref(null)
  const resetPasswordResponse = ref(null)
  const setUserPasswordResponse = ref(null)
  const permissions = ref([])

  const isAuthenticated = computed(() => !!token.value)

  try {
    const storedProfile = localStorage.getItem('userProfile') || localStorage.getItem('sc360_user')
    if (storedProfile) {
      const parsed = JSON.parse(storedProfile)
      profile.value = parsed
      userNumber.value = parsed?.number ?? null
      permissions.value = Array.isArray(parsed?.permissions) ? parsed.permissions : []
    }
  } catch {
    /* ignore parse errors */
  }

  function syncAuthStorage(nextToken, nextProfile) {
    if (nextToken) {
      localStorage.setItem('token', nextToken)
      localStorage.setItem('sc360_token', nextToken)
    }
    if (nextProfile) {
      const value = JSON.stringify(nextProfile)
      localStorage.setItem('userProfile', value)
      localStorage.setItem('sc360_user', value)
    }
  }

  function normalizeLoginError(payload) {
    if (payload == null) return { message: 'Login failed' }
    if (typeof payload === 'string') return { message: payload }
    if (typeof payload.error === 'string') return { message: payload.error }
    if (payload.message) return typeof payload.message === 'string' ? { message: payload.message } : payload.message
    return payload
  }

  function mapRoleToUserType(role) {
    const r = String(role || '').toLowerCase()
    if (r.includes('super')) return 'Admin'
    if (r.includes('admin')) return 'Admin'
    if (r.includes('teacher') || r.includes('staff')) return 'Teacher'
    if (r.includes('student')) return 'Student'
    if (r.includes('parent')) return 'User'
    if (r.includes('user')) return 'User'
    return 'Admin'
  }

  function buildProfileFromLoginPayload(dataInfo) {
    const d = dataInfo?.data && typeof dataInfo.data === 'object' ? dataInfo.data : {}
    const raw = d.userProfile && typeof d.userProfile === 'object' ? d.userProfile : d
    const type = d.type || mapRoleToUserType(raw.role ?? d.role)
    const firstname = raw.firstname ?? d.firstname ?? ''
    const lastname = raw.lastname ?? d.lastname ?? ''
    const name =
      [firstname, lastname].filter(Boolean).join(' ').trim() ||
      raw.username ||
      d.username ||
      raw.email ||
      d.email ||
      'User'

    let perms = []
    if (type === 'Admin') {
      perms = ['*']
    } else if (type === 'User') {
      perms = Array.isArray(d.permissions) ? d.permissions : ['read']
    } else if (Array.isArray(d.permissions) && d.permissions.length) {
      perms = d.permissions
    } else if (raw.powers) {
      perms = String(raw.powers)
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)
    } else {
      perms = ['read']
    }

    const tenantId = Number(d.tenant_id ?? raw.tenant_id ?? 1)
    const studentIdRaw = d.student_id ?? raw.student_id
    const student_id =
      studentIdRaw != null && studentIdRaw !== '' && !Number.isNaN(Number(studentIdRaw))
        ? Number(studentIdRaw)
        : null
    return {
      ...d,
      ...raw,
      type,
      name,
      firstname,
      lastname,
      email: raw.email ?? d.email,
      role: raw.role ?? d.role,
      tenant_id: Number.isFinite(tenantId) ? tenantId : 1,
      permissions: perms,
      scopes: Array.isArray(d.scopes) ? d.scopes : [],
      number: d.number ?? raw.number ?? raw.rolenumber ?? null,
      student_id
    }
  }

  async function userLogin(data) {
    errorMessage.value = null
    const payload =
      data && (data.email != null || data.login != null)
        ? {
          email: String(data.email ?? data.login ?? '').trim(),
          password: data.password
        }
        : data

    return await api
      .post('auth/login.php', payload)
      .then((response) => {
        if (response && response.data) {
          const dataInfo = response.data
          if (dataInfo.error) {
            loginResponse.value = null
            errorMessage.value = normalizeLoginError(dataInfo)
            return
          }

          const userData = buildProfileFromLoginPayload(dataInfo)
          const nextToken = dataInfo?.token || `session-${Date.now()}`

          let abilityList = []
          const userType = String(userData?.type || '').trim()

          if (userType === 'Admin') {
            abilityList.push({ action: 'manage', resource: 'all' })
          } else if (userType === 'User') {
            abilityList.push({ action: 'read', resource: 'all' })
          } else if (Array.isArray(userData?.permissions) && userData.permissions.length > 0) {
            userData.permissions.forEach((permission) => {
              abilityList.push({ action: permission, resource: 'general' })
            })
          } else {
            abilityList.push({ action: 'read', resource: 'general' })
          }

          localStorage.setItem('abilities', JSON.stringify(abilityList))

          loginResponse.value = dataInfo
          userNumber.value = userData?.number ?? null
          token.value = nextToken
          profile.value = userData
          permissions.value = Array.isArray(userData?.permissions) ? userData.permissions : []

          syncAuthStorage(nextToken, userData)
        }
      })
      .catch((error) => {
        if (error && error.response) {
          localStorage.removeItem('token')
          localStorage.removeItem('sc360_token')
          errorMessage.value = normalizeLoginError(error.response.data)
        }
      })
  }

  async function resetPassword(data) {
    errorMessage.value = null
    return await api
      .post('auth/reset-password', data)
      .then((response) => {
        resetPasswordResponse.value = response.data
      })
      .catch((error) => {
        if (error && error.response) {
          localStorage.removeItem('token')
          localStorage.removeItem('sc360_token')
          errorMessage.value = error.response.data
        }
      })
  }

  async function userProfile() {
    errorMessage.value = null
    return await api
      .get('main/profile')
      .then((response) => {
        const user = response.data
        const data = user?.data || null
        if (data) {
          localStorage.setItem('userProfile', JSON.stringify(data))
          localStorage.setItem('sc360_user', JSON.stringify(data))
          profile.value = data
          userNumber.value = data?.number ?? null
          permissions.value = Array.isArray(data?.permissions) ? data.permissions : []
        }
      })
      .catch((error) => {
        const status = error?.response?.status
        if (status === 404) {
          return
        }
        if (error && error.response) {
          localStorage.removeItem('token')
          localStorage.removeItem('sc360_token')
          errorMessage.value = normalizeLoginError(error.response.data)
        }
      })
  }

  async function userRegistration(data) {
    errorMessage.value = null
    return await api
      .post('auth/register.phpnt', data)
      .then((response) => {
        registrationResponse.value = response.data
      })
      .catch((error) => {
        if (error && error.response) {
          localStorage.removeItem('token')
          localStorage.removeItem('sc360_token')
          errorMessage.value = error.response.data
        }
      })
  }

  function updateToken(data) {
    if (data) {
      token.value = data
      localStorage.setItem('token', data)
      localStorage.setItem('sc360_token', data)
    }
  }

  function updateProfile(data) {
    if (data) {
      const profileData = typeof data === 'string' ? JSON.parse(data) : data
      profile.value = profileData
      userNumber.value = profileData?.number ?? null
      permissions.value = Array.isArray(profileData?.permissions) ? profileData.permissions : []
      localStorage.setItem('userProfile', JSON.stringify(profileData))
      localStorage.setItem('sc360_user', JSON.stringify(profileData))
    }
  }

  async function setUserPassword(data) {
    errorMessage.value = null
    return await api
      .post('auth/set-password', data)
      .then((response) => {
        setUserPasswordResponse.value = response.data
      })
      .catch((error) => {
        if (error && error.response) {
          localStorage.removeItem('token')
          localStorage.removeItem('sc360_token')
          errorMessage.value = error.response.data
        }
      })
  }

  function can(permission) {
    const list = permissions.value
    if (!Array.isArray(list)) return false
    if (list.includes('*')) return true
    return list.includes(permission)
  }

  function logout() {
    loginResponse.value = null
    registrationResponse.value = null
    resetPasswordResponse.value = null
    setUserPasswordResponse.value = null
    errorMessage.value = null
    token.value = null
    userNumber.value = null
    profile.value = null
    permissions.value = []

    localStorage.removeItem('token')
    localStorage.removeItem('sc360_token')
    localStorage.removeItem('abilities')
    localStorage.removeItem('userProfile')
    localStorage.removeItem('sc360_user')
  }

  function clearError() {
    errorMessage.value = null
  }

  return {
    loginResponse,
    registrationResponse,
    errorMessage,
    token,
    profile,
    userNumber,
    resetPasswordResponse,
    setUserPasswordResponse,
    permissions,
    isAuthenticated,
    userLogin,
    userRegistration,
    userProfile,
    updateToken,
    updateProfile,
    resetPassword,
    setUserPassword,
    can,
    logout,
    clearError
  }
})
