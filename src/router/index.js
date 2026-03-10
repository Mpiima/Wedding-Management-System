import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'
import { useAuthStore } from '@/stores/auth'

function lazy(name) {
  return () => import(`@/pages/${name}.vue`)
}

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: lazy('Login'),
    meta: { public: true }
  },
  {
    path: '/',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'Dashboard', component: lazy('Dashboard') },
      { path: 'budget', name: 'Budget', component: lazy('Budget') },
      { path: 'expenditures', name: 'Expenditures', component: lazy('Expenditures') },
      { path: 'contributions', name: 'Contributions', component: lazy('Contributions') },
      { path: 'pledges', name: 'Pledges', component: lazy('Pledges') },
      { path: 'members', name: 'Members', component: lazy('Members') },
      { path: 'group-category', name: 'GroupCategory', component: lazy('GroupCategory') },
      { path: 'roles', name: 'Roles', component: lazy('Roles') },
      { path: 'committee', name: 'Committee', component: lazy('Committee') },
      { path: 'guests', name: 'InvitedGuests', component: lazy('Guests') },
      { path: 'meetings', name: 'Meetings', component: lazy('Meetings') },
      { path: 'meeting-minutes', name: 'MeetingMinutes', component: lazy('MeetingMinutes') },
      { path: 'wedding-program', name: 'WeddingProgram', component: lazy('WeddingProgram') },
      { path: 'vendors', name: 'Vendors', component: lazy('Vendors') },
      { path: 'vendor-contracts', name: 'VendorContracts', component: lazy('VendorContracts') },
      { path: 'email-notifications', name: 'EmailNotifications', component: lazy('EmailNotifications') },
      { path: 'sms-notifications', name: 'SMSNotifications', component: lazy('SMSNotifications') },
      { path: 'media', name: 'WeddingGallery', component: lazy('WeddingGallery') },
      { path: 'upload-media', name: 'UploadMedia', component: lazy('UploadMedia') },
      { path: 'reports/financial', name: 'FinancialReports', component: lazy('ReportsFinancial') },
      { path: 'reports/contributions', name: 'ContributionsReport', component: lazy('ReportsContributions') },
      { path: 'reports/guests', name: 'GuestReport', component: lazy('ReportsGuests') },
      { path: 'settings/profile', name: 'WeddingProfile', component: lazy('SettingsProfile') },
      { path: 'settings/system', name: 'SystemSettings', component: lazy('SettingsSystem') }
    ]
  }
]

export const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to) => {
  const authStore = useAuthStore()
  const publicPages = ['/login']
  const isPublic = publicPages.includes(to.path)
  if (!authStore.isAuthenticated && !isPublic) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
  if (authStore.isAuthenticated && to.path === '/login') {
    return { path: to.query.redirect || '/' }
  }
  return true
})
