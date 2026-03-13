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
    <LockScreen v-if="locked" @unlock="handleUnlock" />
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useWeddingProfileStore } from '@/stores/weddingProfile'
import { useIdleLock } from '@/composables/useIdleLock'
import Sidebar from '@/components/Sidebar.vue'
import Navbar from '@/components/Navbar.vue'
import LockScreen from '@/components/LockScreen.vue'

const sidebarOpen = ref(false)
const authStore = useAuthStore()
const locked = ref(false)
const { start: startIdle, stop: stopIdle, reset: resetIdle } = useIdleLock(() => {
  locked.value = true
})

function handleUnlock() {
  locked.value = false
  resetIdle()
}

watch(() => authStore.isAuthenticated, (isAuth) => {
  if (isAuth) {
    startIdle()
  } else {
    stopIdle()
    locked.value = false
  }
}, { immediate: true })

watch(sidebarOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => {
  if (authStore.isAuthenticated) startIdle()
  if (authStore.isAuthenticated) {
    useWeddingProfileStore().fetchWeddingProfile().catch(() => {})
  }
})
onUnmounted(() => stopIdle())
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
