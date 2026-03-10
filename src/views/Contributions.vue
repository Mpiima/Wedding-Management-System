<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Contributions</h1>
        <p class="text-sm text-slate-500">
          Track monetary gifts, pledges, and thank-you status.
        </p>
      </div>
      <button
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-pink-500 text-white text-sm font-medium shadow-sm hover:bg-pink-600"
        @click="openContributionModal()"
      >
        ➕ Add contribution
      </button>
    </div>

    <!-- Summary cards -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="rounded-2xl border border-slate-100 bg-white p-4 space-y-1">
        <p class="text-xs text-slate-400 uppercase tracking-[0.15em]">Total received</p>
        <p class="text-lg font-semibold text-emerald-600">{{ formatCurrency(totalReceived) }}</p>
      </div>
      <div class="rounded-2xl border border-slate-100 bg-white p-4 space-y-1">
        <p class="text-xs text-slate-400 uppercase tracking-[0.15em]">Pledged</p>
        <p class="text-lg font-semibold text-blue-600">{{ formatCurrency(totalPledged) }}</p>
      </div>
      <div class="rounded-2xl border border-slate-100 bg-white p-4 space-y-1">
        <p class="text-xs text-slate-400 uppercase tracking-[0.15em]">Thank-you sent</p>
        <p class="text-lg font-semibold text-slate-800">{{ thankYouSent }} / {{ contributions.length }}</p>
      </div>
    </section>

    <!-- Table -->
    <section class="rounded-2xl border border-slate-100 bg-white overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50/80 text-xs text-slate-400 uppercase tracking-[0.12em]">
            <tr>
              <th class="px-4 py-3">Contributor</th>
              <th class="px-4 py-3">Amount</th>
              <th class="px-4 py-3">Type</th>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Thank-you</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in contributions"
              :key="item.id"
              class="border-t border-slate-50 hover:bg-slate-50/70"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="h-8 w-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-xs font-semibold">
                    {{ item.initials }}
                  </span>
                  <div>
                    <p class="text-sm font-medium text-slate-800">{{ item.contributor }}</p>
                    <p class="text-xs text-slate-400">{{ item.relation }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-sm font-medium text-slate-800">
                {{ formatCurrency(item.amount) }}
              </td>
              <td class="px-4 py-3 text-xs">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full border"
                  :class="item.type === 'Cash' ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-blue-100 bg-blue-50 text-blue-600'"
                >
                  {{ item.type }}
                </span>
              </td>
              <td class="px-4 py-3 text-xs text-slate-500">
                {{ item.date }}
              </td>
              <td class="px-4 py-3 text-xs">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full border"
                  :class="item.thankYouSent ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-amber-100 bg-amber-50 text-amber-600'"
                >
                  {{ item.thankYouSent ? 'Sent' : 'Pending' }}
                </span>
              </td>
              <td class="px-4 py-3 text-xs text-right space-x-1 whitespace-nowrap">
                <button
                  class="px-2 py-1 rounded-md border border-slate-200 text-slate-500 hover:bg-slate-50"
                  @click="openContributionModal(item)"
                >
                  Edit
                </button>
                <button
                  class="px-2 py-1 rounded-md border border-rose-100 text-rose-500 hover:bg-rose-50"
                  @click="removeContribution(item.id)"
                >
                  Remove
                </button>
              </td>
            </tr>
            <tr v-if="!contributions.length">
              <td colspan="6" class="px-4 py-6 text-center text-xs text-slate-400">
                No contributions recorded yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Contribution modal -->
    <transition name="fade">
      <div
        v-if="contributionModalOpen"
        class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 px-4"
      >
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl p-5 space-y-4">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-sm font-semibold text-slate-800">
                {{ editingContribution ? 'Edit contribution' : 'Add contribution' }}
              </h2>
              <p class="text-xs text-slate-400">Record gift or pledge.</p>
            </div>
            <button
              class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 text-slate-400"
              @click="closeContributionModal"
            >
              ✕
            </button>
          </div>

          <form class="space-y-3" @submit.prevent="saveContribution">
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Contributor name</label>
              <input
                v-model="contributionForm.contributor"
                type="text"
                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
              />
              <p v-if="contributionErrors.contributor" class="mt-1 text-[11px] text-rose-500">
                {{ contributionErrors.contributor }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Relation</label>
              <input
                v-model="contributionForm.relation"
                type="text"
                placeholder="Family, friend, colleague..."
                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
              />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Amount</label>
                <input
                  v-model.number="contributionForm.amount"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
                />
                <p v-if="contributionErrors.amount" class="mt-1 text-[11px] text-rose-500">
                  {{ contributionErrors.amount }}
                </p>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
                <select
                  v-model="contributionForm.type"
                  class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
                >
                  <option value="Cash">Cash</option>
                  <option value="Pledge">Pledge</option>
                  <option value="Transfer">Transfer</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Date</label>
                <input
                  v-model="contributionForm.date"
                  type="date"
                  class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
                />
              </div>
              <div class="flex items-end">
                <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                  <input
                    v-model="contributionForm.thankYouSent"
                    type="checkbox"
                    class="rounded border-slate-300 text-pink-500 focus:ring-pink-200"
                  />
                  Thank-you sent
                </label>
              </div>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">Notes</label>
              <textarea
                v-model="contributionForm.notes"
                rows="2"
                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-pink-200 focus:border-pink-300"
              ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs text-slate-600 hover:bg-slate-50"
                @click="closeContributionModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="px-4 py-2 rounded-lg bg-pink-500 text-white text-xs font-medium hover:bg-pink-600"
              >
                Save
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'

const contributions = ref([
  {
    id: 1,
    contributor: 'Uncle James',
    initials: 'UJ',
    relation: 'Family',
    amount: 500,
    type: 'Cash',
    date: '2026-02-15',
    thankYouSent: true,
    notes: ''
  },
  {
    id: 2,
    contributor: 'Sarah & Mike',
    initials: 'SM',
    relation: 'Friends',
    amount: 200,
    type: 'Transfer',
    date: '2026-03-01',
    thankYouSent: false,
    notes: 'Bank transfer ref #8821'
  }
])

const totalReceived = computed(() =>
  contributions.value
    .filter((c) => c.type !== 'Pledge')
    .reduce((sum, c) => sum + (c.amount || 0), 0)
)
const totalPledged = computed(() =>
  contributions.value
    .filter((c) => c.type === 'Pledge')
    .reduce((sum, c) => sum + (c.amount || 0), 0)
)
const thankYouSent = computed(() =>
  contributions.value.filter((c) => c.thankYouSent).length
)

const formatCurrency = (value) =>
  new Intl.NumberFormat('en-UG', { style: 'currency', currency: 'UGX', maximumFractionDigits: 0 }).format(value || 0)

const contributionModalOpen = ref(false)
const editingContribution = ref(null)

const contributionForm = reactive({
  id: null,
  contributor: '',
  relation: '',
  amount: 0,
  type: 'Cash',
  date: new Date().toISOString().slice(0, 10),
  thankYouSent: false,
  notes: ''
})

const contributionErrors = reactive({
  contributor: '',
  amount: ''
})

const openContributionModal = (item = null) => {
  if (item) {
    Object.assign(contributionForm, item)
    editingContribution.value = item
  } else {
    contributionForm.id = null
    contributionForm.contributor = ''
    contributionForm.relation = ''
    contributionForm.amount = 0
    contributionForm.type = 'Cash'
    contributionForm.date = new Date().toISOString().slice(0, 10)
    contributionForm.thankYouSent = false
    contributionForm.notes = ''
    editingContribution.value = null
  }
  contributionErrors.contributor = ''
  contributionErrors.amount = ''
  contributionModalOpen.value = true
}

const closeContributionModal = () => {
  contributionModalOpen.value = false
}

const validateContribution = () => {
  contributionErrors.contributor = contributionForm.contributor ? '' : 'Name is required.'
  contributionErrors.amount =
    contributionForm.amount != null && contributionForm.amount >= 0
      ? ''
      : 'Valid amount is required.'
  return !contributionErrors.contributor && !contributionErrors.amount
}

const saveContribution = () => {
  if (!validateContribution()) return
  if (editingContribution.value) {
    const index = contributions.value.findIndex((c) => c.id === editingContribution.value.id)
    if (index !== -1) {
      contributions.value[index] = {
        ...contributionForm,
        initials: contributionForm.contributor
          .split(' ')
          .map((p) => p[0])
          .join('')
          .toUpperCase()
          .slice(0, 2)
      }
    }
  } else {
    const newId = Math.max(0, ...contributions.value.map((c) => c.id)) + 1
    contributions.value.push({
      ...contributionForm,
      id: newId,
      initials: contributionForm.contributor
        .split(' ')
        .map((p) => p[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    })
  }
  contributionModalOpen.value = false
}

const removeContribution = (id) => {
  contributions.value = contributions.value.filter((c) => c.id !== id)
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
