<template>
  <div class="layout-shell">
    <!-- Navbar -->
    <nav class="navbar">
      <div class="container flex-between" style="height:100%">
        <RouterLink to="/" class="brand">
          <CarIcon class="brand-icon" />
          <span>GlobalRental</span>
        </RouterLink>

        <ul class="nav-list">
          <li><RouterLink to="/" class="nav-link" :class="{ active: route.path === '/' }">{{ t('nav.browse') }}</RouterLink></li>

          <!-- Guest -->
          <template v-if="!auth.isAuthenticated">
            <li><RouterLink to="/login"    class="btn btn-secondary btn-sm">{{ t('nav.login') }}</RouterLink></li>
            <li><RouterLink to="/register" class="btn btn-primary btn-sm">{{ t('nav.register') }}</RouterLink></li>
          </template>

          <!-- Client -->
          <template v-else-if="auth.isClient">
            <li>
              <RouterLink to="/client/reservations" class="nav-link"
                :class="{ active: route.path.startsWith('/client') }">
                {{ t('nav.myBookings') }}
              </RouterLink>
            </li>
            <li><span class="badge badge-active">{{ auth.user.first_name }}</span></li>
          </template>

          <!-- Agency Owner -->
          <template v-else-if="auth.isOwner">
            <li>
              <RouterLink to="/owner/dashboard" class="nav-link flex-center" style="gap:6px"
                :class="{ active: route.path.startsWith('/owner') }">
                <LayoutDashboardIcon style="width:15px;height:15px" /> {{ t('nav.dashboard') }}
              </RouterLink>
            </li>
            <li>
              <span class="badge" style="background:var(--accent-soft);color:var(--accent)">
                <LandmarkIcon style="width:13px;height:13px" />
                {{ auth.user.agency?.name || t('nav.agency') }}
              </span>
            </li>
          </template>

          <!-- Admin -->
          <template v-else-if="auth.isAdmin">
            <li>
              <RouterLink to="/admin/dashboard" class="nav-link flex-center" style="gap:6px"
                :class="{ active: route.path.startsWith('/admin') }">
                <ShieldIcon style="width:15px;height:15px" /> {{ t('nav.admin') }}
              </RouterLink>
            </li>
            <li><span class="badge badge-cancelled"><ShieldIcon style="width:12px;height:12px" /> {{ t('nav.admin') }}</span></li>
          </template>

          <!-- Switchers -->
          <li><LocaleSwitcher /></li>
          <li><ThemeSwitcher /></li>

          <!-- Logout -->
          <li v-if="auth.isAuthenticated">
            <button class="btn btn-secondary btn-sm flex-center" style="padding:6px 10px" @click="handleLogout" :disabled="loggingOut">
              <LogOutIcon style="width:15px;height:15px" />
            </button>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Page content -->
    <main class="main-content">
      <div class="container">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import ThemeSwitcher from '@/components/ThemeSwitcher.vue'
import LocaleSwitcher from '@/components/LocaleSwitcher.vue'
import {
  Car          as CarIcon,
  LogOut       as LogOutIcon,
  LayoutDashboard as LayoutDashboardIcon,
  Landmark     as LandmarkIcon,
  Shield       as ShieldIcon,
} from 'lucide-vue-next'

const { t } = useI18n()
const auth      = useAuthStore()
const route     = useRoute()
const router    = useRouter()
const loggingOut = ref(false)

async function handleLogout() {
  loggingOut.value = true
  await auth.logout()
  router.push('/login')
  loggingOut.value = false
}
</script>

<style scoped>
.layout-shell { display: flex; flex-direction: column; min-height: 100vh; }

.navbar {
  position: sticky; top: 0; z-index: 50; height: 64px;
  background: var(--headerbg);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--line);
}

.brand {
  display: flex; align-items: center; gap: 8px;
  font-family: var(--font-display); font-size: 1.25rem; font-weight: 800;
  color: var(--headerink);
}
.brand-icon { width: 26px; height: 26px; color: var(--accent); }

.nav-list { display: flex; align-items: center; gap: 20px; list-style: none; }

.nav-link {
  font-weight: 500; font-size: 0.9rem; color: var(--muted);
  transition: color var(--transition-fast);
}
.nav-link:hover, .nav-link.active { color: var(--accent); }

.main-content { flex: 1; padding: 32px 0 64px; }
</style>
