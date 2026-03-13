<template>
  <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/90 backdrop-blur-sm p-4">
    <div class="w-full max-w-sm rounded-2xl border border-rose-100/80 bg-white p-6 shadow-xl">
      <div class="text-center mb-6">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 mb-4">
          <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h2 class="text-lg font-semibold text-wmis-text">Session locked</h2>
        <p class="text-sm text-gray-500 mt-1">You were inactive. Enter your password to continue.</p>
      </div>
      <form class="space-y-4" @submit.prevent="unlock">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Account</label>
          <input
            :value="loginName"
            type="text"
            disabled
            class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            v-model="password"
            type="password"
            placeholder="Enter your password"
            required
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm placeholder-gray-500 focus:border-rose-400 focus:ring-2 focus:ring-rose-500/20"
            autofocus
          />
        </div>
        <p v-if="errorMessage" class="text-sm text-rose-600">{{ errorMessage }}</p>
        <button
          type="submit"
          class="w-full rounded-xl bg-rose-500 py-2.5 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50"
          :disabled="loading"
        >
          {{ loading ? 'Unlocking…' : 'Unlock' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

const loginName = computed(() => {
  const p = authStore.profile
  return (p?.email || p?.username || '').trim() || 'Account'
})

const emit = defineEmits(['unlock'])

async function unlock() {
  if (!password.value.trim()) return
  errorMessage.value = ''
  loading.value = true
  try {
    const login = authStore.profile?.username || authStore.profile?.email || ''
    await authStore.userLogin({ login, password: password.value })
    if (authStore.errorMessage) {
      errorMessage.value = authStore.errorMessage
      return
    }
    password.value = ''
    emit('unlock')
  } finally {
    loading.value = false
  }
}
</script>
