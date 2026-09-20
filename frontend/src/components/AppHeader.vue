<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAgencyStore } from '@/stores/agency'

const auth = useAuthStore()
const agencyStore = useAgencyStore()
const menuOpen = ref(false)

let route = null
if (typeof useRoute === 'function') {
  try {
    route = useRoute()
  } catch {
    route = null
  }
}

const path = computed(() => route?.path || '')
const role = computed(() => auth.user?.role || null)

watch(path, () => {
  menuOpen.value = false
})

const homeTo = computed(() => {
  if (!auth.isAuthenticated) return '/login'
  if (role.value === 'agency') {
    return agencyStore.loaded && !agencyStore.isApproved ? '/agency/pending' : '/agency/dashboard'
  }
  if (role.value === 'admin') return '/admin/dashboard'
  return '/myreservations'
})

function isActive(match) {
  if (!path.value) return false
  if (typeof match === 'function') return match(path.value)
  return path.value === match || path.value.startsWith(`${match}/`)
}

function isAdminAgenciesNav() {
  if (!path.value) return false
  if (path.value === '/admin/agencies') return true
  if (path.value.startsWith('/admin/agencies/validation')) return false
  return path.value.startsWith('/admin/agencies/')
}

function linkClass(active) {
  return active
    ? 'font-bold text-[#0F172A] whitespace-nowrap'
    : 'font-semibold text-slate-500 hover:text-slate-800 transition-colors whitespace-nowrap'
}
</script>

<template>
  <header class="sticky top-0 z-50 bg-white border-b border-slate-200" data-testid="app-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
      <RouterLink
        :to="homeTo"
        class="font-bricolage font-extrabold text-lg text-[#0F172A] tracking-tight shrink-0"
      >
        GlobalRental
      </RouterLink>

      <button
        type="button"
        class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-50"
        :aria-expanded="menuOpen"
        aria-label="Ouvrir le menu"
        data-testid="app-header-menu"
        @click="menuOpen = !menuOpen"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <nav
        class="absolute md:static left-0 right-0 top-16 md:top-auto bg-white md:bg-transparent border-b md:border-0 border-slate-200 px-4 md:px-0 py-3 md:py-0 flex-col md:flex-row items-start md:items-center gap-3 text-sm"
        :class="menuOpen ? 'flex' : 'hidden md:flex'"
        aria-label="Navigation principale"
      >
        <template v-if="!auth.isAuthenticated">
          <RouterLink to="/login" :class="linkClass(isActive('/login'))">Se connecter</RouterLink>
          <RouterLink to="/register" :class="linkClass(isActive('/register'))">S’inscrire</RouterLink>
        </template>

        <template v-else-if="role === 'agency' && agencyStore.loaded && !agencyStore.isApproved">
          <RouterLink to="/agency/pending" :class="linkClass(isActive('/agency/pending'))">Ma demande</RouterLink>
          <RouterLink to="/agency/profile" :class="linkClass(isActive('/agency/profile'))">Agence</RouterLink>
          <RouterLink to="/agency/settings" :class="linkClass(isActive('/agency/settings'))">Paramètres</RouterLink>
          <RouterLink to="/logout" class="font-semibold text-rose-600 hover:text-rose-700 whitespace-nowrap">Déconnexion</RouterLink>
        </template>

        <template v-else-if="role === 'agency'">
          <RouterLink to="/agency/dashboard" :class="linkClass(isActive('/agency/dashboard'))">Tableau de bord</RouterLink>
          <RouterLink to="/agency/cars" :class="linkClass(isActive('/agency/cars'))">Flotte</RouterLink>
          <RouterLink to="/agency/pickup" :class="linkClass(isActive('/agency/pickup'))">Pickup</RouterLink>
          <RouterLink to="/agency/return" :class="linkClass(isActive('/agency/return'))">Retour</RouterLink>
          <RouterLink to="/agency/locations" :class="linkClass(isActive('/agency/points') || isActive('/agency/locations'))">Points</RouterLink>
          <RouterLink to="/agency/profile" :class="linkClass(isActive('/agency/profile'))">Agence</RouterLink>
          <RouterLink to="/agency/settings" :class="linkClass(isActive('/agency/settings'))">Paramètres</RouterLink>
          <RouterLink to="/logout" class="font-semibold text-rose-600 hover:text-rose-700 whitespace-nowrap">Déconnexion</RouterLink>
        </template>

        <template v-else-if="role === 'admin'">
          <RouterLink
            to="/admin/dashboard"
            :class="linkClass(isActive('/admin/dashboard') || path === '/admin')"
          >
            Tableau de bord
          </RouterLink>
          <RouterLink
            to="/admin/agencies"
            :class="linkClass(isAdminAgenciesNav())"
          >
            Agences
          </RouterLink>
          <RouterLink
            to="/admin/agencies/validation"
            :class="linkClass(isActive('/admin/agencies/validation'))"
          >
            Validation agences
          </RouterLink>
          <RouterLink to="/logout" class="font-semibold text-rose-600 hover:text-rose-700 whitespace-nowrap">Déconnexion</RouterLink>
        </template>

        <template v-else>
          <RouterLink to="/myreservations" :class="linkClass(isActive('/myreservations'))">Mes réservations</RouterLink>
          <RouterLink
            to="/reservations/new"
            :class="linkClass(isActive((p) => p === '/reservations/new' || p.startsWith('/cars/')))"
          >
            Réserver
          </RouterLink>
          <RouterLink
            to="/payments"
            :class="linkClass(isActive('/payments') || isActive((p) => p.includes('/pay')))"
          >
            Paiements
          </RouterLink>
          <RouterLink to="/logout" class="font-semibold text-rose-600 hover:text-rose-700 whitespace-nowrap">Déconnexion</RouterLink>
        </template>
      </nav>
    </div>
  </header>
</template>
