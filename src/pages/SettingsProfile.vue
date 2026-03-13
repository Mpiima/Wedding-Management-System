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
          :disabled="!canEdit"
        />
        <FormInput
          v-model="form.groom_name"
          label="Groom name"
          placeholder="Full name"
          :disabled="!canEdit"
        />
        <FormInput
          v-model="form.wedding_date"
          label="Wedding date"
          type="date"
          :disabled="!canEdit"
        />
        <FormInput
          v-model="form.venue_name"
          label="Venue name"
          placeholder="Venue"
          :disabled="!canEdit"
        />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Venue address</label>
          <textarea
            v-model="form.venue_address"
            placeholder="Address"
            rows="2"
            :disabled="!canEdit"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm text-wmis-text placeholder-gray-500 transition-all duration-200 focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 disabled:opacity-70 disabled:cursor-not-allowed"
          />
        </div>

        <!-- Wedding photos: Bride, Groom, Both -->
        <div class="space-y-4 pt-4 border-t border-rose-100">
          <p class="text-sm font-medium text-gray-700">Photos</p>
          <p class="text-xs text-gray-500 -mt-2">Click a photo box to upload or replace. Use JPEG, PNG, GIF or WebP.</p>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="space-y-2">
              <label class="block text-xs font-medium text-gray-500">Bride</label>
              <div class="relative aspect-square max-w-[160px] rounded-2xl border-2 border-dashed border-rose-200 bg-rose-50/30 overflow-hidden">
                <img v-if="weddingStore.profile?.bride_photo" :key="`bride-${photoCacheKey}-${weddingStore.profile?.bride_photo || ''}`" :src="photoUrl(weddingStore.profile.bride_photo, photoCacheKey)" alt="Bride" class="w-full h-full object-cover" />
                <span v-else class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">No photo</span>
                <span v-if="uploadingPhoto === 'bride'" class="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-sm font-medium">Updating…</span>
                <input v-if="canEdit" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="onPhotoChange('bride', $event)" />
              </div>
            </div>
            <div class="space-y-2">
              <label class="block text-xs font-medium text-gray-500">Groom</label>
              <div class="relative aspect-square max-w-[160px] rounded-2xl border-2 border-dashed border-rose-200 bg-rose-50/30 overflow-hidden">
                <img v-if="weddingStore.profile?.groom_photo" :key="`groom-${photoCacheKey}-${weddingStore.profile?.groom_photo || ''}`" :src="photoUrl(weddingStore.profile.groom_photo, photoCacheKey)" alt="Groom" class="w-full h-full object-cover" />
                <span v-else class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">No photo</span>
                <span v-if="uploadingPhoto === 'groom'" class="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-sm font-medium">Updating…</span>
                <input v-if="canEdit" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="onPhotoChange('groom', $event)" />
              </div>
            </div>
            <div class="space-y-2">
              <label class="block text-xs font-medium text-gray-500">Couple (both)</label>
              <div class="relative aspect-square max-w-[160px] rounded-2xl border-2 border-dashed border-rose-200 bg-rose-50/30 overflow-hidden">
                <img v-if="weddingStore.profile?.couple_photo" :key="`couple-${photoCacheKey}-${weddingStore.profile?.couple_photo || ''}`" :src="photoUrl(weddingStore.profile.couple_photo, photoCacheKey)" alt="Couple" class="w-full h-full object-cover" />
                <span v-else class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">No photo</span>
                <span v-if="uploadingPhoto === 'couple'" class="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-sm font-medium">Updating…</span>
                <input v-if="canEdit" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="onPhotoChange('couple', $event)" />
              </div>
            </div>
          </div>
        </div>

        <p v-if="weddingStore.errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">
          {{ weddingStore.errorMessage }}
        </p>
        <p v-if="successMessage" class="text-sm text-green-600 bg-green-50 rounded-xl px-4 py-2">
          {{ successMessage }}
        </p>

        <div class="flex justify-end gap-2 pt-4">
          <template v-if="canEdit">
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
          </template>
          <p v-else class="text-sm text-gray-500">View only. Only the wedding owner can edit these details.</p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import FormInput from '@/components/FormInput.vue'
import { useWeddingProfileStore } from '@/stores/weddingProfile'
import { useAuthStore } from '@/stores/auth'
import { weddingPhotoUrl } from '@/config/api.js'

const weddingStore = useWeddingProfileStore()
const authStore = useAuthStore()

const canEdit = computed(() => authStore.can('settings.edit') || authStore.can('*'))

function photoUrl(path, cacheKey) {
  return weddingPhotoUrl(path, cacheKey)
}

const uploadingPhoto = ref(null)
const photoCacheKey = ref(0)

async function onPhotoChange(type, event) {
  const file = event.target?.files?.[0]
  if (!file || !file.type.startsWith('image/')) return
  if (!weddingStore.profile) {
    weddingStore.errorMessage = 'Save wedding profile first, then add photos.'
    return
  }
  uploadingPhoto.value = type
  weddingStore.clearError()
  try {
    const url = await weddingStore.uploadPhoto(type, file)
    await weddingStore.updateWeddingProfile({ [type + '_photo']: url })
    photoCacheKey.value = Date.now()
    successMessage.value = type.charAt(0).toUpperCase() + type.slice(1) + ' photo updated.'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (_) {
    // error set in store
  } finally {
    uploadingPhoto.value = null
    event.target.value = ''
  }
}

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
