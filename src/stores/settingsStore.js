import { defineStore } from 'pinia'
import { ref } from 'vue'

const STORAGE_KEY = 'sc360_ui_settings_v1'
const DEFAULT_SETTINGS = {
  tenant_id: 1,
  school_name: 'SchPro360 Demo School',
  logo_url: '',
  config: {
    timezone: 'Africa/Kampala',
    currency: 'UGX'
  }
}

function readLocal() {
  try {
    const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')
    return parsed && typeof parsed === 'object' ? parsed : { ...DEFAULT_SETTINGS }
  } catch {
    return { ...DEFAULT_SETTINGS }
  }
}

function writeLocal(value) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
}

export const useSettingsStore = defineStore('settings', () => {
  const settings = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchSettings({ tenantId } = {}) {
    loading.value = true
    error.value = null
    settings.value = readLocal()
    loading.value = false
  }

  async function updateSettings(payload, { tenantId } = {}) {
    loading.value = true
    error.value = null
    const next = { ...(readLocal() || {}), ...(payload || {}) }
    writeLocal(next)
    settings.value = next
    loading.value = false
    return { message: 'Settings updated (UI only)' }
  }

  return { settings, loading, error, fetchSettings, updateSettings }
})

