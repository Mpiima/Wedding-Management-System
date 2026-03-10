<template>
  <div v-if="pledge" class="receipt border border-gray-200 bg-white p-6 rounded-xl text-left">
    <h2 class="font-display text-lg font-semibold text-wmis-text border-b border-gray-200 pb-2">Payment Receipt</h2>
    <div class="mt-4 space-y-1 text-sm">
      <p><span class="text-gray-500">Pledger:</span> <strong>{{ pledge.member_name }}</strong></p>
      <p><span class="text-gray-500">Total pledged:</span> {{ formatUgx(pledge.amount_pledged) }}</p>
      <p><span class="text-gray-500">Total paid:</span> {{ formatUgx(pledge.amount_paid) }}</p>
    </div>
    <div v-if="payments && payments.length" class="mt-4 border-t border-gray-100 pt-4">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Payment history</p>
      <table class="mt-2 w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b border-gray-100">
            <th class="pb-1">Date</th>
            <th class="pb-1 text-right">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in payments" :key="p.id" class="border-b border-gray-50">
            <td class="py-1">{{ formatDate(p.paid_at) }}</td>
            <td class="py-1 text-right">{{ formatUgx(p.amount) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p class="mt-6 text-xs text-gray-400">WMIS — Wedding Management Information System</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ pledge: { type: Object, default: null } })

const payments = computed(() => (props.pledge && props.pledge.payments) || [])

function formatUgx(v) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(Number(v) || 0)
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d + 'Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>
