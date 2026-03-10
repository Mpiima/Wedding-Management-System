<template>
  <div v-if="guest" class="invitation-card border-2 border-rose-200 bg-gradient-to-b from-rose-50/30 to-white p-8 rounded-2xl text-center">
    <h2 class="font-display text-xl font-semibold text-rose-800 tracking-tight">You are cordially invited</h2>
    <p v-if="profile && (profile.bride_name || profile.groom_name)" class="mt-2 text-sm text-gray-600">
      to the wedding of<br />
      <span class="font-semibold text-wmis-text">{{ profile.bride_name || 'Bride' }} & {{ profile.groom_name || 'Groom' }}</span>
    </p>
    <p v-else class="mt-2 text-sm text-gray-600">to our wedding celebration</p>
    <p class="mt-6 font-display text-2xl font-semibold text-wmis-text">{{ guest.name }}</p>
    <p v-if="guest.side" class="mt-1 text-sm text-gray-500">Side: {{ guest.side }}</p>
    <p v-if="profile && profile.wedding_date" class="mt-6 text-sm text-gray-600">{{ formatDate(profile.wedding_date) }}</p>
    <p class="mt-8 text-xs text-gray-400">WMIS — Wedding Management Information System</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useWeddingProfileStore } from '@/stores/weddingProfile'

const props = defineProps({ guest: { type: Object, default: null } })

const weddingProfile = useWeddingProfileStore()
const profile = computed(() => weddingProfile.profile)

function formatDate(d) {
  if (!d) return ''
  return new Date(d + 'Z').toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}
</script>
