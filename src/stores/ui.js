import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useUIStore = defineStore('ui', () => {
  const sidebarOpen = ref(false)
  const notifications = ref([
    { id: 'n1', title: 'System ready', detail: 'Mock data loaded', time: 'Just now' },
    { id: 'n2', title: 'Reminder', detail: 'Review attendance records', time: '1h ago' }
  ])

  const notificationCount = computed(() => notifications.value.length)

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function setSidebarOpen(open) {
    sidebarOpen.value = open
  }

  function dismissNotification(id) {
    notifications.value = notifications.value.filter((n) => n.id !== id)
  }

  return {
    sidebarOpen,
    notifications,
    notificationCount,
    toggleSidebar,
    setSidebarOpen,
    dismissNotification
  }
})
