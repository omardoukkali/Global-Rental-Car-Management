<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import adminService from '@/services/admin'

const props = defineProps({
  pendingCount: { type: Number, default: 0 },
})

const auth = useAuthStore()
const fetchedPending = ref(null)

let route = null
if (typeof useRoute === 'function') {
  try {
    route = useRoute()
  } catch {
    route = null
  }
}

const path = computed(() => route?.path || '')
const badgeCount = computed(() => fetchedPending.value ?? props.pendingCount)

const displayName = computed(() => {
  const user = auth.user
  if (!user) return 'Admin GlobalRental'
  const name = [user.first_name, user.last_name].filter(Boolean).join(' ')
  return name || 'Admin GlobalRental'
})

const initials = computed(() => {
  const user = auth.user
  const a = (user?.first_name?.[0] || 'A') + (user?.last_name?.[0] || 'D')
  return a.toUpperCase()
})

function isActive(match) {
  if (!path.value) return false
  return path.value === match || path.value.startsWith(`${match}/`)
}

function isAgenciesNav() {
  if (!path.value) return false
  if (path.value === '/admin/agencies') return true
  if (path.value.startsWith('/admin/agencies/validation')) return false
  return path.value.startsWith('/admin/agencies/')
}

watch(() => props.pendingCount, (value) => {
  fetchedPending.value = value
})

onMounted(async () => {
  try {
    const data = await adminService.getAgencies({ status: 'pending' })
    fetchedPending.value = (data.agencies || []).length
  } catch {
    fetchedPending.value = props.pendingCount
  }
})
</script>

<template>
  <div class="admin-shell">
    <div class="layout">
      <aside class="sidebar">
        <div class="sb-profile">
          <div class="sb-avatar">{{ initials }}</div>
          <div>
            <div class="sb-name">{{ displayName }}</div>
            <div class="sb-verified">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              </svg>
              Super Admin
            </div>
          </div>
        </div>

        <nav class="sb-nav" aria-label="Navigation admin">
          <RouterLink
            to="/admin/dashboard"
            class="sb-link"
            :class="{ active: isActive('/admin/dashboard') || path === '/admin' }"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Tableau de bord
          </RouterLink>
          <RouterLink
            to="/admin/agencies"
            class="sb-link"
            :class="{ active: isAgenciesNav() }"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="3" width="20" height="18" rx="2"/><line x1="8" y1="21" x2="8" y2="3"/><line x1="16" y1="21" x2="16" y2="3"/><line x1="2" y1="9" x2="22" y2="9"/><line x1="2" y1="15" x2="22" y2="15"/></svg>
            Agences
          </RouterLink>
          <RouterLink
            to="/admin/agencies/validation"
            class="sb-link"
            :class="{ active: isActive('/admin/agencies/validation') }"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            En attente
            <span class="sb-badge orange">{{ badgeCount }}</span>
          </RouterLink>
          <RouterLink
            to="/admin/users"
            class="sb-link"
            :class="{ active: isActive('/admin/users') }"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Utilisateurs
          </RouterLink>
          <RouterLink
            to="/admin/cars"
            class="sb-link"
            :class="{ active: isActive('/admin/cars') }"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><path d="M9 17H5m0 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/><path d="M13 17h5m0 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/></svg>
            Véhicules
          </RouterLink>
          <RouterLink
            to="/admin/reservations"
            class="sb-link"
            :class="{ active: isActive('/admin/reservations') }"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            Réservations
          </RouterLink>
          <RouterLink
            to="/admin/revenue"
            class="sb-link"
            :class="{ active: isActive('/admin/revenue') }"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Revenus
          </RouterLink>
          <RouterLink
            to="/admin/reviews"
            class="sb-link"
            :class="{ active: isActive('/admin/reviews') }"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Avis & Signalements
          </RouterLink>

          <div class="sb-separator"></div>

          <RouterLink
            to="/admin/settings"
            class="sb-link"
            :class="{ active: isActive('/admin/settings') }"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Paramètres
          </RouterLink>

          <div class="sb-separator"></div>

          <RouterLink to="/logout" class="sb-link sb-logout">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Se déconnecter
          </RouterLink>
        </nav>
      </aside>

      <main class="main admin-page">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.admin-shell {
  --bg: #FAFAF9;
  --surface: #FFFFFF;
  --ink: #0A0A0B;
  --ink-secondary: #3D3D3F;
  --ink-muted: #7A7A7D;
  --border: #E8E8EA;
  --border-soft: #F2F2F3;
  --accent: #1A1A1C;
  --sidebar-w: 260px;
  --radius: 18px;
  --shadow-sm: 0 1px 4px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--ink);
  min-height: 100vh;
  overflow-x: hidden;
  box-sizing: border-box;
}

.layout {
  display: flex;
  align-items: stretch;
  min-height: calc(100vh - 4rem);
}

.sidebar {
  position: sticky;
  top: 4rem;
  left: 0;
  width: var(--sidebar-w);
  min-width: var(--sidebar-w);
  height: calc(100vh - 4rem);
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  padding: 28px 16px;
  overflow-y: auto;
  z-index: 10;
  box-sizing: border-box;
}

.sb-profile {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 8px;
  padding: 20px 16px;
  border-radius: var(--radius);
  background: var(--bg);
  border: 1px solid var(--border-soft);
  margin-bottom: 28px;
}

.sb-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--ink);
  color: #fff;
  font-family: 'Bricolage Grotesque', sans-serif;
  font-weight: 800;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sb-name {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--ink);
}

.sb-verified {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.65rem;
  font-weight: 700;
  color: #D97706;
  background: #FEF3C7;
  padding: 3px 8px;
  border-radius: 999px;
}

.sb-verified svg {
  width: 12px;
  height: 12px;
  stroke: currentColor;
  fill: none;
  stroke-width: 2.5;
}

.sb-nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}

.sb-link {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 11px 14px;
  border-radius: 12px;
  font-size: 0.83rem;
  font-weight: 600;
  color: var(--ink-secondary);
  text-decoration: none;
  cursor: pointer;
  border: none;
  background: transparent;
  width: 100%;
  text-align: left;
  box-sizing: border-box;
}

.sb-link:hover {
  background: var(--border-soft);
  color: var(--ink);
}

.sb-link.active {
  background: var(--ink);
  color: #fff;
}

.sb-link.active svg {
  stroke: #fff;
}

.sb-link svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  fill: none;
  stroke-width: 2;
  flex-shrink: 0;
}

.sb-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 800;
  background: var(--border);
  color: var(--ink);
}

.sb-badge.orange {
  background: #FFEDD5;
  color: #C2410C;
}

.sb-separator {
  height: 1px;
  background: var(--border-soft);
  margin: 12px 0;
}

.sb-logout {
  color: var(--ink-muted) !important;
}

.sb-logout:hover {
  background: #FEF2F2 !important;
  color: #B91C1C !important;
}

.main {
  flex: 1;
  min-width: 0;
  padding: 40px 44px 60px;
  box-sizing: border-box;
}

@media (max-width: 980px) {
  .layout { display: block; }

  .sidebar {
    position: static;
    width: 100%;
    min-width: 100%;
    height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border);
  }

  .main {
    width: 100%;
    padding: 32px 20px 50px;
  }
}

@media (max-width: 640px) {
  .sidebar { padding: 20px 12px; }
  .main { padding: 24px 16px 40px; }
}
</style>

<style>
.admin-page .page-greeting {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  color: #0A0A0B;
  margin-bottom: 4px;
}

.admin-page .page-sub {
  font-size: 0.88rem;
  color: #7A7A7D;
  margin-bottom: 24px;
}

.admin-page .banner-error {
  background: #FEF2F2;
  color: #B91C1C;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 20px;
}

.admin-page .banner-ok {
  background: #ECFDF5;
  color: #047857;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 20px;
}

.admin-page .loading-box,
.admin-page .empty-cell {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 40px 20px;
  text-align: center;
  color: #7A7A7D;
  font-size: 0.9rem;
}

.admin-page .empty-cell {
  padding: 18px 20px;
  border-radius: 0;
  border: 0;
}

.admin-page .filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 18px;
}

.admin-page .filter-row .form-input,
.admin-page .filter-row select {
  max-width: 260px;
  height: 42px;
  border-radius: 12px;
  border: 1px solid #E8E8EA;
  padding: 0 12px;
  background: #fff;
  font-size: 0.85rem;
}

.admin-page .table-wrap {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
  overflow-x: auto;
}

.admin-page table {
  width: 100%;
  border-collapse: collapse;
}

.admin-page thead th {
  text-align: left;
  padding: 14px 20px;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #7A7A7D;
  background: #FAFAF9;
  border-bottom: 1px solid #F2F2F3;
}

.admin-page tbody td {
  padding: 14px 20px;
  border-bottom: 1px solid #F2F2F3;
  font-size: 0.8rem;
  color: #0A0A0B;
  vertical-align: middle;
}

.admin-page .mono { font-family: monospace; color: #7A7A7D; }
.admin-page .bold { font-weight: 700; }
.admin-page .positive { color: #047857; font-weight: 700; }

.admin-page .pill {
  display: inline-flex;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.admin-page .pill-green { background: #ECFDF5; color: #047857; }
.admin-page .pill-blue { background: #E0E7FF; color: #3730A3; }
.admin-page .pill-yellow { background: #FEFCE8; color: #CA8A04; }
.admin-page .pill-gray { background: #F2F2F3; color: #3D3D3F; }
.admin-page .pill-red { background: #FEF2F2; color: #B91C1C; }

.admin-page .pager {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 16px;
  font-size: 0.8rem;
  color: #7A7A7D;
}

.admin-page .pager button {
  border: 1px solid #E8E8EA;
  background: #fff;
  border-radius: 10px;
  padding: 8px 12px;
  font-weight: 700;
  cursor: pointer;
}

.admin-page .pager button:disabled {
  opacity: 0.45;
  cursor: default;
}

.admin-page .stats-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.admin-page .stat-card {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 20px;
}

.admin-page .stat-label {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #7A7A7D;
  margin-bottom: 6px;
}

.admin-page .stat-value {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.6rem;
  font-weight: 800;
}

.admin-page .section-title {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.1rem;
  font-weight: 800;
  margin: 28px 0 16px;
}

.admin-page .settings-card {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 28px;
  max-width: 560px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

.admin-page .settings-card .form-label { display: block; margin-bottom: 6px; }
.admin-page .settings-card .form-input { width: 100%; margin-bottom: 16px; }

.admin-page .chart-card {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 24px;
  margin-bottom: 24px;
}

.admin-page .chart-flex {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  height: 140px;
}

.admin-page .chart-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  height: 100%;
}

.admin-page .bar-bg {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.admin-page .bar {
  width: 24px;
  background: #E8E8EA;
  border-radius: 4px 4px 0 0;
}

.admin-page .bar.active { background: #0A0A0B; }

.admin-page .chart-lbl {
  font-size: 0.65rem;
  font-weight: 700;
  color: #7A7A7D;
}

.admin-page .chart-lbl.active { color: #0A0A0B; }

.admin-page .report-item {
  padding: 14px;
  border: 1px solid #F2F2F3;
  border-radius: 12px;
  margin-bottom: 12px;
  background: #fff;
}

.admin-page .report-item h4 {
  font-size: 0.85rem;
  font-weight: 800;
  margin: 6px 0 4px;
}

.admin-page .report-item p {
  font-size: 0.75rem;
  color: #7A7A7D;
  margin: 0;
}

@media (max-width: 980px) {
  .admin-page .stats-row { grid-template-columns: 1fr; }
}
</style>

