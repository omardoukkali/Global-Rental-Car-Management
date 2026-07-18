<template>
  <div class="fade-in">
    <h1 style="margin-bottom:8px">{{ t('client.title') }}</h1>
    <p style="color:var(--muted);margin-bottom:28px">{{ t('client.sub') }}</p>

    <div v-if="loading" class="flex-center" style="padding:64px 0"><div class="spinner spinner-lg"></div></div>

    <div v-else-if="!reservations.length" class="empty-state glass-card">
      <CalendarIcon class="empty-icon" />
      <p>{{ t('client.noReservations') }}</p>
      <RouterLink to="/" class="btn btn-primary btn-sm" style="margin-top:12px">{{ t('client.browseCars') }}</RouterLink>
    </div>

    <div v-else class="res-list">
      <div v-for="r in reservations" :key="r.id" class="glass-card res-card">
        <div class="res-img-col">
          <img :src="carImage(r.car)" :alt="`${r.car?.brand} ${r.car?.model}`" class="res-img" />
        </div>
        <div class="res-body">
          <div class="flex-between" style="margin-bottom:6px">
            <h3>{{ r.car?.brand }} {{ r.car?.model }}</h3>
            <span class="badge" :class="`badge-${r.status}`">{{ t(`status.${r.status}`, r.status) }}</span>
          </div>
          <p style="color:var(--muted);font-size:0.88rem">
            📍 {{ t('cities.' + r.car?.city?.name, r.car?.city?.name) }} · {{ t('client.ref') }}: <strong>{{ r.reference_number }}</strong>
          </p>
          <p style="color:var(--muted);font-size:0.88rem;margin-top:4px">
            📅 {{ formatDate(r.start_date) }} → {{ formatDate(r.end_date) }}
          </p>
          <p style="margin-top:8px;font-weight:700;color:var(--accent)"><span class="price-value">{{ Number(r.total_amount).toFixed(0) }} MAD</span> {{ t('client.total') }}</p>

          <!-- Actions -->
          <div class="res-actions">
            <!-- Pay -->
            <button
              v-if="r.status === 'confirmed' && !r.payment"
              class="btn btn-success btn-sm"
              :disabled="paying === r.id"
              @click="handlePay(r)"
            >
              <span v-if="paying === r.id" class="spinner" style="width:14px;height:14px;border-width:2px"></span>
              <span v-else>{{ t('client.payNow') }}</span>
            </button>

            <!-- Refund -->
            <button
              v-if="r.payment?.status === 'paid'"
              class="btn btn-secondary btn-sm"
              :disabled="refunding === r.id"
              @click="handleRefund(r)"
            >
              <span v-if="refunding === r.id" class="spinner" style="width:14px;height:14px;border-width:2px"></span>
              <span v-else>{{ t('client.requestRefund') }}</span>
            </button>

            <!-- Cancel -->
            <button
              v-if="['confirmed','pending'].includes(r.status) && !r.payment?.status === 'paid'"
              class="btn btn-danger btn-sm"
              :disabled="cancelling === r.id"
              @click="handleCancel(r)"
            >{{ t('client.cancel') }}</button>

            <!-- Review -->
            <button
              v-if="r.status === 'completed' && !r.review"
              class="btn btn-secondary btn-sm"
              @click="openReview(r)"
            >{{ t('client.leaveReview') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Review Modal -->
    <div v-if="reviewModal" class="modal-overlay" @click.self="reviewModal = null">
      <div class="glass-card modal-box">
        <h3 style="margin-bottom:16px">{{ t('client.leaveReviewTitle') }}</h3>
        <div class="form-group">
          <label class="form-label">{{ t('client.carRating') }}</label>
          <div class="star-pick">
            <button v-for="n in 5" :key="'car'+n" type="button" @click="reviewForm.car_rating = n"
              :class="['star-btn', { filled: n <= reviewForm.car_rating }]">★</button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">{{ t('client.agencyRating') }}</label>
          <div class="star-pick">
            <button v-for="n in 5" :key="'agency'+n" type="button" @click="reviewForm.agency_rating = n"
              :class="['star-btn', { filled: n <= reviewForm.agency_rating }]">★</button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">{{ t('client.comment') }}</label>
          <textarea v-model="reviewForm.comment" class="form-input" rows="3" :placeholder="t('client.sharePlaceholder')"></textarea>
        </div>
        <div style="display:flex;gap:10px">
          <button class="btn btn-secondary btn-sm flex-1" @click="reviewModal = null">{{ t('client.cancel') }}</button>
          <button class="btn btn-primary btn-sm flex-1" :disabled="submittingReview" @click="submitReview">
            <span v-if="submittingReview" class="spinner" style="width:14px;height:14px;border-width:2px"></span>
            <span v-else>{{ t('client.submit') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import api from '@/services/api'

const { t } = useI18n()
const toast         = inject('toast')
const reservations  = ref([])
const loading       = ref(true)
const paying        = ref(null)
const refunding     = ref(null)
const cancelling    = ref(null)
const reviewModal   = ref(null)
const submittingReview = ref(false)
const reviewForm    = ref({ car_rating: 5, agency_rating: 5, comment: '' })

function carImage(car) {
  const img = car.images?.find(i => i.is_primary) || car.images?.[0]
  if (!img) return 'https://images.unsplash.com/photo-1542282088-fe8426682b8f?auto=format&fit=crop&q=80&w=800'
  return img.url.startsWith('http') || img.url.startsWith('/storage/') ? img.url : `/storage/${img.url}`
}
function formatDate(d) { return new Date(d).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }) }

async function load() {
  loading.value = true
  try {
    const data = await api.get('/client/reservations')
    reservations.value = data.data
  } finally {
    loading.value = false
  }
}

async function handlePay(r) {
  paying.value = r.id
  try {
    await api.post(`/client/reservations/${r.id}/pay`)
    toast(t('client.paymentSuccess'), 'success')
    load()
  } catch (e) { toast(e.message, 'error') } finally { paying.value = null }
}

async function handleRefund(r) {
  refunding.value = r.id
  try {
    await api.post(`/client/reservations/${r.id}/refund`)
    toast(t('client.refundRequested'), 'success')
    load()
  } catch (e) { toast(e.message, 'error') } finally { refunding.value = null }
}

async function handleCancel(r) {
  if (!confirm(t('client.confirmCancel'))) return
  cancelling.value = r.id
  try {
    await api.delete(`/client/reservations/${r.id}`)
    toast(t('client.reservationCancelled'), 'success')
    load()
  } catch (e) { toast(e.message, 'error') } finally { cancelling.value = null }
}

function openReview(r) {
  reviewModal.value = r
  reviewForm.value = { car_rating: 5, agency_rating: 5, comment: '' }
}

async function submitReview() {
  submittingReview.value = true
  try {
    await api.post(`/client/reservations/${reviewModal.value.id}/review`, reviewForm.value)
    toast(t('client.reviewSubmitted'), 'success')
    reviewModal.value = null
    load()
  } catch (e) { toast(e.message, 'error') } finally { submittingReview.value = false }
}

onMounted(load)
</script>

<style scoped>
.res-list { display: flex; flex-direction: column; gap: 16px; }
.res-card { display: flex; gap: 0; overflow: hidden; }
.res-img-col { width: 160px; flex-shrink: 0; }
.res-img { width: 100%; height: 100%; object-fit: cover; }
.res-body { flex: 1; padding: 18px; }
.res-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
@media (max-width: 600px) { .res-card { flex-direction: column; } .res-img-col { width: 100%; height: 160px; } }

.empty-state { text-align: center; padding: 64px 32px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.empty-icon { width: 48px; height: 48px; color: var(--muted); }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-box { max-width: 420px; width: 90%; padding: 28px; }
.star-pick { display: flex; gap: 6px; }
.star-btn { background: none; border: none; font-size: 1.8rem; color: var(--line); cursor: pointer; transition: color var(--transition-fast); }
.star-btn.filled { color: #f59e0b; }
.flex-1 { flex: 1; }
</style>
