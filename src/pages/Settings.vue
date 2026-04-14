<template>
  <div class="page-shell max-w-3xl">
    <PageHeader title="Settings" description="Tenant-based configuration (SchPro360).">
      <template #actions>
        <Button variant="secondary" @click="reload">Refresh</Button>
        <Button @click="save">Save</Button>
      </template>
    </PageHeader>

    <div class="space-y-6">
      <section class="card-surface space-y-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Organization</h2>
        <FormInput v-model="form.school_name" label="School name" placeholder="e.g., Kampala Horizon School" />
        <FormInput v-model="form.logo_url" label="Logo URL" placeholder="https://..." />
      </section>

      <section class="card-surface space-y-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Advanced config</h2>
        <FormInput
          v-model="form.config_text"
          label="Config (JSON)"
          placeholder='{"currency":"UGX"}'
          hint="Stored in config_json (mock)."
        />
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import FormInput from '@/components/ui/FormInput.vue'
import Button from '@/components/ui/Button.vue'
import { useSettingsStore } from '@/stores/settingsStore'
import useNotificationStore from '@/stores/notificationStore'

const store = useSettingsStore()
const { successToast, errorToast } = useNotificationStore()

const form = reactive({
  school_name: '',
  logo_url: '',
  config_text: ''
})

const loading = computed(() => store.loading)

function applyFromStore() {
  const data = store.settings
  form.school_name = data?.school_name || ''
  form.logo_url = data?.logo_url || ''
  form.config_text = data?.config ? JSON.stringify(data.config, null, 2) : ''
}

async function reload() {
  await store.fetchSettings()
  applyFromStore()
}

function safeParseConfig() {
  const text = String(form.config_text || '').trim()
  if (!text) return null
  try {
    const obj = JSON.parse(text)
    return obj
  } catch {
    errorToast('Invalid JSON', 'Config JSON is invalid.')
    return null
  }
}

async function save() {
  const config = safeParseConfig()
  if (config === null && String(form.config_text || '').trim() !== '') return

  const payload = {
    school_name: form.school_name || null,
    logo_url: form.logo_url || null,
  }
  if (config) payload.config = config

  await store.updateSettings(payload)
  successToast('Settings Saved', 'Configuration updated successfully.')
}

onMounted(reload)
</script>
