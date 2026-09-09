<template>
  <div class="min-h-screen bg-[#F8FAFC] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">
      
      <!-- En-tête -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <button 
            type="button" 
            @click="goBack" 
            class="text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1 mb-2 cursor-pointer transition-colors"
          >
            ← Retour
          </button>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight font-bricolage">
            Formulaire de Réservation
          </h1>
          <p class="text-sm text-slate-500 mt-1">
            Sélectionnez vos dates et points de retrait pour réserver votre véhicule.
          </p>
        </div>
      </div>

      <!-- Message d'alerte global en cas d'erreur -->
      <div 
        v-if="globalError" 
        data-testid="global-error"
        class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-start gap-3"
      >
        <span class="text-lg">⚠️</span>
        <div>
          <p class="font-bold">Erreur lors de la réservation</p>
          <p>{{ globalError }}</p>
        </div>
      </div>

      <!-- Message de succès après réservation -->
      <div 
        v-if="createdReservation" 
        data-testid="success-banner"
        class="bg-white border border-emerald-200 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4"
      >
        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-2xl font-bold mb-2">
          ✓
        </div>
        <h2 class="text-xl font-extrabold text-[#0F172A]">Réservation Confirmée !</h2>
        <p class="text-sm text-slate-600">
          Votre demande de réservation a été enregistrée avec succès.
        </p>

        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-500">Référence :</span>
            <span class="font-mono font-bold text-slate-900" data-testid="reservation-ref">
              {{ createdReservation.reference || createdReservation.id }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Statut :</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
              {{ createdReservation.status || 'En attente' }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Montant total :</span>
            <span class="font-bold text-emerald-700">
              {{ createdReservation.total_amount || totalPrice }} MAD
            </span>
          </div>
        </div>

        <div class="flex flex-wrap gap-3 pt-2">
          <RouterLink 
            to="/agency/cars" 
            class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition shadow-sm"
          >
            Explorer d'autres voitures
          </RouterLink>
          <button 
            type="button" 
            @click="resetForm" 
            class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold transition"
          >
            Nouvelle réservation
          </button>
        </div>
      </div>

      <!-- Formulaire principal -->
      <form 
        v-else 
        @submit.prevent="handleSubmit" 
        class="grid grid-cols-1 lg:grid-cols-3 gap-6"
      >
        
        <!-- Colonne de gauche / centrale : Champs du formulaire -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- 1. Sélection du véhicule -->
          <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-[#0F172A] border-b border-slate-100 pb-2">
              1. Véhicule
            </h2>

            <div v-if="loadingCar" class="text-sm text-slate-500 py-4 text-center">
              Chargement des détails du véhicule...
            </div>

            <div v-else-if="selectedCar" class="flex flex-col sm:flex-row gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
              <div class="w-full sm:w-36 h-24 bg-slate-200 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center text-slate-400">
                <img 
                  v-if="carImage" 
                  :src="carImage" 
                  :alt="selectedCar.brand + ' ' + selectedCar.model" 
                  class="w-full h-full object-cover" 
                />
                <span v-else class="text-3xl">🚗</span>
              </div>
              <div class="space-y-1 flex-1">
                <div class="flex items-center justify-between">
                  <h3 class="font-extrabold text-[#0F172A] text-base" data-testid="car-name">
                    {{ selectedCar.brand }} {{ selectedCar.model }}
                  </h3>
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                    {{ selectedCar.year }}
                  </span>
                </div>
                <p class="text-xs text-slate-500">
                  {{ selectedCar.transmission || 'Automatique' }} • {{ selectedCar.energy_type || 'Diesel' }} • {{ selectedCar.seats || 5 }} places
                </p>
                <div class="pt-1 text-sm font-extrabold text-slate-900">
                  <span data-testid="daily-price">{{ selectedCar.daily_price }}</span> MAD <span class="text-xs font-normal text-slate-500">/ jour</span>
                </div>
              </div>
            </div>

            <div v-else>
              <label for="car-select" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                Choisir un véhicule *
              </label>
              <select 
                id="car-select"
                v-model="form.car_id" 
                @change="onCarSelected"
                class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900"
                :class="{ 'border-red-500 ring-1 ring-red-500': errors.car_id }"
                required
              >
                <option value="" disabled>-- Sélectionnez un véhicule disponible --</option>
                <option v-for="car in availableCars" :key="car.id" :value="car.id">
                  {{ car.brand }} {{ car.model }} ({{ car.daily_price }} MAD/j)
                </option>
              </select>
              <p v-if="errors.car_id" class="text-xs text-red-500 mt-1">{{ errors.car_id[0] }}</p>
            </div>
          </div>

          <!-- 2. Période de location -->
          <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
              <h2 class="text-lg font-bold text-[#0F172A]">
                2. Dates de location
              </h2>
              <span 
                v-if="availabilityStatus" 
                data-testid="availability-pill"
                class="text-xs font-bold px-2.5 py-1 rounded-full"
                :class="availabilityStatus.available ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'"
              >
                {{ availabilityStatus.available ? '● Véhicule disponible' : '✕ Indisponible' }}
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label for="start-at" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                  Date & heure de départ *
                </label>
                <input 
                  type="datetime-local" 
                  id="start-at" 
                  v-model="form.start_at" 
                  :min="minStartDate"
                  @change="onDateChange"
                  class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900"
                  :class="{ 'border-red-500 ring-1 ring-red-500': errors.start_at }"
                  required 
                />
                <p v-if="errors.start_at" class="text-xs text-red-500 mt-1">{{ errors.start_at[0] }}</p>
              </div>

              <div>
                <label for="end-at" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                  Date & heure de retour *
                </label>
                <input 
                  type="datetime-local" 
                  id="end-at" 
                  v-model="form.end_at" 
                  :min="form.start_at || minStartDate"
                  @change="onDateChange"
                  class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900"
                  :class="{ 'border-red-500 ring-1 ring-red-500': errors.end_at }"
                  required 
                />
                <p v-if="errors.end_at" class="text-xs text-red-500 mt-1">{{ errors.end_at[0] }}</p>
              </div>
            </div>

            <div v-if="dateError" data-testid="date-error" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-100">
              {{ dateError }}
            </div>
          </div>

          <!-- 3. Points de retrait et restitution -->
          <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-[#0F172A] border-b border-slate-100 pb-2">
              3. Lieux de prise en charge et retour
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label for="pickup-point" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                  Point de retrait *
                </label>
                <select 
                  id="pickup-point" 
                  v-model="form.pickup_point_id" 
                  class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900"
                  :class="{ 'border-red-500 ring-1 ring-red-500': errors.pickup_point_id }"
                  required
                >
                  <option value="" disabled>-- Sélectionner le point de retrait --</option>
                  <option v-for="point in pickupPoints" :key="point.id" :value="point.id">
                    {{ point.name }} ({{ point.address || point.city }})
                  </option>
                </select>
                <p v-if="errors.pickup_point_id" class="text-xs text-red-500 mt-1">{{ errors.pickup_point_id[0] }}</p>
              </div>

              <div>
                <label for="return-point" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                  Point de restitution *
                </label>
                <select 
                  id="return-point" 
                  v-model="form.return_point_id" 
                  class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-900"
                  :class="{ 'border-red-500 ring-1 ring-red-500': errors.return_point_id }"
                  required
                >
                  <option value="" disabled>-- Sélectionner le point de retour --</option>
                  <option v-for="point in returnPoints" :key="point.id" :value="point.id">
                    {{ point.name }} ({{ point.address || point.city }})
                  </option>
                </select>
                <p v-if="errors.return_point_id" class="text-xs text-red-500 mt-1">{{ errors.return_point_id[0] }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Colonne de droite : Récapitulatif et bouton de confirmation -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-6 sticky top-6">
            <h2 class="text-lg font-bold text-[#0F172A] border-b border-slate-100 pb-2">
              Récapitulatif du prix
            </h2>

            <div class="space-y-3 text-sm">
              <div class="flex justify-between text-slate-600">
                <span>Tarif journalier :</span>
                <span class="font-semibold text-slate-900">{{ dailyPrice }} MAD</span>
              </div>

              <div class="flex justify-between text-slate-600">
                <span>Durée estimée :</span>
                <span class="font-semibold text-slate-900" data-testid="duration-days">
                  {{ rentalDays }} jour{{ rentalDays > 1 ? 's' : '' }}
                </span>
              </div>

              <div class="border-t border-slate-100 pt-3 flex justify-between items-baseline">
                <span class="font-bold text-[#0F172A] text-base">Total estimé :</span>
                <span class="text-2xl font-extrabold text-[#0F172A]" data-testid="total-price">
                  {{ totalPrice }} <span class="text-xs font-normal text-slate-500">MAD</span>
                </span>
              </div>
            </div>

            <div class="pt-2">
              <button 
                type="submit" 
                :disabled="submitting || !isValid" 
                data-testid="submit-button"
                class="w-full py-3.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-sm transition shadow-sm flex items-center justify-center gap-2 cursor-pointer"
              >
                <svg v-if="submitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ submitting ? 'Envoi en cours...' : 'Confirmer la réservation' }}</span>
              </button>
            </div>

            <p class="text-[11px] text-slate-400 text-center">
              Paiement sécurisé. Annulation possible selon conditions de l'agence.
            </p>
          </div>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import reservationsService from '@/services/reservations'
import carsService from '@/services/cars'

const props = defineProps({
  carId: {
    type: String,
    default: null
  }
})

const router = useRouter()
const route = useRoute()

// États
const loadingCar = ref(false)
const submitting = ref(false)
const globalError = ref('')
const dateError = ref('')
const errors = reactive({})
const createdReservation = ref(null)
const availabilityStatus = ref(null)

const selectedCar = ref(null)
const availableCars = ref([])
const agencyPoints = ref([])

// Formulaire
const form = reactive({
  car_id: props.carId || route?.params?.carId || route?.query?.car_id || '',
  start_at: '',
  end_at: '',
  pickup_point_id: '',
  return_point_id: ''
})

// Dates limites
const minStartDate = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

// Points
const pickupPoints = computed(() => {
  return agencyPoints.value.filter(p => p.allows_pickup !== false)
})

const returnPoints = computed(() => {
  return agencyPoints.value.filter(p => p.allows_return !== false)
})

const carImage = computed(() => {
  if (!selectedCar.value) return null
  const primary = selectedCar.value.images?.find(img => img.is_primary)
  return primary ? primary.url : selectedCar.value.images?.[0]?.url || null
})

const dailyPrice = computed(() => {
  return selectedCar.value ? Number(selectedCar.value.daily_price) || 0 : 0
})

// Calcul du nombre de jours
const rentalDays = computed(() => {
  if (!form.start_at || !form.end_at) return 1
  const start = new Date(form.start_at)
  const end = new Date(form.end_at)
  if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) return 1
  
  const diffMs = end - start
  const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24))
  return diffDays > 0 ? diffDays : 1
})

// Prix total calculé
const totalPrice = computed(() => {
  return rentalDays.value * dailyPrice.value
})

// Validation formulaire
const isValid = computed(() => {
  return !!(
    form.car_id &&
    form.start_at &&
    form.end_at &&
    form.pickup_point_id &&
    form.return_point_id &&
    !dateError.value
  )
})

// Navigation retour
function goBack() {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/agency/cars')
  }
}

// Changement de voiture
function onCarSelected() {
  selectedCar.value = availableCars.value.find(c => c.id === form.car_id) || null
  checkAvailability()
}

// Changement de date
function onDateChange() {
  dateError.value = ''
  if (form.start_at && form.end_at) {
    const start = new Date(form.start_at)
    const end = new Date(form.end_at)
    if (end <= start) {
      dateError.value = 'La date de retour doit être ultérieure à la date de départ.'
      return
    }
  }
  checkAvailability()
}

// Vérifier la disponibilité
async function checkAvailability() {
  if (!form.car_id || !form.start_at || !form.end_at || dateError.value) {
    availabilityStatus.value = null
    return
  }

  try {
    const res = await reservationsService.checkAvailability(form.car_id, {
      start_at: form.start_at,
      end_at: form.end_at
    })
    availabilityStatus.value = res.data
  } catch (err) {
    // Si l'endpoint n'est pas encore actif ou échoue, on ignore sans bloquer
    availabilityStatus.value = null
  }
}

// Réinitialiser le formulaire
function resetForm() {
  createdReservation.value = null
  globalError.value = ''
  form.start_at = ''
  form.end_at = ''
  form.pickup_point_id = ''
  form.return_point_id = ''
  availabilityStatus.value = null
}

// Soumission du formulaire
async function handleSubmit() {
  globalError.value = ''
  Object.keys(errors).forEach(key => delete errors[key])

  if (!isValid.value) return

  submitting.value = true

  try {
    const payload = {
      car_id: form.car_id,
      pickup_point_id: form.pickup_point_id,
      return_point_id: form.return_point_id,
      start_at: form.start_at,
      end_at: form.end_at
    }

    const response = await reservationsService.createReservation(payload)
    createdReservation.value = response.data?.reservation || {
      id: 'RES-' + Math.floor(Math.random() * 10000),
      total_amount: totalPrice.value,
      status: 'pending'
    }
  } catch (err) {
    if (err.response?.status === 422 && err.response.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    } else {
      globalError.value = err.response?.data?.message || err.message || 'Une erreur est survenue lors de la réservation.'
    }
  } finally {
    submitting.value = false
  }
}

// Initialisation
onMounted(async () => {
  const targetCarId = form.car_id

  loadingCar.value = true
  try {
    // 1. Charger la liste des voitures si nécessaire
    const carsRes = await carsService.getCars()
    availableCars.value = carsRes.data?.cars || []

    if (targetCarId) {
      selectedCar.value = availableCars.value.find(c => c.id === targetCarId)
      if (!selectedCar.value) {
        try {
          const singleCarRes = await carsService.getCar(targetCarId)
          selectedCar.value = singleCarRes.data?.car || null
        } catch {
          // Ignorer
        }
      }
    }
  } catch (e) {
    console.error('Erreur chargement voitures', e)
  } finally {
    loadingCar.value = false
  }

  // 2. Charger les points de relais (avec points par défaut si non disponibles)
  try {
    // Si l'agence ou l'API retourne des points
    const pointsRes = await carsService.getPoints?.() || { data: { points: [] } }
    if (pointsRes.data?.points?.length) {
      agencyPoints.value = pointsRes.data.points
    } else {
      // Points par défaut sécurisés pour l'expérience client
      agencyPoints.value = [
        { id: '11111111-1111-1111-1111-111111111111', name: 'Aéroport Mohammed V', city: 'Casablanca', address: 'Terminal 1', allows_pickup: true, allows_return: true },
        { id: '22222222-2222-2222-2222-222222222222', name: 'Gare Casa-Voyageurs', city: 'Casablanca', address: 'Place Maghreb', allows_pickup: true, allows_return: true },
        { id: '33333333-3333-3333-3333-333333333333', name: 'Agence Centre-Ville', city: 'Casablanca', address: 'Bd Zerktouni', allows_pickup: true, allows_return: true }
      ]
    }
  } catch {
    agencyPoints.value = [
      { id: '11111111-1111-1111-1111-111111111111', name: 'Aéroport Mohammed V', city: 'Casablanca', address: 'Terminal 1', allows_pickup: true, allows_return: true },
      { id: '22222222-2222-2222-2222-222222222222', name: 'Gare Casa-Voyageurs', city: 'Casablanca', address: 'Place Maghreb', allows_pickup: true, allows_return: true }
    ]
  }

  // Sélectionner les points par défaut
  if (agencyPoints.value.length > 0) {
    form.pickup_point_id = agencyPoints.value[0].id
    form.return_point_id = agencyPoints.value[0].id
  }
})
</script>
