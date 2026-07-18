import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/',                  component: () => import('@/pages/Landing.vue'),          name: 'landing' },
  { path: '/cars/:id',          component: () => import('@/pages/CarDetail.vue'),         name: 'car.detail' },
  { path: '/login',             component: () => import('@/pages/Login.vue'),             name: 'login',    meta: { guestOnly: true } },
  { path: '/register',          component: () => import('@/pages/Register.vue'),          name: 'register', meta: { guestOnly: true } },
  { path: '/client/reservations', component: () => import('@/pages/ClientDashboard.vue'), name: 'client.dashboard', meta: { requiresAuth: true, role: 'client' } },
  { path: '/owner/dashboard',   component: () => import('@/pages/OwnerDashboard.vue'),   name: 'owner.dashboard',  meta: { requiresAuth: true, role: 'agency_owner' } },
  { path: '/admin/dashboard',   component: () => import('@/pages/AdminDashboard.vue'),   name: 'admin.dashboard',  meta: { requiresAuth: true, role: 'admin' } },
  { path: '/:pathMatch(.*)*',   redirect: '/' },
]

const router = createRouter({
  history: createWebHistory('/app/'),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.guestOnly && auth.isAuthenticated) {
    // redirect to role-appropriate home
    if (auth.isAdmin)  return { name: 'admin.dashboard' }
    if (auth.isOwner)  return { name: 'owner.dashboard' }
    return { name: 'landing' }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return { name: 'landing' }
  }
})

export default router
