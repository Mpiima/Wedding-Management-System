import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/config/api.js'

const ENDPOINT = 'wedding-profile.php'

export const useWeddingProfileStore = defineStore('weddingProfile', () => {
  const profile = ref(null)
  const errorMessage = ref(null)
  const saveResponse = ref(null)

  async function fetchWeddingProfile() {
    errorMessage.value = null
    return await api
      .get(ENDPOINT)
      .then((response) => {
        if (response && response.data && response.data.data) {
          profile.value = response.data.data
        } else {
          profile.value = null
        }
        return response
      })
      .catch((error) => {
        if (error && error.response) {
          if (error.response.status === 404) {
            profile.value = null
            return error.response
          }
          errorMessage.value = error.response.data?.error || 'Failed to load wedding profile'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  async function createWeddingProfile(data) {
    errorMessage.value = null
    saveResponse.value = null
    return await api
      .post(ENDPOINT, data)
      .then((response) => {
        if (response && response.data && response.data.data) {
          profile.value = response.data.data
        }
        saveResponse.value = response.data
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to create wedding profile'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  async function updateWeddingProfile(data) {
    errorMessage.value = null
    saveResponse.value = null
    return await api
      .put(ENDPOINT, data)
      .then((response) => {
        if (response && response.data && response.data.data) {
          profile.value = response.data.data
        }
        saveResponse.value = response.data
        return response
      })
      .catch((error) => {
        if (error && error.response && error.response.data) {
          errorMessage.value = error.response.data.error || 'Failed to update wedding profile'
        } else {
          errorMessage.value = 'Network error'
        }
        throw error
      })
  }

  function clearError() {
    errorMessage.value = null
  }

  function setProfile(data) {
    profile.value = data
  }

  async function uploadPhoto(type, file) {
    errorMessage.value = null
    const formData = new FormData()
    formData.append('type', type)
    formData.append('photo', file)
    const apiModule = await import('@/config/api.js')
    const api = apiModule.default
    try {
      const response = await api.post('upload-wedding-photo.php', formData)
      const url = response?.data?.data?.url
      if (!url) throw new Error('No URL returned')
      return url
    } catch (err) {
      errorMessage.value = err?.response?.data?.error || err?.message || 'Photo upload failed'
      throw err
    }
  }

  return {
    profile,
    errorMessage,
    saveResponse,
    fetchWeddingProfile,
    createWeddingProfile,
    updateWeddingProfile,
    uploadPhoto,
    clearError,
    setProfile
  }
})
