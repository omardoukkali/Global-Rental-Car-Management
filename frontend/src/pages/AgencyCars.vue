<template>
  <div class="min-h-screen bg-[#F8FAFC] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6 fade-up">
      
      <!-- HEADER DE SECTION -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="font-bricolage text-3xl font-extrabold text-[#0F172A] tracking-tight">
            Ma flotte
          </h1>
          <p class="text-sm text-[#64748B] mt-1">
            Gérez la disponibilité, les tarifs et les informations de vos véhicules.
          </p>
        </div>

        <RouterLink 
          to="/agency/cars/new" 
          class="btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold shadow-sm hover:opacity-90 transition-all text-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Ajouter un véhicule
        </RouterLink>
      </div>

      <!-- BANDEAU D'ERREUR -->
      <div v-if="error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        {{ error }}
      </div>

      <!-- ÉTAT DE CHARGEMENT -->
      <div v-if="loading" class="bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-500 shadow-sm">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-slate-800 mb-3"></div>
        <p class="text-sm font-medium">Chargement de votre flotte...</p>
      </div>

      <!-- ÉTAT VIDE (0 VOITURE) -->
      <div v-else-if="cars.length === 0" class="bg-white rounded-2xl p-12 border border-slate-200 text-center shadow-sm">
        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3m-7 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm9 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
          </svg>
        </div>
        <h3 class="font-bricolage text-lg font-bold text-[#0F172A]">Aucun véhicule enregistré</h3>
        <p class="text-sm text-[#64748B] max-w-md mx-auto mt-1 mb-6">
          Votre agence n'a pas encore ajouté de véhicule à son catalogue.
        </p>
        <RouterLink to="/agency/cars/new" class="btn-primary px-4 py-2 rounded-lg text-sm font-medium">
          + Ajouter votre première voiture
        </RouterLink>
      </div>

      <!-- TABLEAU DE LA FLOTTE -->
      <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                <th class="py-3.5 px-6">Photo</th>
                <th class="py-3.5 px-6">Véhicule</th>
                <th class="py-3.5 px-6">Immatriculation</th>
                <th class="py-3.5 px-6">Statut</th>
                <th class="py-3.5 px-6">Prix / jour</th>
                <th class="py-3.5 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr v-for="car in cars" :key="car.id" class="hover:bg-slate-50/60 transition-colors">
                
                <!-- PHOTO -->
                <td class="py-4 px-6">
                  <div class="w-14 h-10 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center">
                    <img 
                      v-if="car.images && car.images.length > 0" 
                      :src="car.images[0].image_url || car.images[0].url" 
                      :alt="car.brand + ' ' + car.model" 
                      class="w-full h-full object-cover" 
                    />
                    <svg v-else class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                </td>

                <!-- VÉHICULE (Marque, Modèle, Année) -->
                <td class="py-4 px-6">
                  <div class="font-bold text-[#0F172A]">{{ car.brand }} {{ car.model }}</div>
                  <div class="text-xs text-[#64748B]">Année {{ car.year }}</div>
                </td>

                <!-- IMMATRICULATION -->
                <td class="py-4 px-6 font-mono text-xs text-slate-600 font-semibold">
                  {{ car.registration_number || car.plate_number || 'N/A' }}
                </td>

                <!-- STATUT -->
                <td class="py-4 px-6">
                  <span 
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                    :class="getStatusBadgeClass(car.status)"
                  >
                    {{ getStatusLabel(car.status) }}
                  </span>
                </td>

                <!-- PRIX / JOUR -->
                <td class="py-4 px-6 font-bold text-[#0F172A]">
                  {{ car.price_per_day || car.daily_price }} <span class="text-xs font-normal text-slate-500">MAD</span>
                </td>

                <!-- ACTIONS -->
                <td class="py-4 px-6 text-right space-x-3">
                  <RouterLink 
                    :to="`/agency/cars/${car.id}/edit`" 
                    class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors"
                  >
                    Modifier
                  </RouterLink>
                  <button 
                    @click="handleDisable(car)" 
                    class="text-xs font-bold text-slate-400 hover:text-red-600 transition-colors"
                  >
                    Désactiver
                  </button>
                </td>

              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import carsService from '@/services/cars'

const cars = ref([])
const loading = ref(true)
const error = ref('')

// Charger les voitures de l'agence au montage
async function loadCars() {
  loading.value = true
  error.value = ''
  try {
    const res = await carsService.getCars()
    // Gère la réponse que ce soit { cars: [...] } ou direct [...]
    cars.value = res.cars || res.data || (Array.isArray(res) ? res : [])
  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'Erreur lors du chargement des voitures.'
  } finally {
    loading.value = false
  }
}

// Désactiver un véhicule
async function handleDisable(car) {
  if (!confirm(`Voulez-vous vraiment désactiver ${car.brand} ${car.model} ?`)) return
  try {
    await carsService.disableCar(car.id)
    await loadCars() // Recharge la liste
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de la désactivation.')
  }
}

// Helpers d'affichage du statut
function getStatusLabel(status) {
  switch (status) {
    case 'available': return 'Disponible'
    case 'rented': return 'En location'
    case 'maintenance': return 'Maintenance'
    case 'unavailable': return 'Indisponible'
    default: return status || 'Disponible'
  }
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'available': return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'rented': return 'bg-blue-50 text-blue-700 border border-blue-200'
    case 'maintenance': return 'bg-amber-50 text-amber-700 border border-amber-200'
    case 'unavailable': return 'bg-rose-50 text-rose-700 border border-rose-200'
    default: return 'bg-slate-50 text-slate-700 border border-slate-200'
  }
}

onMounted(() => {
  loadCars()
})
</script>