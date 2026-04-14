<template>
  <div class="mx-auto max-w-4xl space-y-6">
    <PageHeader title="Complete School Setup" description="Finish your school profile before using the full system." />
    <div class="card-surface">
      <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submitSetup">
        <FormInput v-model="form.logoUrl" label="Logo URL" placeholder="https://..." />
        <FormInput v-model="form.schoolMotto" label="School Motto" placeholder="Knowledge is Power" />
        <FormInput v-model="form.contactEmail" label="Contact Email" type="email" placeholder="info@school.ug" />
        <FormInput v-model="form.contactPhone" label="Contact Phone" placeholder="+256..." />
        <FormInput v-model="form.principalName" label="Principal / Headteacher" placeholder="Name" />
        <FormInput v-model="form.address" label="School Address" placeholder="District, Town" />
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">About School</label>
          <textarea v-model="form.aboutInfo" rows="4"
            class="w-full rounded-xl border border-sc-line bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
            placeholder="Brief school profile..." />
        </div>
        <div class="md:col-span-2 flex justify-end">
          <Button type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save & Continue' }}</Button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/common/PageHeader.vue'
import FormInput from '@/components/ui/FormInput.vue'
import Button from '@/components/ui/Button.vue'
import api from '@/config/api'
import { useAuthStore } from '@/stores/auth'
import useNotificationStore from '@/stores/notificationStore'

const router = useRouter()
const authStore = useAuthStore()
const { successToast, errorToast } = useNotificationStore()
const saving = ref(false)

const form = reactive({
  logoUrl: '',
  schoolMotto: '',
  aboutInfo: '',
  address: '',
  contactEmail: '',
  contactPhone: '',
  principalName: ''
})

async function submitSetup() {
  saving.value = true
  try {
    await api.post('schools/setup.php', form)
    const updatedProfile = { ...(authStore.profile || {}), setup_completed: true, ...form }
    authStore.updateProfile(updatedProfile)
    successToast('Setup Completed', 'School setup saved successfully.')
    await router.replace('/dashboard')
  } catch (e) {
    errorToast('Setup Failed', e?.response?.data?.error || e?.message || 'Could not save school setup.')
  } finally {
    saving.value = false
  }
}
</script>
