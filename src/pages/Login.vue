<template>
  <div class="flex min-h-screen items-center justify-center bg-sc-page p-6 sm:p-8">
    <div class="w-full max-w-[26rem]">
      <div class="card-surface px-8 py-9 sm:px-9">
        <div class="mb-6 flex flex-col items-center">
          <AppLogo size="lg" />
          <h1 class="mt-4 text-center font-display text-lg font-semibold tracking-tight text-brand-700">Sign in</h1>
        </div>
        <!-- <p class="mb-8 mt-2 text-center text-sm leading-relaxed text-slate-500">
          Staff: use your school email. Students: use the portal username (e.g. <span class="font-mono">ADM</span> + admission
          number) and password from your school.
        </p> -->

        <form class="space-y-6" @submit.prevent="doLogin">
          <FormInput
            v-model="form.email"
            label="username"
            type="text"
            placeholder="you@school.org or ADM12345"
            required
            :error="errors.email"
          />

          <div class="space-y-2">
            <label class="block text-sm font-medium leading-snug text-slate-700">
              Password <span class="text-brand-600">*</span>
            </label>
            <input
              v-model="form.password"
              :type="show ? 'text' : 'password'"
              placeholder="••••••••"
              required
              class="block h-11 w-full rounded-xl border border-sc-line bg-white px-3.5 text-sm text-slate-900 shadow-sm transition-all duration-200 ease-out focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/15"
            />
            <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-slate-600">
              <input v-model="show" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500/30" />
              Show password
            </label>
            <p v-if="errors.password" class="text-xs text-rose-600">{{ errors.password }}</p>
          </div>
          <p v-if="errorMessage" class="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-700">{{ errorMessage }}</p>
          <button type="submit" class="btn-primary mt-2 w-full !py-3" :disabled="buttonLoader">
            {{ buttonLoader ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>

        <div class="mt-6 border-t border-sc-border pt-5">
          <p class="mb-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Demo Credentials</p>
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <button
              type="button"
              class="rounded-xl border border-sc-line bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
              :disabled="buttonLoader"
              @click="loginAsDemo('superAdmin')"
            >
              Super Admin Demo
            </button>
            <!-- <button
              type="button"
              class="rounded-xl border border-sc-line bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
              :disabled="buttonLoader"
              @click="loginAsDemo('admin')"
            >
              Admin Demo
            </button>
            <button
              type="button"
              class="rounded-xl border border-sc-line bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
              :disabled="buttonLoader"
              @click="loginAsDemo('student')"
            >
              Student Portal Demo
            </button>
            <button
              type="button"
              class="rounded-xl border border-sc-line bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
              :disabled="buttonLoader"
              @click="loginAsDemo('parent')"
            >
              Parent Portal Demo
            </button> -->
          </div>
        </div>
      </div>

      <p class="mt-10 text-center text-xs leading-relaxed text-slate-400">© SCH PRO 360 — All rights reserved</p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import useNotificationStore from '@/stores/notificationStore'
import FormInput from '@/components/ui/FormInput.vue'
import AppLogo from '@/components/common/AppLogo.vue'
import router from '@/router/router.js'

const notificationStore = useNotificationStore()
const authStore = useAuthStore()
const { errorMessage: authErrorMessage } = storeToRefs(authStore)

const route = useRoute()

const errorMessage = ref(null)
const buttonLoader = ref(false)
const show = ref(false)
const scope = ref(null)

const form = reactive({
  email: null,
  password: null
})

const errors = reactive({ email: '', password: '' })

/**
 * Optional: mirror entityStore.getConfigureLanding() + landPageResponse watch:
 * set title / logoUrl from API, then persist landingPageTitle + logo to localStorage.
 */

watch(
  () => authStore.loginResponse,
  async (data) => {
    if (!data || data.error) return

    const inner = data.data || {}
    if (inner.campuses != null) {
      localStorage.setItem('Campus', JSON.stringify(inner.campuses))
    }
    if (inner.Schools != null) {
      const school = JSON.stringify(inner.Schools)
      localStorage.setItem('SchoolInfo', school)
      localStorage.setItem('currentSchoolInfo', school)
    }

    scope.value = Array.isArray(inner.scopes) ? inner.scopes : []

    await authStore.userProfile()

    const user = authStore.profile
    if (!user) {
      buttonLoader.value = false
      return
    }

    const r = router.resolve({
      path: resolvePostLoginPath(user, scope.value)
    })
    window.location.assign(r.href)
    notificationStore.successToast('Success', 'Login successfully')
    buttonLoader.value = false
  }
)

function resolvePostLoginPath(user, scopes) {
  const redirectFromQuery = typeof route.query.redirect === 'string' ? route.query.redirect : null
  if (redirectFromQuery) return redirectFromQuery

  const t = String(user?.type || '')
  const sc = Array.isArray(scopes) ? scopes : []
  const role = String(user?.role || '').toLowerCase()

  if (Number(user?.tenant_id) === 0 || role.includes('super')) {
    return '/super-admin/dashboard'
  }
  if (user?.setup_completed === false) {
    return '/school/setup'
  }
  if (t === 'Student' || role.includes('student')) {
    return '/portal/dashboard'
  }
  if (role.includes('parent')) {
    return '/parent/dashboard'
  }
  if (t === 'User' && sc.length === 0) {
    return '/dashboard'
  }
  if (sc.length > 0) {
    localStorage.setItem('schoolSelectStatus', 'NO')
    return '/academics/teachers'
  }
  return '/dashboard'
}

watch(
  () => authErrorMessage.value,
  async (data) => {
    if (data) {
      const msg =
        typeof data === 'string'
          ? data
          : data?.message ?? data?.error ?? 'Login failed'
      errorMessage.value = msg
      buttonLoader.value = false
      notificationStore.errorToast('Error', String(msg))
    }
  }
)

function doLogin() {
  errors.email = ''
  errors.password = ''
  errorMessage.value = null
  authStore.clearError()

  if (!String(form.email || '').trim()) {
    errors.email = 'Email or portal username is required.'
    notificationStore.warningToast('Validation', 'Email or portal username is required.')
    return
  }
  if (!form.password) {
    errors.password = 'Password is required.'
    notificationStore.warningToast('Validation', 'Password is required.')
    return
  }

  buttonLoader.value = true
  authStore.userLogin({ email: form.email, password: form.password })
}

async function loginAsDemo(type) {
  const map = {
    superAdmin: {
      email: 'superadmin@schpro360.local',
      password: 'demo123',
      tenantId: 0,
      redirect: '/super-admin/dashboard'
    },
    admin: {
      email: 'admin@schpro360.local',
      password: 'demo123',
      tenantId: 1,
      redirect: '/dashboard'
    },
    student: {
      email: 'student@schpro360.local',
      password: 'demo123',
      tenantId: 1,
      redirect: '/portal/dashboard'
    },
    parent: {
      email: 'parent@schpro360.local',
      password: 'demo123',
      tenantId: 1,
      redirect: '/parent/dashboard'
    }
  }
  const selected = map[type]
  if (!selected) return

  form.email = selected.email
  form.password = selected.password

  errorMessage.value = null
  authStore.clearError()
  buttonLoader.value = true

  await authStore.userLogin({ email: selected.email, password: selected.password })

  if (authStore.errorMessage) {
    buttonLoader.value = false
    return
  }

  /** Successful API login: loginResponse watcher performs navigation. */
}
</script>
