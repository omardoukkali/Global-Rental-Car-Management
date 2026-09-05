<template>
  <div class="min-h-screen bg-[#F8FAFC] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto space-y-6 fade-up">
      
      <div class="flex items-center justify-between">
        <div>
          <RouterLink to="/agency/cars" class="text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1 mb-2">
            ← Retour à ma flotte
          </RouterLink>
          <h1 class="font-bricolage text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight">
            {{ isEditMode ? 'Modifier le véhicule' : 'Ajouter un nouveau véhicule' }}
          </h1>
        </div>
      </div>

      <div v-if="globalError" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        {{ globalError }}
      </div>
      <div v-if="successMsg" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
        {{ successMsg }}
      </div>

      <div v-if="initialLoading" class="bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-500 shadow-sm">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-slate-800 mb-3"></div>
        <p class="text-sm font-medium">Chargement des informations du véhicule...</p>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
        <div>
          <h2 class="font-bricolage text-lg font-bold text-[#0F172A] border-b border-slate-100 pb-2 mb-4">
            1. Informations principales
          </h2>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label" for="car-brand">Marque *</label>
              <input v-model="form.brand" type="text" id="car-brand" class="form-input" :class="{ 'is-invalid': errors.brand }" placeholder="Ex: Dacia, Renault, Peugeot" required />
              <p v-if="errors.brand" class="text-xs text-red-500 mt-1">{{ errors.brand[0] }}</p>
            </div>

            <div>
              <label class="form-label" for="car-model">Modèle *</label>
              <input v-model="form.model" type="text" id="car-model" class="form-input" :class="{ 'is-invalid': errors.model }" placeholder="Ex: Logan, Clio 5, 208" required />
              <p v-if="errors.model" class="text-xs text-red-500 mt-1">{{ errors.model[0] }}</p>
            </div>

            <div>
              <label class="form-label" for="car-year">Année *</label>
              <input v-model.number="form.year" type="number" id="car-year" min="1990" :max="new Date().getFullYear() + 1" class="form-input" :class="{ 'is-invalid': errors.year }" placeholder="2023" required />
              <p v-if="errors.year" class="text-xs text-red-500 mt-1">{{ errors.year[0] }}</p>
            </div>

            <div>
              <label class="form-label" for="car-plate">Immatriculation *</label>
              <input v-model="form.plate_number" type="text" id="car-plate" class="form-input" :class="{ 'is-invalid': errors.plate_number }" placeholder="Ex: 12345-A-1" required />
              <p v-if="errors.plate_number" class="text-xs text-red-500 mt-1">{{ errors.plate_number[0] }}</p>
            </div>

            <div>
              <label class="form-label" for="car-city">Ville de disponibilité *</label>
              <select v-model="form.city_id" id="car-city" class="form-input" :class="{ 'is-invalid': errors.city_id }" required>
                <option value="" disabled>Sélectionnez une ville</option>
                <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <p v-if="errors.city_id" class="text-xs text-red-500 mt-1">{{ errors.city_id[0] }}</p>
            </div>

            <div>
              <label class="form-label" for="car-color">Couleur</label>
              <input v-model="form.color" type="text" id="car-color" class="form-input" placeholder="Ex: Gris métallisé, Blanc, Noir" />
            </div>
          </div>
        </div>

        <div>
          <h2 class="font-bricolage text-lg font-bold text-[#0F172A] border-b border-slate-100 pb-2 mb-4">
            2. Caractéristiques & Tarification
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="form-label" for="car-type">Carrosserie *</label>
              <select v-model="form.type" id="car-type" class="form-input" required>
                <option value="sedan">Berline (Sedan)</option>
                <option value="suv">SUV / 4x4</option>
                <option value="hatchback">Citadine (Hatchback)</option>
                <option value="coupe">Coupé</option>
                <option value="van">Van / Minibus</option>
                <option value="truck">Utilitaire</option>
              </select>
            </div>

            <div>
              <label class="form-label" for="car-transmission">Transmission *</label>
              <select v-model="form.transmission" id="car-transmission" class="form-input" required>
                <option value="manual">Manuelle</option>
                <option value="automatic">Automatique</option>
              </select>
            </div>

            <div>
              <label class="form-label" for="car-energy">Carburant *</label>
              <select v-model="form.energy_type" id="car-energy" class="form-input" required>
                <option value="diesel">Diesel</option>
                <option value="gasoline">Essence</option>
                <option value="hybrid">Hybride</option>
                <option value="electric">Électrique</option>
              </select>
            </div>

            <div>
              <label class="form-label" for="car-seats">Nombre de places *</label>
              <input v-model.number="form.seats" type="number" id="car-seats" min="1" max="50" class="form-input" required />
            </div>

            <div>
              <label class="form-label" for="car-price">Prix par jour (MAD) *</label>
              <input v-model.number="form.daily_price" type="number" id="car-price" min="0" step="10" class="form-input font-bold" :class="{ 'is-invalid': errors.daily_price }" placeholder="300" required />
              <p v-if="errors.daily_price" class="text-xs text-red-500 mt-1">{{ errors.daily_price[0] }}</p>
            </div>

            <div>
              <label class="form-label" for="car-fuel">Consommation (L/100km)</label>
              <input v-model.number="form.fuel_consumption" type="number" step="0.1" id="car-fuel" class="form-input" placeholder="5.4" />
            </div>
          </div>
        </div>

        <div>
          <h2 class="font-bricolage text-lg font-bold text-[#0F172A] border-b border-slate-100 pb-2 mb-4">
            3. Photo du véhicule
          </h2>

          <input 
            type="file" 
            ref="fileInput" 
            accept="image/*" 
            class="hidden" 
            @change="handleFileSelected" 
          />

          <div 
            v-if="!imagePreview"
            @click="triggerFileInput" 
            class="border-2 border-dashed border-slate-300 hover:border-slate-400 bg-slate-50 hover:bg-slate-100/80 rounded-2xl p-8 text-center cursor-pointer transition-colors"
          >
            <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mx-auto mb-3 text-slate-500">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <p class="text-sm font-bold text-[#0F172A]">Cliquez pour importer une photo</p>
            <p class="text-xs text-slate-500 mt-1">PNG, JPG ou WEBP (Max 5 Mo)</p>
          </div>

          <div v-else class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 rounded-2xl border border-slate-200 bg-slate-50">
            <div class="w-40 h-28 rounded-xl overflow-hidden bg-slate-200 border border-slate-300 flex-shrink-0">
              <img :src="imagePreview" alt="Aperçu voiture" class="w-full h-full object-cover" />
            </div>
            <div class="space-y-2">
              <p class="text-xs font-semibold text-slate-700">Photo sélectionnée</p>
              <div class="flex gap-2">
                <button 
                  type="button" 
                  @click="triggerFileInput" 
                  class="text-xs font-bold px-3 py-1.5 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
                >
                  Changer la photo
                </button>
                <button 
                  type="button" 
                  @click="removeImage" 
                  class="text-xs font-bold px-3 py-1.5 text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors"
                >
                  Supprimer
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
          <RouterLink to="/agency/cars" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition-colors">
            Annuler
          </RouterLink>
          
          <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold shadow-sm" :disabled="saving">
            <span v-if="saving">Enregistrement...</span>
            <span v-else>{{ isEditMode ? 'Enregistrer les modifications' : 'Ajouter le véhicule' }}</span>
          </button>
        </div>

      </form>

    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import carsService from '@/services/cars'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const carId = route.params.id
const isEditMode = computed(() => !!carId)

const fileInput = ref(null)
const imagePreview = ref('')
const cities = ref([])
const initialLoading = ref(false)
const saving = ref(false)
const globalError = ref('')
const successMsg = ref('')
const errors = reactive({})

const form = reactive({
  city_id: '',
  brand: '',
  model: '',
  year: new Date().getFullYear(),
  plate_number: '',
  color: '',
  type: 'sedan',
  transmission: 'manual',
  seats: 5,
  daily_price: 300,
  energy_type: 'diesel',
  fuel_consumption: null,
})

function triggerFileInput() {
  fileInput.value?.click()
}

function handleFileSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function removeImage() {
  imagePreview.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

async function loadCities() {
  try {
    const res = await api.get('/cities')
    cities.value = Array.isArray(res) ? res : (res.data || [])
    if (cities.value.length > 0 && !form.city_id) {
      form.city_id = cities.value[0].id
    }
  } catch (err) {
    console.error('Erreur chargement villes:', err)
  }
}

async function loadCarDetails() {
  if (!isEditMode.value) return
  initialLoading.value = true
  try {
    const res = await carsService.getCar(carId)
    const car = res.car || res.data || res
    
    form.city_id = car.city_id
    form.brand = car.brand
    form.model = car.model
    form.year = car.year
    form.plate_number = car.plate_number
    form.color = car.color || ''
    form.type = car.type || 'sedan'
    form.transmission = car.transmission || 'manual'
    form.seats = car.seats || 5
    form.daily_price = car.daily_price
    form.energy_type = car.energy_type || 'diesel'
    form.fuel_consumption = car.fuel_consumption

    if (car.images && car.images.length > 0) {
      imagePreview.value = car.images[0].url || car.images[0].image_url
    }
  } catch (err) {
    globalError.value = 'Impossible de charger les détails du véhicule.'
  } finally {
    initialLoading.value = false
  }
}

async function handleSubmit() {
  saving.value = true
  globalError.value = ''
  successMsg.value = ''
  Object.keys(errors).forEach(k => delete errors[k])

  const payload = {
    ...form,
    images: imagePreview.value ? [{ url: imagePreview.value, is_primary: true, display_order: 1 }] : []
  }

  try {
    if (isEditMode.value) {
      await carsService.updateCar(carId, form)
      successMsg.value = 'Véhicule modifié avec succès !'
    } else {
      await carsService.createCar(payload)
      successMsg.value = 'Véhicule ajouté avec succès à votre flotte !'
    }

    setTimeout(() => {
      router.push('/agency/cars')
    }, 1200)

  } catch (err) {
    if (err.status === 422 && err.errors) {
      Object.assign(errors, err.errors)
    } else {
      globalError.value = err.response?.data?.message || err.message || 'Une erreur est survenue.'
    }
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadCities()
  await loadCarDetails()
})
</script>
