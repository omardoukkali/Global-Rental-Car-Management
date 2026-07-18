<template>
  <div class="fade-in">
    <!-- Hero -->
    <header class="hero">
      <span class="hero-tag">{{ t('hero.tagline') }}</span>
      <h1 class="hero-title">{{ t('hero.title') }}<br /><span class="hero-accent">{{ t('hero.titleAccent') }}</span></h1>
      <p class="hero-sub">{{ t('hero.sub') }}</p>
    </header>

    <!-- Filter panel -->
    <section class="glass-card filter-panel">
      <div class="filter-grid">
        <div class="form-group" style="margin:0">
          <label class="form-label">{{ t('filter.search') }}</label>
          <input v-model="filters.search" type="text" class="form-input" :placeholder="t('filter.searchPlaceholder')" />
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">{{ t('filter.city') }}</label>
          <select v-model="filters.city_id" class="form-input">
            <option value="">{{ t('filter.anywhere') }}</option>
            <option v-for="c in cities" :key="c.id" :value="c.id">{{ t('cities.' + c.name, c.name) }}</option>
          </select>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">{{ t('filter.type') }}</label>
          <select v-model="filters.type" class="form-input">
            <option value="">{{ t('filter.allTypes') }}</option>
            <option value="sedan">{{ t('filter.sedan') }}</option>
            <option value="suv">{{ t('filter.suv') }}</option>
            <option value="hatchback">{{ t('filter.hatchback') }}</option>
            <option value="coupe">{{ t('filter.coupe') }}</option>
            <option value="van">{{ t('filter.van') }}</option>
            <option value="truck">{{ t('filter.truck') }}</option>
          </select>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">{{ t('filter.transmission') }}</label>
          <select v-model="filters.transmission" class="form-input">
            <option value="">{{ t('filter.any') }}</option>
            <option value="automatic">{{ t('filter.automatic') }}</option>
            <option value="manual">{{ t('filter.manual') }}</option>
          </select>
        </div>
      </div>
      <hr class="divider" />
      <div class="filter-row">
        <div style="flex:1; max-width:440px">
          <div class="flex-between" style="margin-bottom:8px">
            <span class="form-label">{{ t('filter.maxPrice') }}</span>
            <span style="color:var(--accent);font-weight:700" class="price-value">{{ filters.max_price }} MAD</span>
          </div>
          <input type="range" min="100" max="5000" step="50" v-model.number="filters.max_price" class="price-range" />
        </div>
        <button class="btn btn-secondary btn-sm" @click="resetFilters">{{ t('filter.clearFilters') }}</button>
      </div>
    </section>

    <!-- Results -->
    <div v-if="loading" class="flex-center" style="padding:64px 0">
      <div class="spinner spinner-lg"></div>
    </div>
    <div v-else-if="!cars.length" class="empty-state">
      <p>{{ t('landing.noCars') }}</p>
    </div>
    <div v-else class="cars-grid">
      <RouterLink v-for="car in cars" :key="car.id" :to="`/cars/${car.id}`" class="car-card glass-card">
        <div class="car-img-wrap">
          <img :src="carImage(car)" :alt="`${car.brand} ${car.model}`" class="car-img" loading="lazy" />
          <span class="car-type-tag">{{ t(`filter.${car.type}`, car.type) }}</span>
        </div>
        <div class="car-body">
          <div class="flex-between" style="margin-bottom:6px">
            <h3 class="car-name">{{ car.brand }} {{ car.model }}</h3>
            <span class="car-year">{{ car.year }}</span>
          </div>
          <p class="car-city">📍 {{ t('cities.' + car.city?.name, car.city?.name) }}</p>
          <div class="car-specs">
            <span>{{ t(`filter.${car.transmission}`, car.transmission) }}</span>
            <span>{{ car.seats }} {{ t('car.seats') }}</span>
            <span class="badge" :class="`badge-${car.status}`">{{ t(`status.${car.status}`, car.status) }}</span>
          </div>
          <div class="flex-between car-footer">
            <div>
              <span class="car-price price-value">{{ Number(car.price_per_day).toFixed(0) }} MAD</span>
              <span class="car-price-label"> {{ t('car.perDay') }}</span>
            </div>
            <span class="btn btn-primary btn-sm">{{ t('car.view') }}</span>
          </div>
        </div>
      </RouterLink>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button class="btn btn-secondary btn-sm" :disabled="pagination.current_page === 1" @click="goPage(pagination.current_page - 1)">{{ t('pagination.prev') }}</button>
      <span style="color:var(--muted);font-size:0.9rem">{{ t('pagination.page', { current: pagination.current_page, total: pagination.last_page }) }}</span>
      <button class="btn btn-secondary btn-sm" :disabled="pagination.current_page === pagination.last_page" @click="goPage(pagination.current_page + 1)">{{ t('pagination.next') }}</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

const { t } = useI18n()

const cars    = ref([])
const cities  = ref([])
const loading = ref(false)
const pagination = ref({ current_page: 1, last_page: 1 })

const filters = reactive({
  search: '', city_id: '', type: '', transmission: '', max_price: 5000, page: 1
})

const defaultFilters = { search: '', city_id: '', type: '', transmission: '', max_price: 5000, page: 1 }

function resetFilters() { Object.assign(filters, defaultFilters) }

function carImage(car) {
  const img = car.images?.find(i => i.is_primary) || car.images?.[0]
  if (!img) return 'https://images.unsplash.com/photo-1542282088-fe8426682b8f?auto=format&fit=crop&q=80&w=800'
  if (img.url.startsWith('http') || img.url.startsWith('/storage/')) return img.url
  return `/storage/${img.url}`
}

async function fetchCars() {
  loading.value = true
  try {
    const params = {}
    if (filters.search)       params.brand        = filters.search
    if (filters.city_id)      params.city_id      = filters.city_id
    if (filters.type)         params.type         = filters.type
    if (filters.transmission) params.transmission = filters.transmission
    if (filters.max_price < 5000) params.max_price = filters.max_price
    params.page = filters.page

    const data = await api.get('/cars', { params })
    cars.value       = data.data
    pagination.value = { current_page: data.meta.current_page, last_page: data.meta.last_page }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function goPage(page) { filters.page = page }

let debounce
watch(filters, () => {
  clearTimeout(debounce)
  debounce = setTimeout(fetchCars, 350)
}, { deep: true })

onMounted(async () => {
  const [, citiesRes] = await Promise.allSettled([fetchCars(), api.get('/cities')])
  if (citiesRes.status === 'fulfilled') cities.value = citiesRes.value.data ?? citiesRes.value
})
</script>

<style scoped>
.hero { text-align: center; padding: 56px 0 48px; }
.hero-tag { display: inline-block; background: var(--accent-soft); color: var(--accent); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; padding: 6px 16px; border-radius: 100px; margin-bottom: 20px; }
.hero-title { font-size: clamp(2rem, 5vw, 3.5rem); line-height: 1.1; margin-bottom: 16px; }
.hero-accent { color: var(--accent); }
.hero-sub { color: var(--muted); font-size: 1.1rem; }

.filter-panel { padding: 28px 32px; margin-bottom: 32px; }
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; }
.filter-row { display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
.price-range { width: 100%; accent-color: var(--primary); }

.cars-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-bottom: 40px; }
.car-card { display: flex; flex-direction: column; overflow: hidden; text-decoration: none; }
.car-img-wrap { position: relative; height: 180px; overflow: hidden; background: linear-gradient(135deg, var(--bg) 0%, var(--line) 100%); border-bottom: 1px solid var(--line); }
.car-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.car-card:hover .car-img { transform: scale(1.04); }
.car-type-tag { position: absolute; top: 12px; inset-inline-start: 12px; background: rgba(0,0,0,0.6); color: var(--on-accent); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; padding: 4px 10px; border-radius: 4px; backdrop-filter: blur(4px); }
.car-body { padding: 18px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.car-name { font-size: 1.05rem; font-weight: 700; }
.car-year { font-size: 0.85rem; color: var(--muted); }
.car-city { font-size: 0.85rem; color: var(--muted); }
.car-specs { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--muted); flex-wrap: wrap; }
.car-footer { margin-top: auto; }
.car-price { font-size: 1.25rem; font-weight: 800; color: var(--accent); }
.car-price-label { font-size: 0.8rem; color: var(--muted); }

.empty-state { text-align: center; padding: 80px 0; color: var(--muted); font-size: 1.1rem; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 20px; padding: 32px 0; }
</style>
