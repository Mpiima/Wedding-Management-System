<template>
  <header class="sticky top-0 z-40 border-b border-sc-border/80 bg-white/95 backdrop-blur-md">
    <nav class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-6">
      <a href="#home" class="flex items-center gap-2">
        <AppLogo size="md" />
      </a>

      <ul class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
        <li><a href="#home" class="transition hover:text-brand-500">Home</a></li>
        <li><a href="#features" class="transition hover:text-brand-500">Features</a></li>
        <li><a href="#schools" class="transition hover:text-brand-500">Schools</a></li>
        <li><a href="#contact" class="transition hover:text-brand-500">Contact</a></li>
        <li><a href="#blog" class="transition hover:text-brand-500">Blog</a></li>
      </ul>

      <div class="hidden items-center gap-2 md:flex">
        <RouterLink to="/register" class="btn-secondary !h-9 !px-4">Register</RouterLink>
        <RouterLink to="/request-demo" class="btn-primary !h-9 !px-4">Request Demo</RouterLink>
      </div>

      <button
        type="button"
        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-sc-line text-slate-600 md:hidden"
        @click="open = !open"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </nav>

    <div v-if="open" class="border-t border-sc-border/80 bg-white px-6 py-4 md:hidden">
      <div class="space-y-3 text-sm font-medium text-slate-700">
        <a href="#home" class="block" @click="open = false">Home</a>
        <a href="#features" class="block" @click="open = false">Features</a>
        <a href="#schools" class="block" @click="open = false">Schools</a>
        <a href="#contact" class="block" @click="open = false">Contact</a>
        <a href="#blog" class="block" @click="open = false">Blog</a>
        <RouterLink to="/register" class="btn-secondary mt-2 inline-flex !h-9 !px-4" @click="open = false">Register</RouterLink>
        <RouterLink to="/request-demo" class="btn-primary mt-2 inline-flex !h-9 !px-4" @click="open = false">Request Demo</RouterLink>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue'
import AppLogo from '@/components/common/AppLogo.vue'

const open = ref(false)
</script>

