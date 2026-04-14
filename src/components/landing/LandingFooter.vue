<template>
  <footer id="contact" class="bg-slate-950 text-slate-300">
    <div class="mx-auto grid w-full max-w-7xl grid-cols-1 gap-10 px-6 py-14 md:grid-cols-2 lg:grid-cols-4">
      <div>
        <AppLogo size="md" on-dark />
        <h3 class="sr-only">SCH PRO 360</h3>
        <p class="mt-3 text-sm leading-relaxed text-slate-400">
          Modern school management software helping institutions digitize operations and improve outcomes.
        </p>
        <p class="mt-3 text-sm text-slate-400">Kampala, Uganda</p>
      </div>
      <div>
        <h4 class="text-sm font-semibold uppercase tracking-wider text-white">Links</h4>
        <ul class="mt-4 space-y-2 text-sm text-slate-400">
          <li><a href="#features" class="hover:text-white">Features</a></li>
          <li><a href="#schools" class="hover:text-white">Schools</a></li>
          <li><RouterLink to="/request-demo" class="hover:text-white">Contact</RouterLink></li>
        </ul>
      </div>
      <div id="blog">
        <h4 class="text-sm font-semibold uppercase tracking-wider text-white">Resources</h4>
        <ul class="mt-4 space-y-2 text-sm text-slate-400">
          <li><a href="#" class="hover:text-white">Documentation</a></li>
          <li><a href="#" class="hover:text-white">Help Center</a></li>
          <li><a href="#" class="hover:text-white">Blog</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-sm font-semibold uppercase tracking-wider text-white">Get Started</h4>
        <p class="mt-4 text-sm text-slate-400">Request a demo and see how SchPro360 transforms your school workflow.</p>
        <div class="mt-4 flex flex-wrap gap-2">
          <RouterLink to="/request-demo" class="btn-primary inline-flex">Request Demo</RouterLink>
          <RouterLink to="/register" class="btn-secondary inline-flex !border-slate-700 !bg-slate-900 !text-slate-200 hover:!bg-slate-800">
            Register
          </RouterLink>
        </div>
      </div>
    </div>
    <div class="border-t border-slate-800 py-4 text-center text-xs text-slate-500">
      Copyright © {{ year }} SCH PRO 360. All rights reserved.
    </div>
  </footer>
</template>

<script setup>
import AppLogo from '@/components/common/AppLogo.vue'

const year = new Date().getFullYear()
</script>

