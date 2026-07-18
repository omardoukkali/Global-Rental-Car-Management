<template>
  <div class="fade-in">
    <div v-if="loading" class="flex-center" style="min-height:50vh"><div class="spinner spinner-lg"></div></div>
    <div v-else-if="!car" class="flex-center" style="min-height:40vh;color:var(--muted)">{{ t('car.notFound') }}</div>

    <div v-else>
      <!-- Gallery -->
      <div class="gallery">
        <img :src="mainImage" :alt="`${car.brand} ${car.model}`" class="gallery-main" />
        <div v-if="car.images?.length > 1" class="gallery-thumbs">
          <img
            v-for="img in car.images" :key="img.id"
            :src="resolveUrl(img.url)"
            @click="mainImage = resolveUrl(img.url)"
            class="gallery-thumb"
            :class="{ active: mainImage === resolveUrl(img.url) }"
          />
        </div>
      </div>

      <div class="detail-grid">
        <!-- Car info -->
        <div>
          <div class="flex-between" style="margin-bottom:8px">
            <h1 style="font-size:2rem">{{ car.brand }} {{ car.model }} <span style="color:var(--muted);font-size:1.1rem">{{ car.year }}</span></h1>
            <span class="badge" :class="`badge-${car.status}`">{{ t(`status.${car.status}`, car.status) }}</span>
          </div>
          <p style="color:var(--muted);margin-bottom:20px">📍 {{ t('cities.' + car.city?.name, car.city?.name) }} · {{ car.agency?.name }}</p>

          <div class="specs-row">
            <div class="spec-item"><span class="spec-label">{{ t('car.type') }}</span><span class="spec-val">{{ t(`filter.${car.type}`, car.type) }}</span></div>
            <div class="spec-item"><span class="spec-label">{{ t('car.transmission') }}</span><span class="spec-val">{{ t(`filter.${car.transmission}`, car.transmission) }}</span></div>
            <div class="spec-item"><span class="spec-label">{{ t('car.seats_label') }}</span><span class="spec-val">{{ car.seats }}</span></div>
            <div class="spec-item"><span class="spec-label">{{ t('car.color') }}</span><span class="spec-val">{{ t('colors.' + car.color, car.color) }}</span></div>
          </div>

          <p v-if="car.description" style="color:var(--muted);line-height:1.7;margin-top:20px">{{ car.description }}</p>

          <!-- Reviews -->
          <h3 style="margin-top:36px;margin-bottom:16px">{{ t('car.reviews') }} ({{ reviews.length }})</h3>
          <div v-if="reviews.length" class="reviews-list">
            <div v-for="r in reviews" :key="r.id" class="glass-card review-card">
              <div class="flex-between" style="margin-bottom:6px">
                <strong>{{ r.client?.first_name }} {{ r.client?.last_name }}</strong>
                <div class="stars">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</div>
              </div>
              <p style="color:var(--muted);font-size:0.9rem">{{ r.comment }}</p>
            </div>
          </div>
          <p v-else style="color:var(--muted)">{{ t('car.noReviews') }}</p>
        </div>

        <!-- Booking panel -->
        <div>
          <div class="glass-card booking-card">
            <div class="price-block">
              <span class="price price-value">{{ Number(car.price_per_day).toFixed(0) }} MAD</span>
              <span class="price-label"> {{ t('car.perDay') }}</span>
            </div>

            <template v-if="auth.isAuthenticated && auth.isClient">
              <form @submit.prevent="handleBook">
                <div class="form-group">
                  <label class="form-label">{{ t('car.startDate') }}</label>
                  <input v-model="bookForm.start_date" type="date" class="form-input" :min="tomorrow" required />
                </div>
                <div class="form-group">
                  <label class="form-label">{{ t('car.endDate') }}</label>
                  <input v-model="bookForm.end_date" type="date" class="form-input" :min="bookForm.start_date || tomorrow" required />
                </div>

                <div v-if="totalDays > 0" class="price-summary">
                  <div class="flex-between"><span>{{ totalDays }} {{ t('car.days') }} x <span class="price-value">{{ Number(car.price_per_day).toFixed(0) }} MAD</span></span><span class="price-value">{{ totalAmount }} MAD</span></div>
                  <div class="flex-between" style="color:var(--muted);font-size:0.85rem"><span>{{ t('car.platformFee') }}</span><span class="price-value">-{{ commission }} MAD</span></div>
                  <hr style="border:none;border-top:1px solid var(--line);margin:12px 0"/>
                  <div class="flex-between" style="font-weight:700"><span>{{ t('car.total') }}</span><span style="color:var(--accent)" class="price-value">{{ totalAmount }} MAD</span></div>
                </div>

                <div v-if="bookError" class="error-banner">{{ bookError }}</div>
                <div v-if="bookSuccess" class="success-banner">{{ bookSuccess }}</div>

                <button type="submit" class="btn btn-primary btn-full" :disabled="booking || car.status !== 'available'">
                  <span v-if="booking" class="spinner" style="width:16px;height:16px;border-width:2px"></span>
                  <span v-else-if="car.status !== 'available'">{{ t('car.notAvailable') }}</span>
                  <span v-else>{{ t('car.reserveNow') }}</span>
                </button>
              </form>
            </template>
            <template v-else-if="!auth.isAuthenticated">
              <p style="color:var(--muted);text-align:center;margin-bottom:16px">{{ t('car.signInToBook') }}</p>
              <RouterLink to="/login" class="btn btn-primary btn-full">{{ t('car.signInBtn') }}</RouterLink>
            </template>
            <template v-else>
              <p style="color:var(--muted);text-align:center;font-size:0.9rem">{{ t('car.clientsOnly') }}</p>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const { t } = useI18n()
const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()

const car     = ref(null)
const reviews = ref([])
const loading = ref(true)
const mainImage = ref('')

const bookForm   = reactive({ start_date: '', end_date: '' })
const bookError  = ref('')
const bookSuccess = ref('')
const booking    = ref(false)

const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0]

const totalDays = computed(() => {
  if (!bookForm.start_date || !bookForm.end_date) return 0
  const diff = (new Date(bookForm.end_date) - new Date(bookForm.start_date)) / 86400000
  return Math.max(0, Math.floor(diff))
})
const totalAmount = computed(() => totalDays.value * (car.value?.price_per_day || 0))
const commission  = computed(() => (totalAmount.value * 0.15).toFixed(0))

function resolveUrl(url) {
  if (!url) return 'https://images.unsplash.com/photo-1542282088-fe8426682b8f?auto=format&fit=crop&q=80&w=800'
  return url.startsWith('http') || url.startsWith('/storage/') ? url : `/storage/${url}`
}

async function handleBook() {
  bookError.value = ''
  bookSuccess.value = ''
  booking.value = true
  try {
    await api.post('/client/reservations', { car_id: car.value.id, ...bookForm })
    bookSuccess.value = t('car.reservationConfirmed')
    setTimeout(() => router.push('/client/reservations'), 2500)
  } catch (e) {
    bookError.value = e.message
  } finally {
    booking.value = false
  }
}

onMounted(async () => {
  const id = route.params.id
  try {
    const [carRes, revRes] = await Promise.allSettled([
      api.get(`/cars/${id}`),
      api.get(`/cars/${id}/reviews`)
    ])
    if (carRes.status === 'fulfilled') {
      car.value = carRes.value.data ?? carRes.value
      const primary = car.value.images?.find(i => i.is_primary) || car.value.images?.[0]
      mainImage.value = resolveUrl(primary?.url)
    }
    if (revRes.status === 'fulfilled') {
      reviews.value = revRes.value.data ?? revRes.value
    }
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.gallery { margin-bottom: 32px; }
.gallery-main { width: 100%; height: 380px; object-fit: cover; border-radius: var(--radius-lg); background: linear-gradient(135deg, var(--bg) 0%, var(--line) 100%); border: 1px solid var(--line); }
.gallery-thumbs { display: flex; gap: 8px; margin-top: 8px; overflow-x: auto; }
.gallery-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: var(--radius-sm); cursor: pointer; opacity: 0.6; border: 2px solid transparent; transition: all var(--transition-fast); flex-shrink: 0; }
.gallery-thumb.active, .gallery-thumb:hover { opacity: 1; border-color: var(--accent); }

.detail-grid { display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start; }
@media (max-width: 768px) { .detail-grid { grid-template-columns: 1fr; } }

.specs-row { display: flex; gap: 16px; flex-wrap: wrap; }
.spec-item { display: flex; flex-direction: column; gap: 4px; background: rgba(255,255,255,0.03); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 12px 16px; min-width: 100px; }
.spec-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); }
.spec-val { font-weight: 700; font-size: 0.95rem; text-transform: capitalize; }

.booking-card { padding: 24px; position: sticky; top: 80px; }
.price-block { margin-bottom: 20px; }
.price { font-size: 2rem; font-weight: 800; color: var(--accent); }
.price-label { font-size: 0.9rem; color: var(--muted); }
.price-summary { background: rgba(255,255,255,0.03); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 16px; font-size: 0.9rem; }

.reviews-list { display: flex; flex-direction: column; gap: 12px; }
.review-card { padding: 16px; }
.stars { color: #f59e0b; letter-spacing: 2px; }

.error-banner { background: var(--danger-bg); border: 1px solid rgba(239,68,68,0.3); color: var(--danger); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 0.88rem; margin-bottom: 14px; }
.success-banner { background: var(--success-bg); border: 1px solid rgba(16,185,129,0.3); color: var(--success); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 0.88rem; margin-bottom: 14px; }
</style>
