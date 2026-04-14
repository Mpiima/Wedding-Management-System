<template>
  <div class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-100/40">
    <div class="mx-auto w-full max-w-4xl px-6 py-16">
      <div class="mb-8 flex flex-col items-center text-center">
        <AppLogo size="lg" />
        <!-- <p class="ui-label mt-4 text-brand-600">SCH PRO 360</p> -->
        <!-- <h1 class="mt-2 text-3xl font-bold text-slate-900">Subscribe / Register</h1> -->
        <p class="mt-3 text-slate-600">Create your school account and start digitizing operations in minutes.</p>
      </div>

      <div class="card-surface bg-white">
        <form class="grid grid-cols-1 gap-5 md:grid-cols-2" @submit.prevent="submitRegister">
          <FormInput v-model="form.schoolName" label="School Name" placeholder="Institution name" required />
          <FormInput v-model="form.adminName" label="Admin Full Name" placeholder="School administrator" required />
          <FormInput v-model="form.email" label="Admin Email" type="email" placeholder="admin@school.edu" required />
          <FormInput v-model="form.phone" label="Phone Number" placeholder="+256..." required />

          <SelectInput
            v-model="form.plan"
            label="Choose Plan"
            :options="planOptions"
            placeholder="Select a plan"
          />

          <SelectInput
            v-model="form.curriculum"
            label="Curriculum"
            :options="curriculumOptions"
            placeholder="Select curriculum"
            required
          />

          <FormInput v-model="form.students" label="Expected Number of Students" placeholder="e.g. 1500" />

          <FormInput v-model="form.password" type="password" label="Password" placeholder="Create password" required />
          <FormInput v-model="form.confirmPassword" type="password" label="Confirm Password" placeholder="Repeat password" required />

          <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-3 pt-2">
            <RouterLink to="/" class="btn-secondary">Back to Home</RouterLink>
            <button type="submit" class="btn-primary" :disabled="loading">{{ loading ? 'Submitting…' : 'Create Account' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import AppLogo from '@/components/common/AppLogo.vue'
import useNotificationStore from '@/stores/notificationStore'

const form = reactive({
  schoolName: '',
  adminName: '',
  email: '',
  phone: '',
  plan: 'professional',
  curriculum: 'local_based',
  students: '',
  password: '',
  confirmPassword: ''
})

const planOptions = [
  { value: 'starter', label: 'Starter Plan' },
  { value: 'professional', label: 'Professional Plan' },
  { value: 'enterprise', label: 'Enterprise Plan' }
]
const curriculumOptions = [
  { value: 'local_based', label: 'Local Based Curriculum' },
  { value: 'cambridge_international', label: 'Cambridge International Curriculum' }
]
const { successToast, errorToast } = useNotificationStore()
const authStore = useAuthStore()
const loading = ref(false)

async function submitRegister() {
  if (form.password !== form.confirmPassword) {
    errorToast('Validation Error', 'Passwords do not match.')
    return
  }
  loading.value = true
  try {
    await authStore.userRegistration({
      schoolName: form.schoolName,
      adminName: form.adminName,
      email: form.email,
      phone: form.phone,
      plan: form.plan,
      curriculum: form.curriculum,
      students: form.students,
      password: form.password
    })
    if (authStore.errorMessage) {
      errorToast('Registration Failed', authStore.errorMessage?.message || authStore.errorMessage?.error || 'Could not submit.')
      return
    }
    successToast('Registration Submitted', 'Your school has been submitted for super admin approval.')
  } finally {
    loading.value = false
  }
}
</script>

