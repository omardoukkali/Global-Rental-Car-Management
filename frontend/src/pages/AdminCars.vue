<template>
  <AdminLayout>
    <h1 class="page-greeting">Véhicules</h1>
    <p class="page-sub">Flotte de toutes les agences de la plateforme.</p>

    <div class="filter-row">
      <input v-model="q" class="form-input" type="search" placeholder="Marque, modèle, plaque…" @keyup.enter="search" />
      <select v-model="status" @change="search">
        <option value="">Tous les statuts</option>
        <option value="available">Disponible</option>
        <option value="unavailable">Indisponible</option>
        <option value="maintenance">Maintenance</option>
      </select>
    </div>

    <p v-if="error" class="banner-error">{{ error }}</p>
    <div v-else-if="loading" class="loading-box">Chargement des véhicules...</div>
    <div v-else class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Véhicule</th>
            <th>Plaque</th>
            <th>Agence</th>
            <th>Ville</th>
            <th>Prix / jour</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="cars.length === 0">
            <td colspan="6" class="empty-cell">Aucun véhicule.</td>
          </tr>
          <tr
            v-for="car in cars"
            :key="car.id"
            class="row-link"
            role="link"
            tabindex="0"
            @click="openCar(car.id)"
            @keyup.enter="openCar(car.id)"
          >
            <td class="bold">
              <RouterLink :to="carPath(car.id)" class="car-link" @click.stop>
                {{ car.brand }} {{ car.model }}
              </RouterLink>
              <span class="mono"> {{ car.year }}</span>
            </td>
            <td class="mono">{{ car.plate_number }}</td>
            <td>{{ car.agency }}</td>
            <td>{{ car.city || '—' }}</td>
            <td class="bold">{{ formatMoney(car.daily_price) }}</td>
            <td><span :class="['pill', carStatus(car.status).className]">{{ carStatus(car.status).label }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
    <AdminPager :meta="meta" @prev="page--" @next="page++" />
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AdminLayout from '@/components/AdminLayout.vue'
import AdminPager from '@/components/AdminPager.vue'
import adminService from '@/services/admin'
import { carStatus, formatMoney } from '@/utils/adminFormat'

const router = useRouter()
const loading = ref(true)
const error = ref('')
const cars = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const page = ref(1)
const q = ref('')
const status = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getCars({
      page: page.value,
      q: q.value || undefined,
      status: status.value || undefined,
    })
    cars.value = data.cars || []
    meta.value = data.meta || meta.value
  } catch (err) {
    error.value = err.message || 'Impossible de charger les véhicules.'
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

function carPath(id) {
  return `/cars/${id}/reserve`
}

function openCar(id) {
  router.push(carPath(id))
}
</script>

<style scoped>
.row-link {
  cursor: pointer;
}

.row-link:hover td {
  background: #FAFAF9;
}

.car-link {
  color: inherit;
  text-decoration: none;
}

.car-link:hover {
  text-decoration: underline;
}
</style>
