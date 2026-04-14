<template>
  <div class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-100/40">
    <div class="mx-auto w-full max-w-4xl px-6 py-16">
      <div class="mb-8 flex flex-col items-center text-center">
        <AppLogo size="lg" />
        <!-- <p class="ui-label mt-4 text-brand-600">SCH PRO 360</p> -->
        <h1 class="mt-2 text-3xl font-bold text-slate-900">Request a Demo</h1>
        <p class="mt-3 text-slate-600">Tell us about your school and we will schedule a personalized product walkthrough.</p>
      </div>

      <div class="card-surface bg-white">
        <form class="grid grid-cols-1 gap-5 md:grid-cols-2" @submit.prevent="submitDemo">
          <FormInput v-model="form.fullName" label="Full Name" placeholder="Your name" required />
          <FormInput v-model="form.email" label="Work Email" type="email" placeholder="you@school.edu" required />
          <FormInput v-model="form.phone" label="Phone Number" placeholder="+256..." required />
          <FormInput v-model="form.schoolName" label="School Name" placeholder="School / Institution" required />
          <FormInput v-model="form.role" label="Your Role" placeholder="Principal, Bursar, IT Admin..." required />
          <FormInput v-model="form.students" label="Approx. Number of Students" placeholder="e.g. 1200" />

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">What do you want to see in the demo?</label>
            <textarea
              v-model="form.message"
              rows="4"
              class="w-full rounded-xl border border-sc-line px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-500/20"
              placeholder="Admissions, fees, portal, reports..."
            />
          </div>

          <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-3 pt-2">
            <RouterLink to="/" class="btn-secondary">Back to Home</RouterLink>
            <button type="submit" class="btn-primary">Submit Demo Request</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import FormInput from '@/components/ui/FormInput.vue'
import AppLogo from '@/components/common/AppLogo.vue'
import useNotificationStore from '@/stores/notificationStore'

const form = reactive({
  fullName: '',
  email: '',
  phone: '',
  schoolName: '',
  role: '',
  students: '',
  message: ''
})
const { successToast } = useNotificationStore()

function submitDemo() {
  successToast('Demo Request Sent', 'We will contact you shortly.')
}
</script>

