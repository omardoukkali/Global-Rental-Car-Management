<template>
  <AdminLayout :pending-count="pendingAgencies.length">
    <div class="anim d1">
      <h1 class="page-greeting">Tableau de bord Admin</h1>
      <p class="page-sub">Vue globale des performances de GlobalRental Maroc.</p>
    </div>

    <p v-if="error" class="banner-error">{{ error }}</p>

    <div v-if="loading" class="loading-box">Chargement des données du tableau de bord...</div>

    <template v-else>
      <div class="stats-grid anim d2">
        <div class="stat-card">
          <div class="stat-label">Utilisateurs</div>
          <div class="stat-value">{{ formatCount(stats.users) }}</div>
        </div>
        <RouterLink to="/admin/agencies" class="stat-card kpi-link" data-testid="kpi-agencies">
          <div class="stat-label">Agences actives</div>
          <div class="stat-value">{{ formatCount(stats.active_agencies) }}</div>
        </RouterLink>
        <RouterLink to="/admin/agencies/validation" class="stat-card kpi-link" data-testid="kpi-pending-agencies">
          <div class="stat-label">En attente</div>
          <div class="stat-value">{{ formatCount(pendingAgencies.length) }}</div>
        </RouterLink>
        <div class="stat-card">
          <div class="stat-label">Voitures</div>
          <div class="stat-value">{{ formatCount(stats.cars) }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Réserv. mois</div>
          <div class="stat-value">{{ formatCount(stats.reservations_this_month) }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Revenu plateforme</div>
          <div class="stat-value">{{ formatKpiMoney(stats.platform_revenue) }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Note plateforme</div>
          <div class="stat-value">{{ ratingLabel }}</div>
        </div>
      </div>

      <div class="section-title anim d3">
        <h2>
          <RouterLink to="/admin/agencies/validation" class="section-link" data-testid="link-pending-agencies">
            Agences en attente de vérification
            <span class="sb-badge orange pending-badge">{{ pendingAgencies.length }}</span>
          </RouterLink>
        </h2>
        <RouterLink to="/admin/agencies" class="see-all" data-testid="link-all-agencies">
          Toutes les agences
        </RouterLink>
      </div>

      <AdminPendingAgencies
        class="anim d3"
        :agencies="pendingAgencies"
        :busy-id="busyId"
        @approve="approveAgency"
        @reject="rejectAgency"
      />

      <div class="grid-content">
        <div class="anim d4">
          <div class="section-title">
            <h2>Dernières réservations</h2>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Client</th>
                  <th>Agence</th>
                  <th>Montant</th>
                  <th>Commission</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="recentReservations.length === 0">
                  <td colspan="6" class="empty-cell">Aucune réservation pour le moment.</td>
                </tr>
                <tr v-for="row in recentReservations" :key="row.id">
                  <td class="mono">{{ row.reference }}</td>
                  <td class="bold">{{ row.client }}</td>
                  <td>{{ row.agency }}</td>
                  <td class="bold">{{ formatMoney(row.amount) }}</td>
                  <td :class="Number(row.commission) > 0 ? 'positive' : 'strike'">
                    {{ Number(row.commission) > 0 ? '+' + formatMoney(row.commission) : '0' }}
                  </td>
                  <td>
                    <span :class="['pill', statusMeta(row.status).className]">
                      {{ statusMeta(row.status).label }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="anim d5">
          <div class="section-title"><h2>Revenus plateforme</h2></div>
          <div class="chart-card">
            <div class="chart-flex">
              <div v-for="month in revenueChart" :key="month.label + month.month" class="chart-col">
                <div class="bar-bg">
                  <div :class="['bar', { active: month.active }]" :style="{ height: month.height }"></div>
                </div>
                <div :class="['chart-lbl', { active: month.active }]">{{ month.label }}</div>
              </div>
            </div>
          </div>

          <div class="section-title" style="margin-top:32px;"><h2>Signalements récents</h2></div>

          <div v-if="reports.length === 0" class="report-item">
            <p>Aucun litige ouvert pour le moment.</p>
          </div>

          <div v-for="report in reports" :key="report.id" class="report-item">
            <div class="report-head">
              <span class="pill pill-red">En cours</span>
              <span class="report-time">{{ relativeLabel(report.created_at) }}</span>
            </div>
            <h4>{{ report.title }}</h4>
            <p>{{ report.car }} • Signalé par {{ report.client }}</p>
          </div>
        </div>
      </div>
    </template>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/components/AdminLayout.vue'
import AdminPendingAgencies from '@/components/AdminPendingAgencies.vue'
import adminService from '@/services/admin'

const loading = ref(true)
const error = ref('')
const busyId = ref(null)
const stats = ref({
  users: 0,
  active_agencies: 0,
  cars: 0,
  reservations_this_month: 0,
  platform_revenue: 0,
  avg_rating: null,
})
const pendingAgencies = ref([])
const recentReservations = ref([])
const monthlyRevenue = ref([])
const reports = ref([])

const ratingLabel = computed(() => {
  if (stats.value.avg_rating == null) return '—'
  return `${Number(stats.value.avg_rating).toFixed(1)} ★`
})

const revenueChart = computed(() => {
  const rows = monthlyRevenue.value
  const max = Math.max(...rows.map((row) => Number(row.revenue) || 0), 1)
  const currentMonth = new Date().getMonth() + 1

  return rows.map((row) => ({
    ...row,
    height: `${Math.max(8, Math.round((Number(row.revenue) / max) * 100))}%`,
    active: Number(row.month) === currentMonth,
  }))
})

function formatCount(value) {
  return new Intl.NumberFormat('fr-FR').format(Number(value) || 0)
}

function formatMoney(value) {
  return new Intl.NumberFormat('fr-FR').format(Math.round(Number(value) || 0))
}

function formatKpiMoney(value) {
  const amount = Number(value) || 0
  if (amount >= 1000) {
    const thousands = amount / 1000
    const label = thousands >= 10
      ? Math.round(thousands).toString()
      : thousands.toFixed(1).replace(/\.0$/, '')
    return `${label}k`
  }
  return formatCount(Math.round(amount))
}

function statusMeta(status) {
  const map = {
    pending: { label: 'Attente', className: 'pill-yellow' },
    confirmed: { label: 'Confirmé', className: 'pill-green' },
    picked_up: { label: 'En cours', className: 'pill-blue' },
    completed: { label: 'Terminé', className: 'pill-gray' },
    cancelled: { label: 'Annulé', className: 'pill-red' },
    disputed: { label: 'Signalé', className: 'pill-red' },
    rejected: { label: 'Rejeté', className: 'pill-red' },
  }
  return map[status] || { label: status || '—', className: 'pill-gray' }
}

function relativeLabel(iso) {
  if (!iso) return ''
  const then = new Date(iso)
  const hours = Math.round((Date.now() - then.getTime()) / 3600000)
  if (hours < 1) return "À l'instant"
  if (hours < 24) return `Il y a ${hours}h`
  const days = Math.round(hours / 24)
  if (days === 1) return 'Hier'
  return `Il y a ${days}j`
}

async function loadDashboard() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getDashboard()
    stats.value = {
      users: data.stats?.users ?? 0,
      active_agencies: data.stats?.active_agencies ?? 0,
      cars: data.stats?.cars ?? 0,
      reservations_this_month: data.stats?.reservations_this_month ?? 0,
      platform_revenue: data.stats?.platform_revenue ?? 0,
      avg_rating: data.stats?.avg_rating ?? null,
    }
    pendingAgencies.value = data.pending_agencies || []
    recentReservations.value = data.recent_reservations || []
    monthlyRevenue.value = data.monthly_revenue || []
    reports.value = data.reports || []
  } catch (err) {
    error.value = err.message || 'Impossible de charger le tableau de bord.'
  } finally {
    loading.value = false
  }
}

async function approveAgency(id) {
  error.value = ''
  busyId.value = id
  try {
    await adminService.approveAgency(id)
    pendingAgencies.value = pendingAgencies.value.filter((agency) => agency.id !== id)
    stats.value.active_agencies = Number(stats.value.active_agencies || 0) + 1
  } catch (err) {
    error.value = err.message || 'Validation impossible.'
  } finally {
    busyId.value = null
  }
}

async function rejectAgency(id) {
  error.value = ''
  busyId.value = id
  try {
    await adminService.rejectAgency(id)
    pendingAgencies.value = pendingAgencies.value.filter((agency) => agency.id !== id)
  } catch (err) {
    error.value = err.message || 'Rejet impossible.'
  } finally {
    busyId.value = null
  }
}

onMounted(loadDashboard)
</script>

<style scoped>
.page-greeting {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  color: #0A0A0B;
  margin-bottom: 4px;
}

.page-sub {
  font-size: 0.88rem;
  color: #7A7A7D;
  margin-bottom: 36px;
}

.banner-error {
  background: #FEF2F2;
  color: #B91C1C;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 20px;
}

.loading-box {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 40px 20px;
  text-align: center;
  color: #7A7A7D;
  font-size: 0.9rem;
}

.section-title {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #0A0A0B;
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.section-title h2 {
  display: flex;
  align-items: center;
  margin: 0;
}

.pending-badge {
  margin-left: 8px;
  vertical-align: middle;
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
  background: #E8E8EA;
  color: #0A0A0B;
}

.sb-badge.orange {
  background: #FFEDD5;
  color: #C2410C;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 16px;
  margin-bottom: 36px;
}

.stat-card {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 20px 20px 16px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
}

.kpi-link {
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  transition: border-color .15s, box-shadow .15s;
}

.kpi-link:hover {
  border-color: #0A0A0B;
}

.section-link {
  color: inherit;
  text-decoration: none;
  display: flex;
  align-items: center;
}

.section-link:hover {
  text-decoration: underline;
}

.see-all {
  font-size: 0.75rem;
  font-weight: 700;
  color: #7A7A7D;
  text-decoration: none;
}

.see-all:hover {
  color: #0A0A0B;
}

.stat-label {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #7A7A7D;
  margin-bottom: 6px;
}

.stat-value {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: #0A0A0B;
  line-height: 1;
}

.grid-content {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 32px;
}

.table-wrap {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
  margin-bottom: 36px;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead th {
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

tbody td {
  padding: 14px 20px;
  border-bottom: 1px solid #F2F2F3;
  font-size: 0.8rem;
  color: #0A0A0B;
  vertical-align: middle;
}

.empty-cell {
  text-align: center;
  color: #7A7A7D !important;
}

.mono {
  font-family: monospace;
  color: #7A7A7D;
}

.bold {
  font-weight: 700;
}

.positive {
  color: #047857;
  font-weight: 700;
}

.strike {
  color: #7A7A7D;
  text-decoration: line-through;
}

.pill {
  display: inline-flex;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.pill-green { background: #ECFDF5; color: #047857; }
.pill-blue { background: #E0E7FF; color: #3730A3; }
.pill-yellow { background: #FEFCE8; color: #CA8A04; }
.pill-gray { background: #F2F2F3; color: #3D3D3F; }
.pill-red { background: #FEF2F2; color: #B91C1C; }

.chart-card {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 24px;
  margin-bottom: 24px;
}

.chart-flex {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  height: 140px;
  padding-top: 10px;
}

.chart-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  height: 100%;
}

.bar-bg {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.bar {
  width: 24px;
  background: #E8E8EA;
  border-radius: 4px 4px 0 0;
  transition: background .2s;
}

.bar.active { background: #0A0A0B; }

.chart-lbl {
  font-size: 0.65rem;
  font-weight: 700;
  color: #7A7A7D;
}

.chart-lbl.active { color: #0A0A0B; }

.report-item {
  padding: 14px;
  border: 1px solid #F2F2F3;
  border-radius: 12px;
  margin-bottom: 12px;
  background: #fff;
}

.report-head {
  display: flex;
  justify-content: space-between;
  margin-bottom: 6px;
}

.report-time {
  font-size: 0.7rem;
  color: #7A7A7D;
}

.report-item h4 {
  font-size: 0.85rem;
  font-weight: 800;
  margin-bottom: 4px;
}

.report-item p {
  font-size: 0.75rem;
  color: #7A7A7D;
  margin-bottom: 0;
}

.anim {
  opacity: 0;
  animation: fadeUp 0.55s cubic-bezier(0.16,1,0.3,1) forwards;
}

.d1 { animation-delay: .06s; }
.d2 { animation-delay: .14s; }
.d3 { animation-delay: .22s; }
.d4 { animation-delay: .30s; }
.d5 { animation-delay: .38s; }

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(18px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 1280px) {
  .stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .grid-content { grid-template-columns: 1fr; }
}

@media (max-width: 980px) {
  .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 640px) {
  .stats-grid { grid-template-columns: 1fr; }
}
</style>
