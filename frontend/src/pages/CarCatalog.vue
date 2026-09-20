<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CarSearchBar from '@/components/CarSearchBar.vue'
import PublicCarCard from '@/components/PublicCarCard.vue'
import carsService from '@/services/cars'

const route = useRoute()
const router = useRouter()

const TYPES = [
  { value: 'sedan', label: 'Berline' },
  { value: 'suv', label: 'SUV' },
  { value: 'hatchback', label: 'Citadine' },
  { value: 'coupe', label: 'Coupé' },
  { value: 'van', label: 'Van' },
  { value: 'truck', label: 'Utilitaire' },
]
const TRANSMISSIONS = [
  { value: 'manual', label: 'Manuelle' },
  { value: 'automatic', label: 'Automatique' },
]
const ENERGIES = [
  { value: 'gasoline', label: 'Essence' },
  { value: 'diesel', label: 'Diesel' },
  { value: 'hybrid', label: 'Hybride' },
  { value: 'electric', label: 'Électrique' },
]
const SORTS = [
  { value: '', label: 'Pertinence' },
  { value: 'price_asc', label: 'Prix croissant' },
  { value: 'price_desc', label: 'Prix décroissant' },
  { value: 'newest', label: 'Nouveautés' },
]

const FILTER_KEYS = [
  'q', 'city_id', 'start_at', 'end_at', 'type', 'transmission',
  'energy_type', 'min_seats', 'min_price', 'max_price', 'sort',
]

function filtersFromRoute() {
  const out = {}
  for (const key of FILTER_KEYS) {
    const value = route.query[key]
    out[key] = typeof value === 'string' ? value : ''
  }
  return out
}

const filters = reactive(filtersFromRoute())
const cars = ref([])
const loading = ref(true)
const error = ref('')
const validationErrors = ref({})
const filtersOpen = ref(false)

const search = computed({
  get: () => ({
    q: filters.q,
    city_id: filters.city_id,
    start_at: filters.start_at,
    end_at: filters.end_at,
  }),
  set: (value) => {
    filters.q = value.q || ''
    filters.city_id = value.city_id || ''
    filters.start_at = value.start_at || ''
    filters.end_at = value.end_at || ''
  },
})

const activeFilterCount = computed(() =>
  ['type', 'transmission', 'energy_type', 'min_seats', 'min_price', 'max_price']
    .filter((key) => filters[key] !== '' && filters[key] !== null).length
)

const dateQuery = computed(() => ({ start_at: filters.start_at, end_at: filters.end_at }))

function apiParams() {
  const params = {}
  for (const key of FILTER_KEYS) {
    const value = filters[key]
    if (value === '' || value === null || value === undefined) continue
    params[key] = value
  }
  // Dates only make sense as a pair
  if (!params.start_at || !params.end_at) {
    delete params.start_at
    delete params.end_at
  }
  return params
}

function syncRoute() {
  const query = apiParams()
  const current = JSON.stringify(route.query)
  if (current !== JSON.stringify(query)) {
    router.replace({ path: '/cars', query })
  }
}

let requestId = 0
async function load() {
  const id = ++requestId
  loading.value = true
  error.value = ''
  validationErrors.value = {}
  try {
    const res = await carsService.getPublicCars(apiParams())
    if (id !== requestId) return
    cars.value = res?.cars || res?.data?.cars || []
  } catch (err) {
    if (id !== requestId) return
    cars.value = []
    if (err?.status === 422 && err.errors) {
      validationErrors.value = err.errors
      error.value = Object.values(err.errors).flat()[0] || 'Filtres invalides.'
    } else {
      error.value = err?.message || 'Impossible de charger le catalogue.'
    }
  } finally {
    if (id === requestId) loading.value = false
  }
}

function applyAndLoad() {
  syncRoute()
  load()
}

function onSearch(value) {
  search.value = value
  applyAndLoad()
}

function toggle(key, value) {
  filters[key] = filters[key] === value ? '' : value
  applyAndLoad()
}

function resetFilters() {
  for (const key of ['type', 'transmission', 'energy_type', 'min_seats', 'min_price', 'max_price', 'sort']) {
    filters[key] = ''
  }
  applyAndLoad()
}

const resultLabel = computed(() => {
  if (loading.value) return 'Recherche…'
  const n = cars.value.length
  if (n === 0) return 'Aucun véhicule'
  return `${n} véhicule${n > 1 ? 's' : ''} disponible${n > 1 ? 's' : ''}`
})

watch(
  () => route.query,
  () => {
    const next = filtersFromRoute()
    let changed = false
    for (const key of FILTER_KEYS) {
      if (filters[key] !== next[key]) {
        filters[key] = next[key]
        changed = true
      }
    }
    if (changed) load()
  }
)

onMounted(load)
</script>

<template>
  <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC]" data-testid="car-catalog">
    <div class="bg-white border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
        <CarSearchBar :model-value="search" compact @search="onSearch" />
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-4 gap-8">
      <!-- FILTERS -->
      <aside class="lg:col-span-1">
        <div class="flex items-center justify-between lg:hidden mb-3">
          <button
            type="button"
            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold"
            @click="filtersOpen = !filtersOpen"
          >
            Filtres <span v-if="activeFilterCount" class="ml-1 text-slate-500">({{ activeFilterCount }})</span>
          </button>
          <span class="text-sm text-slate-500">{{ resultLabel }}</span>
        </div>

        <div
          class="bg-white rounded-2xl border border-slate-200 p-5 space-y-6 lg:sticky lg:top-24"
          :class="filtersOpen ? 'block' : 'hidden lg:block'"
          data-testid="catalog-filters"
        >
          <div class="flex items-center justify-between">
            <h2 class="font-bold text-[#0F172A]">Filtres</h2>
            <button
              v-if="activeFilterCount || filters.sort"
              type="button"
              class="text-xs font-semibold text-slate-500 hover:text-slate-800"
              @click="resetFilters"
            >
              Réinitialiser
            </button>
          </div>

          <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Catégorie</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="t in TYPES"
                :key="t.value"
                type="button"
                class="px-3 py-1.5 rounded-full text-xs font-semibold border"
                :class="filters.type === t.value
                  ? 'bg-[#0F172A] text-white border-[#0F172A]'
                  : 'bg-white text-slate-700 border-slate-200 hover:border-slate-400'"
                @click="toggle('type', t.value)"
              >
                {{ t.label }}
              </button>
            </div>
          </div>

          <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Transmission</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="t in TRANSMISSIONS"
                :key="t.value"
                type="button"
                class="px-3 py-1.5 rounded-full text-xs font-semibold border"
                :class="filters.transmission === t.value
                  ? 'bg-[#0F172A] text-white border-[#0F172A]'
                  : 'bg-white text-slate-700 border-slate-200 hover:border-slate-400'"
                @click="toggle('transmission', t.value)"
              >
                {{ t.label }}
              </button>
            </div>
          </div>

          <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Énergie</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="e in ENERGIES"
                :key="e.value"
                type="button"
                class="px-3 py-1.5 rounded-full text-xs font-semibold border"
                :class="filters.energy_type === e.value
                  ? 'bg-[#0F172A] text-white border-[#0F172A]'
                  : 'bg-white text-slate-700 border-slate-200 hover:border-slate-400'"
                @click="toggle('energy_type', e.value)"
              >
                {{ e.label }}
              </button>
            </div>
          </div>

          <div>
            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 block" for="filter-seats">
              Places minimum
            </label>
            <select
              id="filter-seats"
              v-model="filters.min_seats"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
              @change="applyAndLoad"
            >
              <option value="">Indifférent</option>
              <option v-for="n in [2, 4, 5, 7, 9]" :key="n" :value="String(n)">{{ n }}+ places</option>
            </select>
          </div>

          <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Prix / jour (MAD)</p>
            <div class="grid grid-cols-2 gap-2">
              <input
                v-model="filters.min_price"
                type="number"
                min="0"
                step="50"
                placeholder="Min"
                class="rounded-xl border border-slate-200 px-3 py-2 text-sm"
                :class="{ 'border-rose-400': validationErrors.min_price }"
                @change="applyAndLoad"
              />
              <input
                v-model="filters.max_price"
                type="number"
                min="0"
                step="50"
                placeholder="Max"
                class="rounded-xl border border-slate-200 px-3 py-2 text-sm"
                :class="{ 'border-rose-400': validationErrors.max_price }"
                @change="applyAndLoad"
              />
            </div>
          </div>
        </div>
      </aside>

      <!-- RESULTS -->
      <section class="lg:col-span-3">
        <div class="hidden lg:flex items-center justify-between gap-4 mb-5">
          <h1 class="font-bricolage text-2xl font-extrabold text-[#0F172A] tracking-tight" data-testid="result-count">
            {{ resultLabel }}
          </h1>
          <label class="text-sm text-slate-500 flex items-center gap-2">
            Trier
            <select
              v-model="filters.sort"
              class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-[#0F172A] bg-white"
              @change="applyAndLoad"
            >
              <option v-for="s in SORTS" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </label>
        </div>

        <div v-if="error" class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
          {{ error }}
        </div>

        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
          <div v-for="i in 6" :key="i" class="animate-pulse">
            <div class="aspect-[4/3] rounded-2xl bg-slate-200"></div>
            <div class="h-4 bg-slate-200 rounded mt-3 w-2/3"></div>
            <div class="h-3 bg-slate-100 rounded mt-2 w-1/2"></div>
          </div>
        </div>

        <div
          v-else-if="cars.length === 0"
          class="p-12 text-center rounded-2xl bg-white border border-dashed border-slate-200"
          data-testid="empty-results"
        >
          <p class="font-bold text-[#0F172A]">Aucun véhicule ne correspond à votre recherche.</p>
          <p class="text-sm text-slate-500 mt-1">Essayez d’autres dates, une autre ville ou moins de filtres.</p>
          <button
            type="button"
            class="mt-5 px-5 py-2.5 rounded-xl bg-[#0F172A] text-white text-sm font-semibold"
            @click="resetFilters"
          >
            Effacer les filtres
          </button>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6" data-testid="results-grid">
          <PublicCarCard v-for="car in cars" :key="car.id" :car="car" :query="dateQuery" />
        </div>
      </section>
    </div>
  </div>
</template>
