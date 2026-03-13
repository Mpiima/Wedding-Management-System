<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-1">
      <h1 class="text-xl font-semibold text-wmis-text">Email configuration</h1>
      <p class="text-sm text-gray-500">SMTP settings for sending emails (PHPMailer)</p>
    </div>

    <div class="rounded-2xl bg-white shadow-card border border-gray-100 p-6 max-w-2xl">
      <form class="space-y-4" @submit.prevent="save">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormInput v-model="form.smtp_host" label="SMTP host" placeholder="e.g. smtp.gmail.com" />
          <FormInput v-model.number="form.smtp_port" label="SMTP port" type="number" placeholder="587" />
        </div>
        <FormInput v-model="form.smtp_username" label="SMTP username" placeholder="Leave empty if no auth" />
        <FormInput
          v-model="form.smtp_password"
          label="SMTP password"
          type="password"
          placeholder="Leave blank to keep existing password"
        />
        <p v-if="config?.smtp_password_set && !form.smtp_password" class="text-xs text-gray-500">Password is currently set. Enter a new one only to change it.</p>
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Encryption</label>
          <select
            v-model="form.encryption"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          >
            <option value="none">None</option>
            <option value="tls">TLS (STARTTLS)</option>
            <option value="ssl">SSL</option>
          </select>
        </div>
        <FormInput v-model="form.from_email" label="From email" type="email" placeholder="noreply@example.com" />
        <FormInput v-model="form.from_name" label="From name" placeholder="e.g. WMIS" />
        <div class="space-y-1">
          <label class="block text-sm font-medium text-gray-700">Always BCC (copy to)</label>
          <input
            v-model="form.bcc_emails"
            type="text"
            placeholder="e.g. pledge@zinitechnology.com (comma-separated)"
            class="block w-full rounded-xl border border-rose-100 bg-rose-50/20 px-4 py-2.5 text-sm focus:border-rose-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          />
          <p class="text-xs text-gray-500">These addresses receive a BCC copy of every outgoing email (e.g. pledge notifications).</p>
        </div>
        <p v-if="errorMessage" class="text-sm text-rose-600 bg-rose-50 rounded-xl px-4 py-2">{{ errorMessage }}</p>
        <p v-if="successMessage" class="text-sm text-green-600 bg-green-50 rounded-xl px-4 py-2">{{ successMessage }}</p>
        <div class="flex justify-end gap-2 pt-2">
          <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="saving">
            {{ saving ? 'Saving…' : 'Save configuration' }}
          </button>
        </div>
      </form>
    </div>

    <div class="rounded-2xl bg-white shadow-card border border-gray-100 p-6 max-w-2xl">
      <h2 class="text-base font-semibold text-wmis-text mb-1">Send test email</h2>
      <p class="text-sm text-gray-500 mb-4">Send a test message to verify your configuration.</p>
      <form class="flex flex-wrap items-end gap-3" @submit.prevent="sendTest">
        <div class="min-w-[200px] flex-1">
          <FormInput v-model="testToEmail" label="Send to" type="email" placeholder="Recipient email" />
        </div>
        <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-rose-600 disabled:opacity-50" :disabled="sendingTest">
          {{ sendingTest ? 'Sending…' : 'Send test' }}
        </button>
      </form>
      <p v-if="testError" class="mt-2 text-sm text-rose-600">{{ testError }}</p>
      <p v-if="testSuccess" class="mt-2 text-sm text-green-600">{{ testSuccess }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import FormInput from '@/components/FormInput.vue'
import api from '@/config/api.js'

const config = ref(null)
const form = reactive({
  smtp_host: '',
  smtp_port: 587,
  smtp_username: '',
  smtp_password: '',
  encryption: 'tls',
  from_email: '',
  from_name: '',
  bcc_emails: ''
})
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const testToEmail = ref('')
const sendingTest = ref(false)
const testError = ref('')
const testSuccess = ref('')

async function load() {
  try {
    const r = await api.get('email-config.php')
    config.value = r?.data?.data ?? null
    const d = config.value
    if (d) {
      form.smtp_host = d.smtp_host ?? ''
      form.smtp_port = d.smtp_port ?? 587
      form.smtp_username = d.smtp_username ?? ''
      form.smtp_password = ''
      form.encryption = d.encryption ?? 'tls'
      form.from_email = d.from_email ?? ''
      form.from_name = d.from_name ?? ''
      form.bcc_emails = d.bcc_emails ?? ''
      if (!testToEmail.value && d.from_email) testToEmail.value = d.from_email
    }
  } catch (_) {
    errorMessage.value = 'Failed to load configuration'
  }
}

async function save() {
  errorMessage.value = ''
  successMessage.value = ''
  saving.value = true
  try {
    const payload = {
      smtp_host: form.smtp_host.trim(),
      smtp_port: Number(form.smtp_port) || 587,
      smtp_username: form.smtp_username.trim() || null,
      encryption: form.encryption,
      from_email: form.from_email.trim(),
      from_name: form.from_name.trim() || null,
      bcc_emails: form.bcc_emails.trim() || null
    }
    if (form.smtp_password) payload.smtp_password = form.smtp_password
    await api.put('email-config.php', payload)
    successMessage.value = 'Configuration saved.'
    setTimeout(() => { successMessage.value = '' }, 3000)
    form.smtp_password = ''
    await load()
  } catch (e) {
    errorMessage.value = e?.response?.data?.error || e?.message || 'Failed to save'
  } finally {
    saving.value = false
  }
}

async function sendTest() {
  testError.value = ''
  testSuccess.value = ''
  const to = testToEmail.value.trim()
  if (!to) {
    testError.value = 'Enter a recipient email.'
    return
  }
  sendingTest.value = true
  try {
    const r = await api.post('send-test-email.php', { to_email: to })
    testSuccess.value = r?.data?.message || 'Test email sent.'
    setTimeout(() => { testSuccess.value = '' }, 5000)
  } catch (e) {
    testError.value = e?.response?.data?.error || e?.message || 'Failed to send test email'
  } finally {
    sendingTest.value = false
  }
}

onMounted(() => load())
</script>
