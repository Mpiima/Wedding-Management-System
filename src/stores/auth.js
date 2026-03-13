import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/config/api.js'

export const useAuthStore = defineStore('auth', () => {
  const loginResponse = ref(null)
  const errorMessage = ref(null)
  const token = ref(localStorage.getItem('token'))
  const profile = ref(null)
  const abilities = ref([])
  const permissions = ref([])

  // Restore profile and permissions from localStorage on init
  try {
    const stored = localStorage.getItem('userProfile')
    if (stored) {
      const p = JSON.parse(stored)
      profile.value = p
      permissions.value = Array.isArray(p?.permissions) ? p.permissions : []
    }
  } catch (_) {}

  const isAuthenticated = computed(() => !!token.value)

  function can(permission) {
    const list = permissions.value
    if (!Array.isArray(list)) return false
    if (list.includes('*')) return true
    return list.includes(permission)
  }

  async function userLogin(data) {
    errorMessage.value = null
    loginResponse.value = null
    return await api
      .post('auth/login.php', data)
      .then((response) => {
        if (response && response.data) {
          const res = response.data
          if (res.error) {
            errorMessage.value = res.error
            localStorage.removeItem('token')
            localStorage.removeItem('userProfile')
            return
          }
          const dataInfo = res.data
          const authToken = res.token || res.data?.token
          if (authToken) {
            localStorage.setItem('token', authToken)
            token.value = authToken
          }
          if (dataInfo) {
            const userProfile = dataInfo.userProfile || dataInfo
            const perms = dataInfo.permissions ?? userProfile?.permissions
            if (Array.isArray(perms)) permissions.value = perms
            localStorage.setItem('userProfile', JSON.stringify(userProfile))
            profile.value = userProfile
          }
          loginResponse.value = res
          const userProfile = res.data?.userProfile || res.data
          const role = (userProfile?.role || res.data?.role || '').toLowerCase()
          if (role === 'admin') {
            abilities.value = [{ action: 'manage', resource: 'all' }]
          } else {
            abilities.value = [{ action: 'read', resource: 'all' }]
          }
          localStorage.setItem('abilities', JSON.stringify(abilities.value))
        }
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          const err = error.response.data
          errorMessage.value = err.error || err.message || 'Login failed'
        } else {
          errorMessage.value = 'Network error. Please try again.'
        }
        localStorage.removeItem('token')
        localStorage.removeItem('userProfile')
      })
  }

  function logout() {
    token.value = null
    profile.value = null
    loginResponse.value = null
    errorMessage.value = null
    abilities.value = []
    permissions.value = []
    localStorage.removeItem('token')
    localStorage.removeItem('userProfile')
    localStorage.removeItem('abilities')
  }

  function updateToken(data) {
    if (data) {
      token.value = data
      localStorage.setItem('token', data)
    }
  }

  function updateProfile(data) {
    if (data) {
      const profileData = typeof data === 'string' ? JSON.parse(data) : data
      profile.value = profileData
      localStorage.setItem('userProfile', JSON.stringify(profileData))
    }
  }

  async function fetchProfile() {
    try {
      const r = await api.get('me.php')
      if (r?.data?.data) {
        const current = profile.value || {}
        profile.value = { ...current, ...r.data.data }
        localStorage.setItem('userProfile', JSON.stringify(profile.value))
      }
      return r
    } catch (e) {
      throw e
    }
  }

  async function updateProfileApi(payload) {
    const r = await api.put('me.php', payload)
    if (r?.data?.data) {
      const current = profile.value || {}
      profile.value = { ...current, ...r.data.data }
      localStorage.setItem('userProfile', JSON.stringify(profile.value))
    }
    return r
  }

  async function uploadAvatar(file) {
    const form = new FormData()
    form.append('photo', file)
    const r = await api.post('upload-avatar.php', form)
    const url = r?.data?.data?.url
    if (url) await updateProfileApi({ avatar: url })
    return url
  }

  async function changePassword(currentPassword, newPassword) {
    errorMessage.value = null
    try {
      const r = await api.post('change-password.php', {
        current_password: currentPassword,
        new_password: newPassword
      })
      if (r?.data?.error) errorMessage.value = r.data.error
      return r
    } catch (err) {
      errorMessage.value = err?.response?.data?.error || err?.message || 'Failed to change password'
      throw err
    }
  }

  function clearError() {
    errorMessage.value = null
  }

  return {
    loginResponse,
    errorMessage,
    token,
    profile,
    abilities,
    permissions,
    isAuthenticated,
    can,
    userLogin,
    logout,
    updateToken,
    updateProfile,
    fetchProfile,
    updateProfileApi,
    uploadAvatar,
    changePassword,
    clearError
  }
})
