<template>
  <div class="page-shell">
    <PageHeader title="Settings" description="Global SaaS configuration and integration placeholders (mock).">
      <template #actions>
        <Button variant="secondary" @click="loadFromStore">Reset</Button>
        <Button @click="save">Save</Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <section class="rounded-2xl border border-sc-border bg-white p-6 shadow-card lg:col-span-2">
        <h3 class="text-sm font-semibold text-slate-900">Global configs</h3>
        <div class="mt-4 space-y-4">
          <FormInput v-model="form.globalConfigs.currency" label="Currency" placeholder="UGX" />
          <FormInput v-model="form.globalConfigs.timeZone" label="Time zone" placeholder="Africa/Kampala" />
          <SelectInput
            v-model="form.globalConfigs.defaultPlanId"
            label="Default plan"
            :options="planOptions"
            placeholder="Select"
          />
        </div>
      </section>

      <section class="rounded-2xl border border-sc-border bg-white p-6 shadow-card">
        <h3 class="text-sm font-semibold text-slate-900">SMS settings</h3>
        <div class="mt-4 space-y-4">
          <SelectInput
            v-model="form.smsSettings.enabled"
            label="Enabled"
            :options="boolOptions"
            placeholder="Enabled"
          />
          <FormInput v-model="form.smsSettings.provider" label="Provider" placeholder="MockSMSProvider" />
          <FormInput v-model="form.smsSettings.senderId" label="Sender ID" placeholder="SC360" />
        </div>
      </section>

      <section class="rounded-2xl border border-sc-border bg-white p-6 shadow-card lg:col-span-3">
        <h3 class="text-sm font-semibold text-slate-900">Payment integration</h3>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="rounded-2xl border border-sc-border bg-slate-50/50 p-5">
            <p class="text-sm font-semibold text-slate-900">Stripe (placeholder)</p>
            <p class="mt-1 text-sm text-slate-600">Currently {{ form.paymentIntegration.stripeEnabled ? 'enabled' : 'disabled' }}.</p>
            <div class="mt-4 flex gap-2">
              <Button
                variant="secondary"
                class="!px-3 !py-2 text-xs"
                :disabled="form.paymentIntegration.stripeEnabled"
                @click="form.paymentIntegration.stripeEnabled = true"
              >
                Enable
              </Button>
              <Button
                variant="ghost"
                class="!px-3 !py-2 text-xs"
                :disabled="!form.paymentIntegration.stripeEnabled"
                @click="form.paymentIntegration.stripeEnabled = false"
              >
                Disable
              </Button>
            </div>
          </div>

          <div class="rounded-2xl border border-sc-border bg-slate-50/50 p-5">
            <p class="text-sm font-semibold text-slate-900">Paystack (placeholder)</p>
            <p class="mt-1 text-sm text-slate-600">Currently {{ form.paymentIntegration.paystackEnabled ? 'enabled' : 'disabled' }}.</p>
            <div class="mt-4 flex gap-2">
              <Button
                variant="secondary"
                class="!px-3 !py-2 text-xs"
                :disabled="form.paymentIntegration.paystackEnabled"
                @click="form.paymentIntegration.paystackEnabled = true"
              >
                Enable
              </Button>
              <Button
                variant="ghost"
                class="!px-3 !py-2 text-xs"
                :disabled="!form.paymentIntegration.paystackEnabled"
                @click="form.paymentIntegration.paystackEnabled = false"
              >
                Disable
              </Button>
            </div>
          </div>
        </div>

        <div class="mt-4 space-y-4 sm:flex sm:items-end sm:justify-between">
          <SelectInput
            v-model="form.paymentIntegration.providerMode"
            label="Provider mode"
            :options="modeOptions"
            placeholder="Mode"
          />
          <div class="text-xs text-slate-500">
            Integration actions are placeholders until backend wiring is implemented.
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue'
import PageHeader from '@/components/common/PageHeader.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/ui/FormInput.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { useSuperAdminStore } from '@/stores/superAdmin'
import useNotificationStore from '@/stores/notificationStore'

const store = useSuperAdminStore()
const { successToast } = useNotificationStore()

const form = reactive({
  globalConfigs: {
    currency: 'UGX',
    timeZone: 'Africa/Kampala',
    defaultPlanId: 'plan-basic'
  },
  smsSettings: {
    enabled: 'true',
    provider: 'MockSMSProvider',
    senderId: 'SC360'
  },
  paymentIntegration: {
    stripeEnabled: false,
    paystackEnabled: true,
    providerMode: 'sandbox'
  }
})

const boolOptions = [
  { value: 'true', label: 'Enabled' },
  { value: 'false', label: 'Disabled' }
]

const modeOptions = [
  { value: 'sandbox', label: 'Sandbox' },
  { value: 'live', label: 'Live' }
]

const planOptions = computed(() => store.plans.map((p) => ({ value: p.id, label: p.name })))

function loadFromStore() {
  const s = store.settings || {}
  form.globalConfigs = {
    currency: s.globalConfigs?.currency || 'UGX',
    timeZone: s.globalConfigs?.timeZone || 'Africa/Kampala',
    defaultPlanId: s.globalConfigs?.defaultPlanId || 'plan-basic'
  }
  form.smsSettings = {
    enabled: String(s.smsSettings?.enabled ?? true),
    provider: s.smsSettings?.provider || 'MockSMSProvider',
    senderId: s.smsSettings?.senderId || 'SC360'
  }
  form.paymentIntegration = {
    stripeEnabled: s.paymentIntegration?.stripeEnabled ?? false,
    paystackEnabled: s.paymentIntegration?.paystackEnabled ?? true,
    providerMode: s.paymentIntegration?.providerMode || 'sandbox'
  }
}

function save() {
  store.updateSettings({
    globalConfigs: { ...form.globalConfigs },
    smsSettings: { ...form.smsSettings, enabled: form.smsSettings.enabled === 'true' },
    paymentIntegration: { ...form.paymentIntegration }
  })
  successToast('Settings Saved', 'Super admin settings were saved.')
}

onMounted(async () => {
  await store.fetchAll()
  loadFromStore()
})
</script>

