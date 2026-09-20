<template>
  <AdminLayout>
    <h1 class="page-greeting">Revenus</h1>
    <p class="page-sub">Commission plateforme encaissée sur les paiements.</p>

    <p v-if="error" class="banner-error">{{ error }}</p>
    <div v-else-if="loading" class="loading-box">Chargement des revenus...</div>
    <template v-else>
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-label">Ce mois</div>
          <div class="stat-value">{{ formatKpiMoney(stats.month) }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Cette année</div>
          <div class="stat-value">{{ formatKpiMoney(stats.year) }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Paiements du mois</div>
          <div class="stat-value">{{ formatCount(stats.payments_this_month) }}</div>
        </div>
      </div>

      <div class="section-title">Revenus plateforme</div>
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

      <div class="section-title">Paiements</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Transaction</th>
              <th>Réservation</th>
              <th>Agence</th>
              <th>Montant</th>
              <th>Commission nette</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="payments.length === 0">
              <td colspan="6" class="empty-cell">Aucun paiement.</td>
            </tr>
            <tr v-for="row in payments" :key="row.id">
              <td class="mono">{{ row.transaction_id }}</td>
              <td class="mono">{{ row.reference }}</td>
              <td>{{ row.agency }}</td>
              <td class="bold">{{ formatMoney(row.amount) }}</td>
              <td class="positive">+{{ formatMoney(row.net_commission) }}</td>
              <td>
                <span :class="['pill', paymentStatus(row.status).className]">
                  {{ paymentStatus(row.status).label }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <AdminPager :meta="meta" @prev="page--" @next="page++" />
    </template>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import AdminPager from '@/components/AdminPager.vue'
import adminService from '@/services/admin'
import { formatCount, formatKpiMoney, formatMoney, paymentStatus } from '@/utils/adminFormat'

const loading = ref(true)
const error = ref('')
const payments = ref([])
const monthlyRevenue = ref([])
const stats = ref({ month: 0, year: 0, payments_this_month: 0 })
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const page = ref(1)

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

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getRevenue({ page: page.value })
    payments.value = data.payments || []
    monthlyRevenue.value = data.monthly_revenue || []
    stats.value = data.stats || stats.value
    meta.value = data.meta || meta.value
  } catch (err) {
    error.value = err.message || 'Impossible de charger les revenus.'
  } finally {
    loading.value = false
  }
}

watch(page, load)
onMounted(load)
</script>
