<template>
  <section id="home" class="relative overflow-hidden bg-gradient-to-br from-brand-50 via-white to-brand-100/50">
    <div class="absolute -left-12 top-16 h-40 w-40 rounded-full bg-brand-200/50 blur-3xl" />
    <div class="absolute -right-12 bottom-12 h-56 w-56 rounded-full bg-brand-500/15 blur-3xl" />

    <div class="mx-auto grid w-full max-w-7xl grid-cols-1 gap-12 px-6 py-20 lg:grid-cols-2 lg:items-center">
      <div class="space-y-6">
        <p class="inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-brand-700">
          Best School Management Software
        </p>
        <h1 class="text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">
          Digitize Your School Operations
        </h1>
        <p class="max-w-xl text-base leading-relaxed text-slate-600">
          SCH PRO 360 helps schools manage admissions, academics, finance, staff, and communication from one elegant cloud platform.
        </p>
        <div class="flex flex-wrap items-center gap-3">
          <RouterLink to="/register" class="btn-primary">Get Started</RouterLink>
          <a href="#features" class="btn-secondary">Explore Features</a>
        </div>
      </div>

      <div class="relative">
        <div class="rounded-2xl border border-brand-100 bg-white p-4 shadow-soft-lg transition duration-300 hover:scale-[1.01]">
          <img
            src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=1200&auto=format&fit=crop&q=80"
            alt="Dashboard preview"
            class="h-[22rem] w-full rounded-xl object-cover"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
</script>

