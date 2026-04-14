<template>
  <section id="features" class="mx-auto w-full max-w-7xl px-6 py-16">
    <div class="mx-auto max-w-3xl text-center">
      <p class="ui-label text-brand-600">All-in-One System</p>
      <h2 class="mt-2 text-3xl font-bold text-slate-900">Everything your school needs in one platform</h2>
      <p class="mt-3 text-slate-600">Built for school leaders, finance teams, and academic staff with a clean and fast workflow.</p>
    </div>

    <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2">
      <article
        v-for="f in features"
        :key="f.title"
        class="overflow-hidden rounded-2xl border border-sc-border bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-card-hover"
      >
        <img :src="f.image" :alt="f.title" class="h-44 w-full object-cover" />
        <div class="p-5">
          <h3 class="text-lg font-semibold text-slate-900">{{ f.title }}</h3>
          <p class="mt-2 text-sm text-slate-600">{{ f.description }}</p>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
const features = [
  {
    title: 'Student Profile Management',
    description: 'Maintain complete student records, enrollment history, and performance profiles in one secure place.',
    image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200&auto=format&fit=crop&q=80'
  },
  {
    title: 'Fees Payment Management',
    description: 'Track invoices, payments, balances, and reminders with a reliable and transparent school finance workflow.',
    image: 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&auto=format&fit=crop&q=80'
  },
  {
    title: 'Academics Management',
    description: 'Manage classes, attendance, marks, and results while giving teachers and students real-time visibility.',
    image: 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=1200&auto=format&fit=crop&q=80'
  },
  {
    title: 'Budget & Expenses',
    description: 'Plan school budgets, monitor expenditures, and improve accountability with structured reporting dashboards.',
    image: 'https://images.unsplash.com/photo-1554224154-26032ffc0d07?w=1200&auto=format&fit=crop&q=80'
  }
]
</script>

