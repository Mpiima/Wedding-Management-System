<template>
  <div v-if="showAdminNav" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-6 text-sm text-amber-950">
    <p class="font-semibold">Student sign-in only</p>
    <p class="mt-2 text-amber-900/90">
      Changing the student portal password is only available when signed in as that student. Close preview or sign out and use the student portal username and password on the login page.
    </p>
  </div>

  <div v-else class="mx-auto max-w-md space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Change password</h1>
      <p class="mt-1 text-sm text-slate-600">
        Use your portal username (<span class="font-mono text-slate-800">{{ usernameHint }}</span>) with your new password next time you sign in.
      </p>
    </div>

    <form class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
     <FormInput
        v-model="currentPassword"
        type="password"
        label="Current password"
        autocomplete="current-password"
        required
        :error="fieldErrors.current"
      />
      <FormInput
        v-model="newPassword"
        type="password"
        label="New password"
        autocomplete="new-password"
        hint="At least 6 characters."
        required
        :error="fieldErrors.new"
      />
      <FormInput
        v-model="confirmPassword"
        type="password"
        label="Confirm new password"
        autocomplete="new-password"
        required
        :error="fieldErrors.confirm"
      />

      <p v-if="formError" class="text-sm text-rose-600">{{ formError }}</p>
      <p v-if="successMessage" class="text-sm text-emerald-700">{{ successMessage }}</p>

      <div class="flex flex-wrap gap-2 pt-1">
        <Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Update password' }}</Button>
        <Button type="button" variant="secondary" :disabled="saving" @click="clearForm">Clear</Button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import FormInput from '@/components/ui/FormInput.vue'
import Button from '@/components/ui/Button.vue'
import { useAuth } from '@/composables/useAuth'
import { usePortalAdminNav } from '@/composables/usePortalAdminNav'
import api from '@/config/api'

const { user } = useAuth()
const { showAdminNav } = usePortalAdminNav()

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const saving = ref(false)
const formError = ref('')
const successMessage = ref('')
const fieldErrors = ref({ current: '', new: '', confirm: '' })

const usernameHint = computed(() => {
  const u = user.value
  if (u?.username) return String(u.username)
  if (u?.email) return String(u.email)
  return 'your portal username'
})

function clearForm() {
  currentPassword.value = ''
  newPassword.value = ''
  confirmPassword.value = ''
  formError.value = ''
  successMessage.value = ''
  fieldErrors.value = { current: '', new: '', confirm: '' }
}

async function submit() {
  formError.value = ''
  successMessage.value = ''
  fieldErrors.value = { current: '', new: '', confirm: '' }

  if (newPassword.value.length < 6) {
    fieldErrors.value.new = 'Use at least 6 characters'
    return
  }
  if (newPassword.value !== confirmPassword.value) {
    fieldErrors.value.confirm = 'Does not match new password'
    return
  }

  saving.value = true
  try {
    await api.post('auth/change-password.php', {
      currentPassword: currentPassword.value,
      newPassword: newPassword.value
    })
    successMessage.value = 'Your password was updated. Use it the next time you log in.'
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (e) {
    const msg =
      e?.response?.data?.error ||
      e?.response?.data?.message ||
      e?.message ||
      'Could not update password'
    formError.value = typeof msg === 'string' ? msg : 'Could not update password'
  } finally {
    saving.value = false
  }
}
</script>
