<template>
  <section id="schools" class="bg-white py-16">
    <div class="mx-auto w-full max-w-7xl px-6">
      <h2 class="text-center text-2xl font-bold text-slate-900">Trusted by Top Schools</h2>
      <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div
          v-for="logo in logos"
          :key="logo"
          class="flex h-16 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-500"
        >
          {{ logo }}
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const logos = ['Greenfield High', 'Blue Ridge Academy', 'Scholars Hub', 'Prime College', 'Future Minds', 'Starlight School']
</script>

