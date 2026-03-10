<template>
  <div v-if="pledge" class="pledge-card border border-rose-200 bg-gradient-to-b from-rose-50/50 to-white p-6 rounded-2xl text-center">
    <h2 class="font-display text-lg font-semibold text-wmis-text border-b border-rose-200 pb-2">Pledge Card</h2>
    <p class="mt-4 text-sm text-gray-500">Pledger</p>
    <p class="font-display text-xl font-semibold text-wmis-text">{{ pledge.member_name }}</p>
    <p class="mt-4 text-sm text-gray-500">Amount pledged</p>
    <p class="font-display text-2xl font-semibold text-rose-600">{{ formatUgx(pledge.amount_pledged) }}</p>
    <p class="mt-2 text-sm text-gray-500">Amount paid</p>
    <p class="font-display text-lg text-wmis-text">{{ formatUgx(pledge.amount_paid) }}</p>
    <p v-if="pledge.paying_date" class="mt-2 text-xs text-gray-500">Paying date: {{ formatDate(pledge.paying_date) }}</p>
    <p class="mt-6 text-xs text-gray-400">WMIS — Wedding Management Information System</p>
  </div>
</template>

<script setup>
defineProps({ pledge: { type: Object, default: null } })

function formatUgx(v) {
  return new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(Number(v) || 0)
}
function formatDate(d) {
  return new Date(d + 'Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>
