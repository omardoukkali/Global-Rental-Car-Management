import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAgencyStore } from '@/stores/agency'
import AgencySettings from '@/pages/AgencySettings.vue'
import AgencyProfile from '@/pages/AgencyProfile.vue'
import AgencyLocations from '@/pages/AgencyLocations.vue'
import AgencyPending from '@/pages/AgencyPending.vue'
import AdminAgencyValidation from '@/pages/AdminAgencyValidation.vue'
import AdminAgencies from '@/pages/AdminAgencies.vue'
import AdminAgencyDetail from '@/pages/AdminAgencyDetail.vue'
import AdminDashboard from '@/pages/AdminDashboard.vue'
import AdminUsers from '@/pages/AdminUsers.vue'
import AdminCars from '@/pages/AdminCars.vue'
import AdminReservations from '@/pages/AdminReservations.vue'
import AdminRevenue from '@/pages/AdminRevenue.vue'
import AdminReviews from '@/pages/AdminReviews.vue'
import AdminSettings from '@/pages/AdminSettings.vue'
import AgencyCars from '@/pages/AgencyCars.vue'
import CarForm from '@/pages/CarForm.vue'
import AgencyDashboard from '@/pages/AgencyDashboard.vue'
import ReservationForm from '@/pages/ReservationForm.vue'
import ClientReservations from '@/pages/ClientReservations.vue'
import AgencyPickupConfirm from '@/pages/AgencyPickupConfirm.vue'
import AgencyReturnConfirm from '@/pages/AgencyReturnConfirm.vue'
import PaymentHistory from '@/pages/PaymentHistory.vue'
import PaymentCheckout from '@/pages/PaymentCheckout.vue'

const routes = [{
        path: '/',
        name: 'home',
        redirect: '/login'
    },
    {
        path: '/login',
        name: 'login',
        component: () =>
            import ('@/pages/Login.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/register',
        name: 'register',
        component: () =>
            import ('@/pages/Register.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/forgot-password',
        name: 'forgotPassword',
        component: () =>
            import ('@/pages/ForgotPassword.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/reset-password',
        name: 'resetPassword',
        component: () =>
            import ('@/pages/ResetPassword.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/logout',
        name: 'logout',
        component: () =>
            import ('@/pages/Logout.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/pending',
        name: 'AgencyPending',
        component: AgencyPending,
        meta: { requiresAuth: true, allowPendingAgency: true }
    },
    {
        path: '/agency/settings',
        name: 'AgencySettings',
        component: AgencySettings,
        meta: { requiresAuth: true, allowPendingAgency: true }
    },
    {
        path: '/agency/profile',
        name: 'AgencyProfile',
        component: AgencyProfile,
        meta: { requiresAuth: true, allowPendingAgency: true }
    },
    {
        path: '/agency/locations',
        name: 'AgencyLocations',
        component: AgencyLocations,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/points',
        redirect: '/agency/locations',
    },
    {
        path: '/admin',
        redirect: '/admin/dashboard',
    },
    {
        path: '/admin/dashboard',
        name: 'AdminDashboard',
        component: AdminDashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/agencies',
        name: 'AdminAgencies',
        component: AdminAgencies,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/agencies/validation',
        name: 'AdminAgencyValidation',
        component: AdminAgencyValidation,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/agencies/:agencyId',
        name: 'AdminAgencyDetail',
        component: AdminAgencyDetail,
        props: true,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/users',
        name: 'AdminUsers',
        component: AdminUsers,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/cars',
        name: 'AdminCars',
        component: AdminCars,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/reservations',
        name: 'AdminReservations',
        component: AdminReservations,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/revenue',
        name: 'AdminRevenue',
        component: AdminRevenue,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/reviews',
        name: 'AdminReviews',
        component: AdminReviews,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/settings',
        name: 'AdminSettings',
        component: AdminSettings,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/cars',
        name: 'AgencyCars',
        component: AgencyCars,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/cars/new',
        name: 'AgencyCarCreate',
        component: CarForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/cars/:id/edit',
        name: 'AgencyCarEdit',
        component: CarForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/dashboard',
        name: 'AgencyDashboard',
        component: AgencyDashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/pickup',
        name: 'AgencyPickupConfirm',
        component: AgencyPickupConfirm,
        meta: { requiresAuth: true }
    },
    {
        path: '/agency/return',
        name: 'AgencyReturnConfirm',
        component: AgencyReturnConfirm,
        meta: { requiresAuth: true }
    },
    {
        path: '/reservations/new',
        name: 'ReservationCreate',
        component: ReservationForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/cars/:carId/reserve',
        name: 'CarReserve',
        component: ReservationForm,
        props: true,
        meta: { requiresAuth: true }
    },
    {
        path: '/myreservations',
        name: 'ClientReservations',
        component: ClientReservations,
        meta: { requiresAuth: true }
    },
    {
        path: '/payments',
        name: 'PaymentHistory',
        component: PaymentHistory,
        meta: { requiresAuth: true }
    },
    {
        path: '/reservations/:id/pay',
        name: 'PaymentCheckout',
        component: PaymentCheckout,
        meta: { requiresAuth: true }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

function homeForRole(user) {
    const role = user?.role
    if (role === 'agency') return { name: 'AgencyDashboard' }
    if (role === 'admin') return { name: 'AdminDashboard' }
    return { name: 'ClientReservations' }
}

/**
 * Agencies that are not approved yet only get the waiting screen,
 * their profile and their settings. Everything else under /agency
 * needs an approved agency (the API answers 403 otherwise).
 */
async function agencyGate(to) {
    if (!to.path.startsWith('/agency')) return null

    const agencyStore = useAgencyStore()
    if (!agencyStore.loaded) {
        await agencyStore.fetchProfile()
    }

    // Could not load the profile (network): let the page handle it
    if (!agencyStore.loaded) return null

    const approved = agencyStore.isApproved

    if (approved && to.name === 'AgencyPending') {
        return { name: 'AgencyDashboard' }
    }

    if (!approved && !to.meta.allowPendingAgency) {
        return { name: 'AgencyPending' }
    }

    return null
}

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore()
    const isAuthenticated = auth.isAuthenticated

    if (to.path === '/' && isAuthenticated) {
        next(homeForRole(auth.user))
        return
    }

    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } })
        return
    }

    if (to.meta.guestOnly && isAuthenticated) {
        next(homeForRole(auth.user))
        return
    }

    if (isAuthenticated && auth.user?.role === 'agency') {
        const redirect = await agencyGate(to)
        if (redirect) {
            next(redirect)
            return
        }
    }

    next()
})

export default router
