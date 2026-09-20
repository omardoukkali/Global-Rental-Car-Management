<template>
  <AdminLayout>
    <h1 class="page-greeting">Avis & Signalements</h1>
    <p class="page-sub">Notes des clients et litiges ouverts sur les réservations.</p>

    <p v-if="error" class="banner-error">{{ error }}</p>
    <div v-else-if="loading" class="loading-box">Chargement des avis...</div>
    <template v-else>
      <div class="section-title">Signalements ({{ reports.length }})</div>
      <div v-if="reports.length === 0" class="report-item">
        <p>Aucun litige ouvert pour le moment.</p>
      </div>
      <div v-for="report in reports" :key="report.id" class="report-item">
        <span class="pill pill-red">En cours</span>
        <h4>{{ report.title }} · {{ report.reference }}</h4>
        <p>{{ report.car }} • Signalé par {{ report.client }}</p>
      </div>

      <div class="section-title">Avis clients</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Client</th>
              <th>Véhicule</th>
              <th>Agence</th>
              <th>Note voiture</th>
              <th>Note agence</th>
              <th>Commentaire</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="reviews.length === 0">
              <td colspan="6" class="empty-cell">Aucun avis.</td>
            </tr>
            <tr v-for="review in reviews" :key="review.id">
              <td class="bold">{{ review.client }}</td>
              <td>{{ review.car || '—' }}</td>
              <td>{{ review.agency || '—' }}</td>
              <td>{{ review.car_rating != null ? review.car_rating + ' ★' : '—' }}</td>
              <td>{{ review.agency_rating != null ? review.agency_rating + ' ★' : '—' }}</td>
              <td>{{ review.comment || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <AdminPager :meta="meta" @prev="page--" @next="page++" />
    </template>
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import AdminPager from '@/components/AdminPager.vue'
import adminService from '@/services/admin'

const loading = ref(true)
const error = ref('')
const reviews = ref([])
const reports = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const page = ref(1)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getReviews({ page: page.value })
    reviews.value = data.reviews || []
    reports.value = data.reports || []
    meta.value = data.meta || meta.value
  } catch (err) {
    error.value = err.message || 'Impossible de charger les avis.'
  } finally {
    loading.value = false
  }
}

watch(page, load)
onMounted(load)
</script>
