<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">My account</h1>
      <p class="text-sm text-gray-500">Edit your profile and profile picture</p>
    </div>

    <div class="rounded-2xl bg-white shadow-card border border-gray-100 p-6 max-w-2xl">
      <form class="space-y-4" @submit.prevent="save">
        <div class="flex flex-col sm:flex-row gap-6">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Profile picture</label>
            <div class="relative h-24 w-24 rounded-2xl border-2 border-dashed border-rose-200 bg-rose-50/30 overflow-hidden">
              <img
                v-if="avatarPreview || authStore.profile?.avatar"
                :key="avatarKey"
                :src="avatarPreview || avatarUrl(authStore.profile?.avatar)"
                alt="Avatar"
                class="w-full h-full object-cover"
              />
              <span v-else class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">No photo</span>
              <span v-if="uploadingAvatar" class="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-sm font-medium">Updating…</span>
              <input
                type="file"
                accept="image/*"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                @change="onAvatarChange"
              />
            </div>
            <p class="text-xs text-gray-500">JPEG, PNG, GIF or WebP</p>
          </div>
          <div class="flex-1 space-y-4">
            <FormInput v-model="form.firstname" label="First name" placeholder="First name" />
            <FormInput v-model="form.lastname" label="Last name" placeholder="Last name" />
            <FormInput v-model="form.email" label="Email" type="email" placeholder="you@example.com" required />
            <FormInput v-model="form.username" label="Username" placeholder="Username" disabled class="opacity-70" />
          </div>
        </div>
        <p v-if="authStore.errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">{{ authStore.errorMessage }}</p>
        <p v-if="successMessage" class="text-sm text-green-600 bg-green-50 rounded-xl px-4 py-2">{{ successMessage }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="saving">
            {{ saving ? 'Saving…' : 'Save profile' }}
          </button>
        </div>
      </form>
    </div>

    <div class="rounded-2xl bg-white shadow-card border border-gray-100 p-6 max-w-2xl">
      <h2 class="text-base font-semibold text-wmis-text mb-1">Change password</h2>
      <p class="text-sm text-gray-500 mb-4">Enter your current password and choose a new one.</p>
      <form class="space-y-4" @submit.prevent="changePasswordSubmit">
        <FormInput
          v-model="passwordForm.current_password"
          label="Current password"
          type="password"
          placeholder="••••••••"
          required
        />
        <FormInput
          v-model="passwordForm.new_password"
          label="New password"
          type="password"
          placeholder="At least 6 characters"
          required
        />
        <FormInput
          v-model="passwordForm.confirm_password"
          label="Confirm new password"
          type="password"
          placeholder="••••••••"
          required
        />
        <p v-if="passwordError" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">{{ passwordError }}</p>
        <p v-if="passwordSuccessMessage" class="text-sm text-green-600 bg-green-50 rounded-xl px-4 py-2">{{ passwordSuccessMessage }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button
            type="submit"
            class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50"
            :disabled="changingPassword"
          >
            {{ changingPassword ? 'Updating…' : 'Change password' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import FormInput from '@/components/FormInput.vue'
import { useAuthStore } from '@/stores/auth'
import { baseURL } from '@/config/api.js'

const authStore = useAuthStore()
const form = reactive({ firstname: '', lastname: '', email: '', username: '' })
const passwordForm = reactive({ current_password: '', new_password: '', confirm_password: '' })
const saving = ref(false)
const changingPassword = ref(false)
const successMessage = ref('')
const passwordSuccessMessage = ref('')
const passwordError = ref('')
const uploadingAvatar = ref(false)
const avatarPreview = ref('')
const avatarKey = ref(0)

function avatarUrl(path) {
  if (!path) return ''
  const base = (baseURL || '').replace(/\/$/, '')
  return base ? `${base}/${path.replace(/^\//, '')}` : path
}

async function onAvatarChange(e) {
  const file = e.target?.files?.[0]
  if (!file || !file.type.startsWith('image/')) return
  authStore.clearError()
  uploadingAvatar.value = true
  try {
    await authStore.uploadAvatar(file)
    avatarKey.value++
    avatarPreview.value = ''
    successMessage.value = 'Profile picture updated.'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (_) {
    // error in store
  } finally {
    uploadingAvatar.value = false
    e.target.value = ''
  }
}

function fillForm() {
  const p = authStore.profile
  if (p) {
    form.firstname = p.firstname || ''
    form.lastname = p.lastname || ''
    form.email = p.email || ''
    form.username = p.username || ''
  }
}

async function save() {
  authStore.clearError()
  successMessage.value = ''
  passwordError.value = ''
  saving.value = true
  try {
    await authStore.updateProfileApi({
      firstname: form.firstname.trim() || null,
      lastname: form.lastname.trim() || null,
      email: form.email.trim() || null
    })
    successMessage.value = 'Profile saved.'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (_) {}
  finally {
    saving.value = false
  }
}

async function changePasswordSubmit() {
  passwordError.value = ''
  passwordSuccessMessage.value = ''
  authStore.clearError()
  if (passwordForm.new_password.length < 6) {
    passwordError.value = 'New password must be at least 6 characters.'
    return
  }
  if (passwordForm.new_password !== passwordForm.confirm_password) {
    passwordError.value = 'New password and confirmation do not match.'
    return
  }
  changingPassword.value = true
  try {
    await authStore.changePassword(passwordForm.current_password, passwordForm.new_password)
    if (authStore.errorMessage) {
      passwordError.value = authStore.errorMessage
      return
    }
    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.confirm_password = ''
    passwordSuccessMessage.value = 'Password changed successfully.'
    setTimeout(() => { passwordSuccessMessage.value = '' }, 4000)
  } catch (_) {
    if (authStore.errorMessage) passwordError.value = authStore.errorMessage
  } finally {
    changingPassword.value = false
  }
}

onMounted(async () => {
  try {
    await authStore.fetchProfile()
  } catch (_) {}
  fillForm()
})
</script>
