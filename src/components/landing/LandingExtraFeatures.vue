<template>
  <section class="bg-blue-50/40 py-16">
    <div class="mx-auto w-full max-w-7xl px-6">
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <article
          v-for="item in items"
          :key="item.title"
          class="rounded-2xl border border-blue-100 bg-white p-6 shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-card-hover"
        >
          <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
            <component :is="item.icon" class="h-6 w-6" />
          </div>
          <h3 class="text-lg font-semibold text-slate-900">{{ item.title }}</h3>
          <p class="mt-2 text-sm text-slate-600">{{ item.description }}</p>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { BriefcaseIcon, ChatBubbleBottomCenterTextIcon, ArchiveBoxIcon } from '@heroicons/vue/24/outline'

const items = [
  {
    icon: BriefcaseIcon,
    title: 'Staff Management',
    description: 'Manage staff roles, attendance, and payroll tasks from one secure admin workspace.'
  },
  {
    icon: ChatBubbleBottomCenterTextIcon,
    title: 'Messaging Center',
    description: 'Send timely updates to parents, students, and staff with centralized communication tools.'
  },
  {
    icon: ArchiveBoxIcon,
    title: 'Inventory Management',
    description: 'Track school inventory, procurement flow, and stock usage with clear audit history.'
  }
]
</script>

