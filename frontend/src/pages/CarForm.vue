<template>
  <AgencyLayout>
    <div class="space-y-6 fade-up">
      
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

      <div v-if="globalError" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold">
        ⚠️ {{ globalError }}
      </div>
      <div v-if="successMsg" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold">
        ✅ {{ successMsg }}
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
          <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-4">
            <h2 class="font-bricolage text-lg font-bold text-[#0F172A]">
              3. Galerie photos
            </h2>
            <span v-if="isEditMode" class="text-[11px] font-semibold text-slate-400">
              Enregistrement immédiat
            </span>
          </div>

          <p v-if="galleryError" class="mb-3 text-xs font-semibold text-red-600">{{ galleryError }}</p>
          <p v-if="galleryNotice" class="mb-3 text-xs font-semibold text-emerald-700">{{ galleryNotice }}</p>

          <CarImageGallery
            :images="images"
            :busy="galleryBusy"
            @add="addImage"
            @remove="removeImage"
            @primary="setPrimaryImage"
            @move="moveImage"
          />
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
  </AgencyLayout>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import AgencyLayout from '@/components/AgencyLayout.vue'
import CarImageGallery from '@/components/CarImageGallery.vue'
import carsService from '@/services/cars'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const carId = route.params.id
const isEditMode = computed(() => !!carId)

const images = ref([])
const galleryBusy = ref(false)
const galleryError = ref('')
const galleryNotice = ref('')
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

// ---------------------------------------------------------------------------
// Galerie photos
// Création : la liste reste locale et part avec le véhicule (images[]).
// Édition : chaque action appelle l'API images du véhicule.
// ---------------------------------------------------------------------------

function normalizeImage(image, index) {
  return {
    id: image.id ?? null,
    url: image.url || image.image_url || '',
    is_primary: !!image.is_primary,
    display_order: image.display_order ?? index,
  }
}

function sortImages(list) {
  return [...list]
    .sort((a, b) => {
      if (!!a.is_primary !== !!b.is_primary) return a.is_primary ? -1 : 1
      return (a.display_order ?? 0) - (b.display_order ?? 0)
    })
    .map((img, index) => ({ ...img, display_order: index }))
}

function ensurePrimary(list) {
  if (list.length > 0 && !list.some((img) => img.is_primary)) {
    list[0].is_primary = true
  }
  return list
}

function galleryPayload() {
  return sortImages(images.value).map((img, index) => ({
    url: img.url,
    is_primary: !!img.is_primary,
    display_order: index,
  }))
}

function clearGalleryMessages() {
  galleryError.value = ''
  galleryNotice.value = ''
}

async function loadImages() {
  if (!isEditMode.value) return
  try {
    const res = await carsService.getCarImages(carId)
    const list = res.images || res.data?.images || []
    images.value = sortImages(ensurePrimary(list.map(normalizeImage)))
  } catch (err) {
    galleryError.value = err.message || 'Impossible de charger les photos.'
  }
}

async function addImage(url) {
  clearGalleryMessages()
  const isFirst = images.value.length === 0

  if (!isEditMode.value) {
    images.value = sortImages([
      ...images.value,
      { id: null, url, is_primary: isFirst, display_order: images.value.length },
    ])
    return
  }

  galleryBusy.value = true
  try {
    await carsService.addCarImage(carId, {
      url,
      is_primary: isFirst,
      display_order: images.value.length,
    })
    await loadImages()
    galleryNotice.value = 'Photo ajoutée.'
  } catch (err) {
    galleryError.value = err.errors?.url?.[0] || err.message || 'Ajout de la photo impossible.'
  } finally {
    galleryBusy.value = false
  }
}

async function removeImage(image) {
  clearGalleryMessages()

  if (!isEditMode.value || !image.id) {
    const rest = images.value.filter((img) => img !== image && img.url !== image.url)
    images.value = sortImages(ensurePrimary(rest))
    return
  }

  galleryBusy.value = true
  try {
    await carsService.deleteCarImage(carId, image.id)
    const rest = images.value.filter((img) => img.id !== image.id)
    // Si la principale a été supprimée, promouvoir la suivante côté API
    if (image.is_primary && rest.length > 0) {
      await carsService.setPrimaryCarImage(carId, sortImages(rest)[0].id)
    }
    await loadImages()
    galleryNotice.value = 'Photo supprimée.'
  } catch (err) {
    galleryError.value = err.message || 'Suppression impossible.'
  } finally {
    galleryBusy.value = false
  }
}

async function setPrimaryImage(image) {
  clearGalleryMessages()

  if (!isEditMode.value || !image.id) {
    images.value = sortImages(
      images.value.map((img) => ({ ...img, is_primary: img === image || img.url === image.url }))
    )
    return
  }

  galleryBusy.value = true
  try {
    await carsService.setPrimaryCarImage(carId, image.id)
    await loadImages()
    galleryNotice.value = 'Photo principale mise à jour.'
  } catch (err) {
    galleryError.value = err.message || 'Changement de photo principale impossible.'
  } finally {
    galleryBusy.value = false
  }
}

function moveImage({ image, direction }) {
  clearGalleryMessages()
  const list = sortImages(images.value)
  const from = list.findIndex((img) => img === image || (img.id && img.id === image.id) || img.url === image.url)
  const to = from + direction
  if (from < 0 || to < 0 || to >= list.length || list[to].is_primary) return
  const [moved] = list.splice(from, 1)
  list.splice(to, 0, moved)
  images.value = list.map((img, index) => ({ ...img, display_order: index }))
  if (isEditMode.value) {
    galleryNotice.value = 'Ordre modifié pour l’affichage local. L’ordre enregistré suit l’ordre d’ajout.'
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

    if (Array.isArray(car.images) && car.images.length > 0) {
      images.value = sortImages(ensurePrimary(car.images.map(normalizeImage)))
    }
    await loadImages()
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
    images: galleryPayload(),
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
    }, 1000)

  } catch (err) {
    if (err.errors) {
      Object.assign(errors, err.errors)
      const firstError = Object.values(err.errors).flat()[0]
      globalError.value = firstError || 'Erreur de validation sur le formulaire.'
    } else {
      globalError.value = err.message || err.response?.data?.message || 'Une erreur est survenue lors de l\'enregistrement.'
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
