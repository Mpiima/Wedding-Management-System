import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/config/api.js'

export const useAuthStore = defineStore('auth', () => {
  const loginResponse = ref(null)
  const errorMessage = ref(null)
  const token = ref(localStorage.getItem('token'))
  const profile = ref(null)
  const abilities = ref([])

  // Restore profile from localStorage on init
  try {
    const stored = localStorage.getItem('userProfile')
    if (stored) profile.value = JSON.parse(stored)
  } catch (_) {}

  const isAuthenticated = computed(() => !!token.value)

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
            localStorage.setItem('userProfile', JSON.stringify(userProfile))
            profile.value = userProfile
          }
          loginResponse.value = res
          // Abilities based on role (extend as needed)
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

  function clearError() {
    errorMessage.value = null
  }

  return {
    loginResponse,
    errorMessage,
    token,
    profile,
    abilities,
    isAuthenticated,
    userLogin,
    logout,
    updateToken,
    updateProfile,
    clearError
  }
})
