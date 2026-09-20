<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import PublicCarCard from '@/components/PublicCarCard.vue'
import carsService from '@/services/cars'
import agencyService from '@/services/agency'

const props = defineProps({
  agencyId: { type: String, required: true },
})

const route = useRoute()

const cars = ref([])
const agency = ref(null)
const reviews = ref([])
const reviewsMeta = ref({ current_page: 1, last_page: 1, total: 0 })
const loadingCars = ref(true)
const loadingReviews = ref(true)
const loadingMore = ref(false)
const error = ref('')
const reviewsError = ref('')
const sort = ref('')

const isFound = computed(() => !!agency.value || reviews.value.length > 0)

const points = computed(() => {
  const list = agency.value?.agency_points || agency.value?.agencyPoints || []
  return Array.isArray(list) ? list : []
})

const cities = computed(() => {
  const names = new Set()
  for (const car of cars.value) if (car.city?.name) names.add(car.city.name)
  return [...names]
})

const initials = computed(() => {
  const name = agency.value?.name || 'A'
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() || '')
    .join('')
})

const sortedCars = computed(() => {
  const list = [...cars.value]
  if (sort.value === 'price_asc') list.sort((a, b) => Number(a.daily_price) - Number(b.daily_price))
  else if (sort.value === 'price_desc') list.sort((a, b) => Number(b.daily_price) - Number(a.daily_price))
  else if (sort.value === 'newest') list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
  return list
})

const priceRange = computed(() => {
  if (!cars.value.length) return null
  const prices = cars.value.map((c) => Number(c.daily_price || 0))
  return { min: Math.min(...prices), max: Math.max(...prices) }
})

const ratingFromReviews = computed(() => {
  if (!reviews.value.length) return 0
  const sum = reviews.value.reduce((acc, r) => acc + Number(r.agency_rating || 0), 0)
  return sum / reviews.value.length
})

const rating = computed(() => Number(agency.value?.avg_rating || ratingFromReviews.value || 0))
const reviewCount = computed(() => Number(agency.value?.total_reviews || reviewsMeta.value.total || reviews.value.length || 0))

const ratingBreakdown = computed(() => {
  const counts = [5, 4, 3, 2, 1].map((star) => ({ star, count: 0 }))
  for (const review of reviews.value) {
    const value = Math.round(Number(review.agency_rating || 0))
    const bucket = counts.find((c) => c.star === value)
    if (bucket) bucket.count += 1
  }
  const total = reviews.value.length || 1
  return counts.map((c) => ({ ...c, pct: Math.round((c.count / total) * 100) }))
})

function matchesAgency(car, id) {
  return car.agency_id === id || car.agency?.id === id || car.agency?.slug === id
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('fr-MA')
}

function formatDate(value) {
  if (!value) return ''
  const d = new Date(value)
  return Number.isNaN(d.getTime())
    ? ''
    : d.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
}

function stars(value) {
  const n = Math.round(Number(value || 0))
  return '★'.repeat(Math.max(0, Math.min(5, n))) + '☆'.repeat(Math.max(0, 5 - n))
}

async function loadCars() {
  loadingCars.value = true
  error.value = ''
  try {
    const res = await carsService.getPublicCars()
    const all = res?.cars || res?.data?.cars || []
    cars.value = all.filter((car) => matchesAgency(car, props.agencyId))
    agency.value = cars.value[0]?.agency || null
  } catch (err) {
    error.value = err?.message || 'Impossible de charger la flotte de l’agence.'
    cars.value = []
  } finally {
    loadingCars.value = false
  }
}

function extractReviews(res) {
  const payload = res?.reviews || res?.data?.reviews
  if (Array.isArray(payload)) return { items: payload, meta: { current_page: 1, last_page: 1, total: payload.length } }
  return {
    items: payload?.data || [],
    meta: {
      current_page: payload?.current_page || 1,
      last_page: payload?.last_page || 1,
      total: payload?.total ?? (payload?.data?.length || 0),
    },
  }
}

async function loadReviews(page = 1) {
  if (page === 1) loadingReviews.value = true
  else loadingMore.value = true
  reviewsError.value = ''
  try {
    const res = await agencyService.getPublicAgencyReviews(props.agencyId, { page })
    const { items, meta } = extractReviews(res)
    reviews.value = page === 1 ? items : [...reviews.value, ...items]
    reviewsMeta.value = meta
  } catch (err) {
    if (err?.status === 404) reviewsError.value = 'Agence introuvable.'
    else reviewsError.value = err?.message || 'Impossible de charger les avis.'
    if (page === 1) reviews.value = []
  } finally {
    loadingReviews.value = false
    loadingMore.value = false
  }
}

function loadMoreReviews() {
  if (reviewsMeta.value.current_page >= reviewsMeta.value.last_page) return
  loadReviews(reviewsMeta.value.current_page + 1)
}

async function loadAll() {
  await Promise.all([loadCars(), loadReviews(1)])
}

watch(() => props.agencyId, loadAll)
onMounted(loadAll)
</script>

<template>
  <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC]" data-testid="agency-public">
    <div v-if="loadingCars && loadingReviews" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center text-slate-500">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-slate-800 mb-3"></div>
      <p class="text-sm">Chargement de l’agence…</p>
    </div>

    <div
      v-else-if="!isFound"
      class="max-w-3xl mx-auto px-4 py-20 text-center"
      data-testid="agency-not-found"
    >
      <h1 class="font-bricolage text-2xl font-extrabold text-[#0F172A]">Agence introuvable</h1>
      <p class="text-sm text-slate-500 mt-2">
        Cette agence n’existe pas, n’est pas encore approuvée, ou n’a aucun véhicule disponible pour le moment.
      </p>
      <p v-if="error || reviewsError" class="text-xs text-rose-600 mt-2">{{ error || reviewsError }}</p>
      <RouterLink to="/cars" class="inline-block mt-6 px-5 py-2.5 rounded-xl bg-[#0F172A] text-white text-sm font-semibold">
        Voir tous les véhicules
      </RouterLink>
    </div>

    <template v-else>
      <!-- HEADER -->
      <section class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
          <RouterLink :to="route.query.from || '/cars'" class="text-xs font-bold text-slate-500 hover:text-slate-800 inline-flex items-center gap-1 mb-6">
            ← Retour au catalogue
          </RouterLink>

          <div class="flex flex-col sm:flex-row sm:items-start gap-6">
            <div class="w-20 h-20 rounded-2xl bg-[#0F172A] text-white flex items-center justify-center font-bricolage font-extrabold text-2xl shrink-0">
              {{ initials }}
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-bricolage text-3xl sm:text-4xl font-extrabold text-[#0F172A] tracking-tight" data-testid="agency-name">
                  {{ agency?.name || 'Agence' }}
                </h1>
                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold uppercase tracking-wider">
                  Agence vérifiée
                </span>
              </div>

              <p class="mt-2 text-sm text-slate-600 flex flex-wrap items-center gap-x-3 gap-y-1">
                <span v-if="rating" class="font-bold text-[#0F172A]">
                  {{ rating.toFixed(1) }} <span class="text-amber-500">★</span>
                  <span class="font-normal text-slate-500">({{ reviewCount }} avis)</span>
                </span>
                <span v-if="agency?.address">📍 {{ agency.address }}</span>
                <span v-if="cities.length">· {{ cities.join(', ') }}</span>
              </p>

              <p v-if="agency?.description" class="mt-3 text-sm text-slate-600 max-w-2xl">{{ agency.description }}</p>

              <div class="mt-4 flex flex-wrap gap-2 text-xs">
                <span v-if="agency?.phone" class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 font-semibold">
                  {{ agency.phone }}
                </span>
                <span v-if="agency?.email" class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 font-semibold">
                  {{ agency.email }}
                </span>
                <span v-if="agency?.created_at" class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-500 font-semibold">
                  Partenaire depuis {{ formatDate(agency.created_at) }}
                </span>
              </div>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-1 gap-3 sm:w-40 shrink-0">
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-center sm:text-left">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Véhicules</div>
                <div class="font-bricolage text-xl font-extrabold text-[#0F172A]">{{ cars.length }}</div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-center sm:text-left">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Points</div>
                <div class="font-bricolage text-xl font-extrabold text-[#0F172A]">{{ points.length }}</div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-center sm:text-left">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Dès</div>
                <div class="font-bricolage text-xl font-extrabold text-[#0F172A]">
                  <template v-if="priceRange">{{ formatMoney(priceRange.min) }}</template>
                  <template v-else>—</template>
                  <span class="text-xs font-normal text-slate-400"> MAD/j</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12">
        <!-- POINTS -->
        <section v-if="points.length">
          <h2 class="font-bricolage text-xl font-extrabold text-[#0F172A] tracking-tight mb-4">Points de retrait & restitution</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div v-for="point in points" :key="point.id" class="p-4 rounded-2xl bg-white border border-slate-200">
              <p class="font-bold text-sm text-[#0F172A]">{{ point.name }}</p>
              <p class="text-xs text-slate-500 mt-1">📍 {{ point.address }}</p>
              <div class="flex gap-2 mt-2">
                <span v-if="point.allows_pickup" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700">Prise en charge</span>
                <span v-if="point.allows_return" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-violet-50 text-violet-700">Restitution</span>
              </div>
            </div>
          </div>
        </section>

        <!-- FLEET -->
        <section>
          <div class="flex items-end justify-between gap-4 mb-5">
            <div>
              <h2 class="font-bricolage text-xl font-extrabold text-[#0F172A] tracking-tight">
                Flotte disponible
                <span class="text-slate-400 font-semibold text-base">({{ cars.length }})</span>
              </h2>
              <p v-if="priceRange && cars.length > 1" class="text-sm text-slate-500 mt-1">
                De {{ formatMoney(priceRange.min) }} à {{ formatMoney(priceRange.max) }} MAD par jour.
              </p>
            </div>
            <label v-if="cars.length > 1" class="text-sm text-slate-500 flex items-center gap-2">
              Trier
              <select v-model="sort" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-[#0F172A] bg-white">
                <option value="">Pertinence</option>
                <option value="price_asc">Prix croissant</option>
                <option value="price_desc">Prix décroissant</option>
                <option value="newest">Nouveautés</option>
              </select>
            </label>
          </div>

          <div v-if="loadingCars" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="i in 3" :key="i" class="animate-pulse">
              <div class="aspect-[4/3] rounded-2xl bg-slate-200"></div>
              <div class="h-4 bg-slate-200 rounded mt-3 w-2/3"></div>
            </div>
          </div>
          <div v-else-if="error" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">{{ error }}</div>
          <div
            v-else-if="cars.length === 0"
            class="p-10 text-center rounded-2xl bg-white border border-dashed border-slate-200 text-slate-500 text-sm"
          >
            Aucun véhicule disponible pour le moment chez cette agence.
          </div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" data-testid="agency-fleet">
            <PublicCarCard v-for="car in sortedCars" :key="car.id" :car="car" />
          </div>
        </section>

        <!-- REVIEWS -->
        <section id="reviews">
          <h2 class="font-bricolage text-xl font-extrabold text-[#0F172A] tracking-tight mb-5">
            Avis clients
            <span class="text-slate-400 font-semibold text-base">({{ reviewCount }})</span>
          </h2>

          <div v-if="loadingReviews" class="text-sm text-slate-500 py-8 text-center">Chargement des avis…</div>
          <div v-else-if="reviewsError" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">{{ reviewsError }}</div>
          <div
            v-else-if="reviews.length === 0"
            class="p-10 text-center rounded-2xl bg-white border border-dashed border-slate-200 text-slate-500 text-sm"
            data-testid="no-reviews"
          >
            Cette agence n’a pas encore reçu d’avis.
          </div>

          <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 p-6 rounded-2xl bg-white border border-slate-200 self-start">
              <div class="flex items-baseline gap-2">
                <span class="font-bricolage text-4xl font-extrabold text-[#0F172A]">{{ rating.toFixed(1) }}</span>
                <span class="text-amber-500 text-xl">★</span>
              </div>
              <p class="text-xs text-slate-500 mt-1">Note agence moyenne · {{ reviewCount }} avis</p>
              <ul class="mt-4 space-y-2">
                <li v-for="row in ratingBreakdown" :key="row.star" class="flex items-center gap-2 text-xs">
                  <span class="w-6 text-slate-500 font-semibold">{{ row.star }}★</span>
                  <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-[#0F172A]" :style="{ width: row.pct + '%' }"></div>
                  </div>
                  <span class="w-6 text-right text-slate-400">{{ row.count }}</span>
                </li>
              </ul>
            </div>

            <ul class="lg:col-span-2 space-y-4" data-testid="agency-reviews">
              <li v-for="review in reviews" :key="review.id" class="p-5 rounded-2xl bg-white border border-slate-200">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-bold text-sm text-[#0F172A]">{{ review.user?.first_name || 'Client' }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                      <template v-if="review.reservation?.car">
                        {{ review.reservation.car.brand }} {{ review.reservation.car.model }} ·
                      </template>
                      {{ formatDate(review.created_at) }}
                    </p>
                  </div>
                  <div class="text-right shrink-0">
                    <p class="text-amber-500 text-sm tracking-tight">{{ stars(review.agency_rating) }}</p>
                    <p class="text-[10px] text-slate-400">
                      Agence {{ Number(review.agency_rating).toFixed(0) }}/5
                      <template v-if="review.car_rating"> · Véhicule {{ Number(review.car_rating).toFixed(0) }}/5</template>
                    </p>
                  </div>
                </div>
                <p v-if="review.comment" class="text-sm text-slate-700 mt-3 leading-relaxed">{{ review.comment }}</p>
              </li>

              <li v-if="reviewsMeta.current_page < reviewsMeta.last_page" class="text-center pt-2">
                <button
                  type="button"
                  class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-[#0F172A] hover:bg-slate-50 disabled:opacity-50"
                  :disabled="loadingMore"
                  @click="loadMoreReviews"
                >
                  {{ loadingMore ? 'Chargement…' : 'Voir plus d’avis' }}
                </button>
              </li>
            </ul>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>
