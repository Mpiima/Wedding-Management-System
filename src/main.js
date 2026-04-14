import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Notifications from '@kyvg/vue3-notification'
import App from './App.vue'
import router from './router/router'
import './main.css'

const app = createApp(App)
app.use(createPinia())
app.use(Notifications)
app.use(router)
app.mount('#app')
