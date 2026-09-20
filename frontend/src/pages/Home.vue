<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import CarSearchBar from '@/components/CarSearchBar.vue'
import PublicCarCard from '@/components/PublicCarCard.vue'
import carsService from '@/services/cars'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const search = ref({ q: '', city_id: '', start_at: '', end_at: '' })
const cars = ref([])
const loading = ref(true)
const error = ref('')

const featured = computed(() => {
  const list = [...cars.value]
  list.sort((a, b) => {
    const ra = Number(a.agency?.avg_rating || 0)
    const rb = Number(b.agency?.avg_rating || 0)
    if (rb !== ra) return rb - ra
    return (b.images?.length || 0) - (a.images?.length || 0)
  })
  return list.slice(0, 8)
})

const cities = computed(() => {
  const map = new Map()
  for (const car of cars.value) {
    const city = car.city
    if (!city?.id) continue
    const entry = map.get(city.id) || { id: city.id, name: city.name, count: 0, image: null }
    entry.count += 1
    if (!entry.image) {
      const primary = (car.images || []).find((img) => img.is_primary) || car.images?.[0]
      entry.image = primary?.url || primary?.image_url || null
    }
    map.set(city.id, entry)
  }
  return [...map.values()].sort((a, b) => b.count - a.count).slice(0, 6)
})

const agencyCount = computed(() => new Set(cars.value.map((c) => c.agency_id || c.agency?.id)).size)

const types = [
  { value: 'suv', label: 'SUV & 4x4', hint: 'Familles, pistes, montagne' },
  { value: 'sedan', label: 'Berlines', hint: 'Confort et longs trajets' },
  { value: 'hatchback', label: 'Citadines', hint: 'Petit budget, ville' },
  { value: 'van', label: 'Vans & minibus', hint: 'Groupes jusqu’à 9' },
]

function goSearch(value) {
  const query = {}
  for (const [key, val] of Object.entries(value || {})) {
    if (val) query[key] = val
  }
  router.push({ path: '/cars', query })
}

function goType(type) {
  router.push({ path: '/cars', query: { type } })
}

function goCity(cityId) {
  router.push({ path: '/cars', query: { city_id: cityId } })
}

onMounted(async () => {
  loading.value = true
  try {
    const res = await carsService.getPublicCars()
    cars.value = res?.cars || res?.data?.cars || []
  } catch (err) {
    error.value = err?.message || 'Impossible de charger le catalogue.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="bg-white" data-testid="home-page">
    <!-- HERO -->
    <section class="relative overflow-hidden">
      <div class="absolute inset-0 hero-bg"></div>
      <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/50 to-[#F8FAFC]"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-12 lg:pt-28 lg:pb-20 text-white">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70 mb-4 fade-up">Location de voitures au Maroc</p>
        <h1 class="font-bricolage text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.05] max-w-3xl fade-up">
          Louez la voiture qu’il vous faut, auprès d’agences vérifiées.
        </h1>
        <p class="mt-5 text-lg text-white/80 max-w-2xl fade-up fade-up-1">
          Comparez les véhicules disponibles à Casablanca, Marrakech, Tanger, Agadir et Rabat.
          Réservez en ligne, récupérez à l’agence ou à l’aéroport.
        </p>

        <div class="mt-10 fade-up fade-up-1">
          <CarSearchBar v-model="search" @search="goSearch" />
        </div>

        <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold fade-up fade-up-1">
          <span class="px-3 py-1.5 rounded-full bg-white/90 text-slate-700 border border-slate-200">
            <strong class="text-[#0F172A]">{{ loading ? '…' : cars.length }}</strong> véhicules disponibles
          </span>
          <span class="px-3 py-1.5 rounded-full bg-white/90 text-slate-700 border border-slate-200">
            <strong class="text-[#0F172A]">{{ loading ? '…' : agencyCount }}</strong> agences approuvées
          </span>
          <span class="px-3 py-1.5 rounded-full bg-white/90 text-slate-700 border border-slate-200">
            Annulation gratuite avant paiement
          </span>
        </div>
      </div>
    </section>

    <div class="bg-[#F8FAFC]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-16">
        <!-- TYPES -->
        <section>
          <h2 class="font-bricolage text-2xl font-extrabold text-[#0F172A] tracking-tight mb-5">Parcourir par catégorie</h2>
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <button
              v-for="type in types"
              :key="type.value"
              type="button"
              class="text-left p-5 rounded-2xl bg-white border border-slate-200 hover:border-slate-900 hover:shadow-md transition-all"
              @click="goType(type.value)"
            >
              <span class="block font-bold text-[#0F172A]">{{ type.label }}</span>
              <span class="block text-xs text-slate-500 mt-1">{{ type.hint }}</span>
            </button>
          </div>
        </section>

        <!-- FEATURED -->
        <section>
          <div class="flex items-end justify-between gap-4 mb-5">
            <div>
              <h2 class="font-bricolage text-2xl font-extrabold text-[#0F172A] tracking-tight">Véhicules à la une</h2>
              <p class="text-sm text-slate-500 mt-1">Les mieux notés du moment, disponibles à la réservation.</p>
            </div>
            <RouterLink to="/cars" class="text-sm font-bold text-[#0F172A] underline underline-offset-4 whitespace-nowrap">
              Tout le catalogue →
            </RouterLink>
          </div>

          <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="i in 4" :key="i" class="animate-pulse">
              <div class="aspect-[4/3] rounded-2xl bg-slate-200"></div>
              <div class="h-4 bg-slate-200 rounded mt-3 w-2/3"></div>
              <div class="h-3 bg-slate-100 rounded mt-2 w-1/2"></div>
            </div>
          </div>
          <div v-else-if="error" class="p-6 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            {{ error }}
          </div>
          <div v-else-if="featured.length === 0" class="p-10 text-center rounded-2xl bg-white border border-dashed border-slate-200 text-slate-500 text-sm">
            Aucun véhicule disponible pour le moment.
          </div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" data-testid="featured-grid">
            <PublicCarCard v-for="car in featured" :key="car.id" :car="car" />
          </div>
        </section>

        <!-- CITIES -->
        <section v-if="cities.length">
          <h2 class="font-bricolage text-2xl font-extrabold text-[#0F172A] tracking-tight mb-5">Destinations populaires</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <button
              v-for="city in cities"
              :key="city.id"
              type="button"
              class="relative aspect-square rounded-2xl overflow-hidden bg-slate-800 text-left group"
              @click="goCity(city.id)"
            >
              <img
                v-if="city.image"
                :src="city.image"
                :alt="city.name"
                class="absolute inset-0 w-full h-full object-cover opacity-70 group-hover:opacity-90 group-hover:scale-105 transition-all duration-300"
                loading="lazy"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
              <div class="absolute bottom-3 left-3 right-3 text-white">
                <span class="block font-bricolage font-bold">{{ city.name }}</span>
                <span class="block text-xs text-white/80">{{ city.count }} véhicule{{ city.count > 1 ? 's' : '' }}</span>
              </div>
            </button>
          </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="(step, i) in [
            { title: 'Choisissez', text: 'Filtrez par ville, dates, catégorie et budget. Les photos et avis sont ceux des agences.' },
            { title: 'Réservez', text: 'Sélectionnez vos points de retrait et de restitution, l’agence confirme votre demande.' },
            { title: 'Roulez', text: 'Payez en ligne, récupérez les clés, et confirmez la prise en charge depuis votre espace.' },
          ]" :key="step.title" class="p-6 rounded-2xl bg-white border border-slate-200">
            <span class="inline-flex w-8 h-8 rounded-full bg-[#0F172A] text-white items-center justify-center text-sm font-bold">{{ i + 1 }}</span>
            <h3 class="font-bold text-[#0F172A] mt-4">{{ step.title }}</h3>
            <p class="text-sm text-slate-500 mt-1">{{ step.text }}</p>
          </div>
        </section>

        <!-- AGENCY CTA -->
        <section v-if="!auth.isAuthenticated" class="rounded-3xl bg-[#0F172A] text-white p-8 sm:p-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
          <div>
            <h2 class="font-bricolage text-2xl font-extrabold tracking-tight">Vous êtes une agence de location ?</h2>
            <p class="text-slate-300 mt-2 max-w-xl">
              Publiez votre flotte, gérez vos points de retrait et vos réservations depuis un seul tableau de bord.
            </p>
          </div>
          <RouterLink to="/register" class="px-6 py-3 rounded-xl bg-white text-[#0F172A] font-bold text-sm text-center whitespace-nowrap">
            Inscrire mon agence
          </RouterLink>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hero-bg {
  background-image: url('@/assets/hero.png');
  background-size: cover;
  background-position: center 60%;
}
</style>
