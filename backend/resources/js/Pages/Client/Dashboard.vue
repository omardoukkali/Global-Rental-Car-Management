<template>
  <AppLayout>
    <div class="client-dashboard">
      <header class="dashboard-header">
        <h1>{{ $t('my_reservations') }}</h1>
        <div class="filters">
          <select v-model="statusFilter" @change="applyFilter" class="status-filter">
            <option value="">{{ $t('all_statuses') }}</option>
            <option value="pending">{{ $t('pending') }}</option>
            <option value="confirmed">{{ $t('confirmed') }}</option>
            <option value="completed">{{ $t('completed') }}</option>
            <option value="cancelled">{{ $t('cancelled') }}</option>
          </select>
        </div>
      </header>

      <div v-if="reservations.data.length === 0" class="empty-state">
        <Car :size="48" class="empty-icon" />
        <p>{{ $t('no_reservations') }}</p>
        <a href="/" class="browse-btn">{{ $t('browse_cars') }}</a>
      </div>

      <div v-else class="reservations-grid">
        <div
          v-for="reservation in reservations.data"
          :key="reservation.id"
          class="reservation-card"
          :class="'status-' + reservation.status"
        >
          <!-- Car Image -->
          <div class="card-image">
            <img
              :src="primaryImage(reservation.car)"
              :alt="reservation.car.brand + ' ' + reservation.car.model"
            />
            <span class="status-badge" :class="reservation.status">
              {{ $t(reservation.status) }}
            </span>
          </div>

          <!-- Card Body -->
          <div class="card-body">
            <h3>{{ reservation.car.brand }} {{ reservation.car.model }}</h3>
            <p class="agency-name">{{ reservation.agency.name }}</p>
            <p class="reference">{{ reservation.reference_number }}</p>

            <div class="dates-row">
              <div class="date-block">
                <span class="date-label">{{ $t('pickup') }}</span>
                <span class="date-value">{{ formatDate(reservation.start_date) }}</span>
              </div>
              <ChevronRight :size="16" class="arrow-icon" />
              <div class="date-block">
                <span class="date-label">{{ $t('return') }}</span>
                <span class="date-value">{{ formatDate(reservation.end_date) }}</span>
              </div>
            </div>

            <div class="price-row">
              <span class="total-label">{{ $t('total') }}</span>
              <span class="total-amount">{{ reservation.total_amount }} MAD</span>
            </div>

            <!-- Payment Status -->
            <div v-if="reservation.payment" class="payment-info">
              <CheckCircle :size="14" class="paid-icon" />
              <span>{{ $t('paid') }} — {{ formatDate(reservation.payment.paid_at) }}</span>
              <span v-if="reservation.payment.refund" class="refund-badge">
                {{ $t('refunded') }}: {{ reservation.payment.refund.amount }} MAD
              </span>
            </div>

            <!-- Review Display -->
            <div v-if="reservation.review" class="review-display">
              <StarRating :rating="reservation.review.car_rating" :readonly="true" />
              <p class="review-comment">{{ reservation.review.comment }}</p>
            </div>

            <!-- Action Buttons -->
            <div class="card-actions">
              <!-- Pay button -->
              <button
                v-if="reservation.status === 'confirmed' && !reservation.payment"
                @click="payReservation(reservation)"
                class="btn btn-pay"
                :disabled="isProcessing(reservation.id)"
              >
                <CreditCard :size="14" />
                {{ $t('pay') }}
              </button>

              <!-- Cancel button -->
              <button
                v-if="canCancel(reservation)"
                @click="cancelReservation(reservation)"
                class="btn btn-cancel"
                :disabled="isProcessing(reservation.id)"
              >
                <XCircle :size="14" />
                {{ $t('cancel') }}
              </button>

              <!-- Refund button -->
              <button
                v-if="canRefund(reservation)"
                @click="refundReservation(reservation)"
                class="btn btn-refund"
                :disabled="isProcessing(reservation.id)"
              >
                <RotateCcw :size="14" />
                {{ $t('request_refund') }}
              </button>

              <!-- Review button -->
              <button
                v-if="reservation.status === 'completed' && !reservation.review"
                @click="openReviewModal(reservation)"
                class="btn btn-review"
              >
                <Star :size="14" />
                {{ $t('write_review') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <Pagination :links="reservations.links" />

      <!-- Review Modal -->
      <Modal :show="showReviewModal" @close="showReviewModal = false">
        <div class="review-modal">
          <h2>{{ $t('write_review') }}</h2>
          <p v-if="reviewTarget" class="review-target">
            {{ reviewTarget.car.brand }} {{ reviewTarget.car.model }}
          </p>

          <div class="rating-field">
            <label>{{ $t('car_rating') }}</label>
            <StarRating v-model:rating="reviewForm.car_rating" />
          </div>

          <div class="rating-field">
            <label>{{ $t('agency_rating') }}</label>
            <StarRating v-model:rating="reviewForm.agency_rating" />
          </div>

          <div class="comment-field">
            <label>{{ $t('comment') }}</label>
            <textarea
              v-model="reviewForm.comment"
              :placeholder="$t('share_experience')"
              rows="4"
              maxlength="1000"
            ></textarea>
          </div>

          <div class="modal-actions">
            <button @click="showReviewModal = false" class="btn btn-cancel">
              {{ $t('cancel') }}
            </button>
            <button
              @click="submitReview"
              class="btn btn-pay"
              :disabled="reviewForm.processing || !reviewForm.car_rating || !reviewForm.agency_rating"
            >
              {{ $t('submit') }}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import StarRating from '@/Components/StarRating.vue';
import {
  Car, ChevronRight, CreditCard, XCircle, RotateCcw,
  Star, CheckCircle
} from 'lucide-vue-next';

const { t: $t, locale } = useI18n();

const props = defineProps({
  reservations: Object,
  filters: Object,
});

const statusFilter = ref(props.filters?.status || '');
const processingIds = ref(new Set());
const showReviewModal = ref(false);
const reviewTarget = ref(null);

function isProcessing(id) {
  return processingIds.value.has(id);
}

const reviewForm = useForm({
  car_rating: 0,
  agency_rating: 0,
  comment: '',
});

function primaryImage(car) {
  const primary = car.images?.find(img => img.is_primary);
  return primary ? `/storage/${primary.url}` : '/images/car-placeholder.jpg';
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString(locale.value, {
    year: 'numeric', month: 'short', day: 'numeric',
  });
}

function applyFilter() {
  router.get('/client/reservations', {
    status: statusFilter.value || undefined,
  }, { preserveState: true, preserveScroll: true });
}

function canCancel(reservation) {
  return !reservation.payment
    && ['confirmed', 'pending'].includes(reservation.status)
    && new Date(reservation.start_date) > new Date();
}

function canRefund(reservation) {
  return reservation.payment
    && !reservation.payment.refund
    && ['confirmed', 'pending'].includes(reservation.status)
    && new Date(reservation.start_date) > new Date();
}

function payReservation(reservation) {
  if (!confirm($t('confirm_payment'))) return;
  processingIds.value.add(reservation.id);
  router.post(`/client/reservations/${reservation.id}/pay`, {}, {
    onFinish: () => processingIds.value.delete(reservation.id),
  });
}

function cancelReservation(reservation) {
  if (!confirm($t('confirm_cancel'))) return;
  processingIds.value.add(reservation.id);
  router.delete(`/client/reservations/${reservation.id}`, {
    onFinish: () => processingIds.value.delete(reservation.id),
  });
}

function refundReservation(reservation) {
  if (!confirm($t('confirm_refund'))) return;
  processingIds.value.add(reservation.id);
  router.post(`/client/reservations/${reservation.id}/refund`, {}, {
    onFinish: () => processingIds.value.delete(reservation.id),
  });
}

function openReviewModal(reservation) {
  reviewTarget.value = reservation;
  reviewForm.reset();
  showReviewModal.value = true;
}

function submitReview() {
  reviewForm.post(`/client/reservations/${reviewTarget.value.id}/review`, {
    onSuccess: () => {
      showReviewModal.value = false;
      reviewTarget.value = null;
    },
  });
}
</script>

<style scoped>
.client-dashboard {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.dashboard-header h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary, #1a1a2e);
}

.status-filter {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-color, #e0e0e0);
  border-radius: 8px;
  font-size: 0.9rem;
  background: var(--bg-surface, #fff);
  cursor: pointer;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--text-muted, #888);
}
.empty-icon { margin-bottom: 1rem; opacity: 0.4; }
.browse-btn {
  display: inline-block;
  margin-top: 1rem;
  padding: 0.6rem 1.5rem;
  background: var(--accent, #4361ee);
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  transition: background 0.2s;
}
.browse-btn:hover { background: var(--accent-hover, #3a56d4); }

/* Reservation Grid */
.reservations-grid {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.reservation-card {
  display: flex;
  border: 1px solid var(--border-color, #e8e8e8);
  border-radius: 12px;
  overflow: hidden;
  background: var(--bg-surface, #fff);
  transition: box-shadow 0.2s;
}
.reservation-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.card-image {
  position: relative;
  width: 240px;
  min-height: 200px;
  flex-shrink: 0;
}
.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.status-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.status-badge.confirmed { background: #d4edda; color: #155724; }
.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.completed { background: #cce5ff; color: #004085; }
.status-badge.cancelled { background: #f8d7da; color: #721c24; }

.card-body {
  padding: 1.25rem 1.5rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.card-body h3 {
  font-size: 1.15rem;
  font-weight: 700;
  margin: 0;
}
.agency-name {
  font-size: 0.85rem;
  color: var(--text-muted, #666);
  margin: 0;
}
.reference {
  font-family: monospace;
  font-size: 0.8rem;
  color: var(--text-muted, #999);
  margin: 0;
}

.dates-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0.25rem 0;
}
.date-block { display: flex; flex-direction: column; }
.date-label { font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted, #999); letter-spacing: 0.5px; }
.date-value { font-size: 0.9rem; font-weight: 600; }
.arrow-icon { color: var(--text-muted, #ccc); }

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-top: 1px solid var(--border-color, #eee);
}
.total-label { font-size: 0.85rem; color: var(--text-muted, #666); }
.total-amount { font-size: 1.1rem; font-weight: 700; color: var(--accent, #4361ee); }

.payment-info {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: #155724;
}
.paid-icon { color: #28a745; }
.refund-badge {
  background: #fff3cd;
  color: #856404;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  margin-left: 0.5rem;
}

.review-display {
  padding: 0.5rem 0;
  border-top: 1px dashed var(--border-color, #eee);
}
.review-comment {
  font-size: 0.85rem;
  color: var(--text-muted, #666);
  font-style: italic;
  margin: 0.25rem 0 0;
}

/* Actions */
.card-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: auto;
  padding-top: 0.5rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 0.9rem;
  border: none;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-pay { background: #28a745; color: #fff; }
.btn-pay:hover:not(:disabled) { background: #218838; }

.btn-cancel { background: #dc3545; color: #fff; }
.btn-cancel:hover:not(:disabled) { background: #c82333; }

.btn-refund { background: #ffc107; color: #333; }
.btn-refund:hover:not(:disabled) { background: #e0a800; }

.btn-review { background: var(--accent, #4361ee); color: #fff; }
.btn-review:hover:not(:disabled) { background: var(--accent-hover, #3a56d4); }

/* Review Modal */
.review-modal { padding: 1.5rem; }
.review-modal h2 { font-size: 1.3rem; margin: 0 0 0.5rem; }
.review-target { color: var(--text-muted, #666); margin-bottom: 1.5rem; }

.rating-field {
  margin-bottom: 1rem;
}
.rating-field label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
}

.comment-field { margin-bottom: 1.5rem; }
.comment-field label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
}
.comment-field textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--border-color, #ddd);
  border-radius: 8px;
  font-size: 0.9rem;
  resize: vertical;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

/* Responsive */
@media (max-width: 768px) {
  .reservation-card { flex-direction: column; }
  .card-image { width: 100%; min-height: 160px; }
}
</style>
