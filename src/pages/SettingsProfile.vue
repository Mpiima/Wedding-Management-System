<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Wedding Profile</h1>
      <p class="text-sm text-gray-500">Couple details, wedding date, and venue information</p>
    </div>

    <div class="rounded-2xl bg-white shadow-card border border-gray-100 p-6 max-w-2xl">
      <form class="space-y-4" @submit.prevent="handleSubmit">
        <FormInput
          v-model="form.bride_name"
          label="Bride name"
          placeholder="Full name"
        />
        <FormInput
          v-model="form.groom_name"
          label="Groom name"
          placeholder="Full name"
        />
        <FormInput
          v-model="form.wedding_date"
          label="Wedding date"
          type="date"
        />
        <FormInput
          v-model="form.venue_name"
          label="Venue name"
          placeholder="Venue"
        />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Venue address</label>
          <textarea
            v-model="form.venue_address"
            placeholder="Address"
            rows="2"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm text-wmis-text placeholder-gray-500 transition-all duration-200 focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          />
        </div>

        <p v-if="weddingStore.errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">
          {{ weddingStore.errorMessage }}
        </p>
        <p v-if="successMessage" class="text-sm text-green-600 bg-green-50 rounded-xl px-4 py-2">
          {{ successMessage }}
        </p>

        <div class="flex justify-end gap-2 pt-4">
          <button
            type="button"
            class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            @click="resetForm"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50"
            :disabled="saving"
          >
            {{ saving ? 'Saving…' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import FormInput from '@/components/FormInput.vue'
import { useWeddingProfileStore } from '@/stores/weddingProfile'

const weddingStore = useWeddingProfileStore()

const form = reactive({
  bride_name: '',
  groom_name: '',
  wedding_date: '',
  venue_name: '',
  venue_address: ''
})

const saving = ref(false)
const successMessage = ref('')

function resetForm() {
  weddingStore.clearError()
  successMessage.value = ''
  if (weddingStore.profile) {
    form.bride_name = weddingStore.profile.bride_name || ''
    form.groom_name = weddingStore.profile.groom_name || ''
    form.wedding_date = weddingStore.profile.wedding_date || ''
    form.venue_name = weddingStore.profile.venue_name || ''
    form.venue_address = weddingStore.profile.venue_address || ''
  } else {
    form.bride_name = ''
    form.groom_name = ''
    form.wedding_date = ''
    form.venue_name = ''
    form.venue_address = ''
  }
}

function fillFormFromProfile() {
  if (!weddingStore.profile) return
  form.bride_name = weddingStore.profile.bride_name || ''
  form.groom_name = weddingStore.profile.groom_name || ''
  form.wedding_date = weddingStore.profile.wedding_date || ''
  form.venue_name = weddingStore.profile.venue_name || ''
  form.venue_address = weddingStore.profile.venue_address || ''
}

async function handleSubmit() {
  weddingStore.clearError()
  successMessage.value = ''
  saving.value = true
  const payload = {
    bride_name: form.bride_name.trim() || null,
    groom_name: form.groom_name.trim() || null,
    wedding_date: form.wedding_date || null,
    venue_name: form.venue_name.trim() || null,
    venue_address: form.venue_address.trim() || null
  }
  try {
    if (weddingStore.profile) {
      await weddingStore.updateWeddingProfile(payload)
      successMessage.value = 'Wedding profile updated.'
    } else {
      await weddingStore.createWeddingProfile(payload)
      successMessage.value = 'Wedding profile created.'
    }
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (_) {
    // error already set in store
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    await weddingStore.fetchWeddingProfile()
    fillFormFromProfile()
  } catch (_) {
    fillFormFromProfile()
  }
})

watch(() => weddingStore.profile, (p) => {
  if (p) fillFormFromProfile()
}, { deep: true })
</script>
