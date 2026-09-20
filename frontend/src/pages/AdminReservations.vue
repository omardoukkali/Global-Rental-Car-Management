<template>
  <AdminLayout>
    <h1 class="page-greeting">Réservations</h1>
    <p class="page-sub">Toutes les locations de la plateforme.</p>

    <div class="filter-row">
      <input v-model="q" class="form-input" type="search" placeholder="Référence, client, agence…" @keyup.enter="search" />
      <select v-model="status" @change="search">
        <option value="">Tous les statuts</option>
        <option value="pending">Attente</option>
        <option value="confirmed">Confirmé</option>
        <option value="picked_up">En cours</option>
        <option value="completed">Terminé</option>
        <option value="cancelled">Annulé</option>
        <option value="disputed">Signalé</option>
        <option value="rejected">Rejeté</option>
      </select>
    </div>

    <p v-if="error" class="banner-error">{{ error }}</p>
    <div v-else-if="loading" class="loading-box">Chargement des réservations...</div>
    <div v-else class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Référence</th>
            <th>Client</th>
            <th>Agence</th>
            <th>Véhicule</th>
            <th>Montant</th>
            <th>Commission</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="reservations.length === 0">
            <td colspan="7" class="empty-cell">Aucune réservation.</td>
          </tr>
          <tr v-for="row in reservations" :key="row.id">
            <td class="mono">{{ row.reference }}</td>
            <td class="bold">{{ row.client }}</td>
            <td>{{ row.agency }}</td>
            <td>{{ row.car || '—' }}</td>
            <td class="bold">{{ formatMoney(row.amount) }}</td>
            <td class="positive">{{ Number(row.commission) > 0 ? '+' + formatMoney(row.commission) : '0' }}</td>
            <td>
              <span :class="['pill', reservationStatus(row.status).className]">
                {{ reservationStatus(row.status).label }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <AdminPager :meta="meta" @prev="page--" @next="page++" />
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import AdminPager from '@/components/AdminPager.vue'
import adminService from '@/services/admin'
import { formatMoney, reservationStatus } from '@/utils/adminFormat'

const loading = ref(true)
const error = ref('')
const reservations = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const page = ref(1)
const q = ref('')
const status = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getReservations({
      page: page.value,
      q: q.value || undefined,
      status: status.value || undefined,
    })
    reservations.value = data.reservations || []
    meta.value = data.meta || meta.value
  } catch (err) {
    error.value = err.message || 'Impossible de charger les réservations.'
  } finally {
    loading.value = false
  }
}

function search() {
  page.value = 1
  load()
}

watch(page, load)
onMounted(load)
</script>
