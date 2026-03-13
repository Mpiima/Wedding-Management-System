<template>
  <div class="min-h-screen flex items-center justify-center bg-wmis-page p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <router-link to="/" class="inline-flex items-center gap-2 text-wmis-text hover:opacity-90 transition">
          <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-rose-500 to-rose-600 text-white text-xl font-display font-semibold shadow-soft">♥</span>
          <span class="font-display text-2xl font-semibold tracking-tight">WMIS</span>
        </router-link>
        <p class="mt-2 text-sm text-gray-500">Wedding Management Information System</p>
      </div>

      <div class="card-luxury p-8">
        <h1 class="font-display text-xl font-semibold text-wmis-text text-center mb-1">Welcome back</h1>
        <p class="text-sm text-gray-500 text-center mb-6">Sign in to manage your wedding</p>

        <form class="space-y-5" @submit.prevent="handleSubmit">
          <FormInput
            v-model="login"
            label="Email or username"
            type="text"
            placeholder="you@example.com or your.username"
            required
            :error="errors.login"
          />
          <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">
              Password <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              required
              class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm text-wmis-text placeholder-gray-500 transition-all duration-200 focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
            />
            <div class="flex items-center justify-between mt-1">
              <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input v-model="showPassword" type="checkbox" class="rounded border-rose-200 text-rose-500 focus:ring-rose-500/20" />
                Show password
              </label>
              <button type="button" class="text-sm text-rose-600 hover:text-rose-700 font-medium">Forgot password?</button>
            </div>
            <p v-if="errors.password" class="text-xs text-rose-500">{{ errors.password }}</p>
          </div>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input v-model="remember" type="checkbox" class="rounded border-rose-200 text-rose-500 focus:ring-rose-500/20" />
            Remember me
          </label>
          <p v-if="displayError" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">{{ displayError }}</p>
          <button
            type="submit"
            class="btn-primary w-full py-3"
            :disabled="loading"
          >
            {{ loading ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
          Don't have an account?
          <button type="button" class="text-rose-600 hover:text-rose-700 font-medium ml-1">Request access</button>
        </p>
      </div>

      <p class="mt-6 text-center text-xs text-gray-400">© WMIS — Wedding Management</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import FormInput from '@/components/FormInput.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const login = ref('')
const password = ref('')
const remember = ref(false)
const showPassword = ref(false)
const loading = ref(false)
const errors = reactive({ login: '', password: '' })

const displayError = computed(() => authStore.errorMessage)

function handleSubmit() {
  authStore.clearError()
  errors.login = ''
  errors.password = ''
  if (!login.value.trim()) {
    errors.login = 'Email or username is required.'
    return
  }
  if (!password.value) {
    errors.password = 'Password is required.'
    return
  }
  loading.value = true
  authStore.userLogin({ login: login.value.trim(), password: password.value }).then(() => {
    loading.value = false
    if (authStore.loginResponse && !authStore.errorMessage) {
      const redirect = route.query.redirect || '/'
      router.replace(redirect)
    }
  }).catch(() => {
    loading.value = false
  })
}
</script>
