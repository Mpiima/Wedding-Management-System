<template>
  <div class="flex h-screen overflow-hidden bg-wmis-page">
    <Sidebar :mobile="sidebarOpen" @close="sidebarOpen = false" />
    <Transition name="overlay">
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
        aria-hidden="true"
        @click="sidebarOpen = false"
      />
    </Transition>
    <div class="flex flex-1 flex-col min-w-0">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      <main class="flex-1 overflow-y-auto p-5 md:p-8">
        <Transition name="page" mode="out-in">
          <router-view />
        </Transition>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import Sidebar from '@/components/Sidebar.vue'
import Navbar from '@/components/Navbar.vue'

const sidebarOpen = ref(false)

watch(sidebarOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})
</script>

<style scoped>
.page-enter-active,
.page-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.page-enter-from { opacity: 0; transform: translateY(4px); }
.page-leave-to { opacity: 0; transform: translateY(-4px); }
.overlay-enter-active,
.overlay-leave-active { transition: opacity 0.2s ease; }
.overlay-enter-from,
.overlay-leave-to { opacity: 0; }
</style>
