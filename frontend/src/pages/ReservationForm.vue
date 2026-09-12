<template>
  <div class="turo-page">
    <!-- ========== SUCCESS ========== -->
    <div v-if="createdReservation" data-testid="success-banner" class="turo-success">
      <div class="turo-success-icon">✓</div>
      <p class="turo-eyebrow turo-teal-text">Demande envoyée</p>
      <h1 class="turo-car-title">Réservation confirmée</h1>
      <p class="turo-meta">
        L’agence examinera votre demande. Vous recevrez une mise à jour sous peu.
      </p>
      <div class="turo-success-box">
        <div class="turo-row">
          <span>Référence</span>
          <span class="turo-mono" data-testid="reservation-ref">
            {{ createdReservation.reference || createdReservation.id }}
          </span>
        </div>
        <div class="turo-row">
          <span>Véhicule</span>
          <strong>{{ carTitle || '—' }}</strong>
        </div>
        <div class="turo-row">
          <span>Statut</span>
          <span class="turo-teal-badge">{{ statusLabel(createdReservation.status) }}</span>
        </div>
        <div class="turo-row">
          <span>Période</span>
          <span>{{ formatRange(form.start_at, form.end_at) }}</span>
        </div>
        <div class="turo-row">
          <span>Total</span>
          <strong>{{ formatMoney(createdReservation.total_amount || totalPrice) }} MAD</strong>
        </div>
      </div>
      <div class="turo-success-actions">
        <RouterLink to="/myreservations" class="turo-btn-primary">Voir mes réservations</RouterLink>
        <button type="button" class="turo-btn-ghost" @click="resetForm">Nouvelle réservation</button>
      </div>
    </div>

    <template v-else>
      <div class="turo-topbar">
        <button type="button" class="turo-back" @click="goBack">← Retour</button>
        <nav v-if="selectedCar" class="turo-crumb" aria-label="Fil d'Ariane">
          <span>Maroc</span>
          <span class="turo-crumb-sep">/</span>
          <span>{{ locationLabel || 'Location' }}</span>
          <span class="turo-crumb-sep">/</span>
          <span class="turo-crumb-current">{{ carTitle }}</span>
        </nav>
      </div>

      <div v-if="globalError" data-testid="global-error" class="turo-alert">
        <strong>Impossible de finaliser la réservation</strong>
        <p>{{ globalError }}</p>
      </div>

      <div v-if="loadingCar" class="turo-loading">
        <div class="turo-spinner" />
        <p>Chargement du véhicule…</p>
      </div>

      <form v-else class="turo-layout" @submit.prevent="handleSubmit">
        <div class="turo-columns">
          <!-- Car image (left, top) -->
          <div class="turo-mosaic" :class="mosaicClass">
            <button
              type="button"
              class="turo-mosaic-cell turo-mosaic-main"
              @click="setActiveFromIndex(0)"
            >
              <img
                v-if="mosaicSlots[0]"
                :src="mosaicSlots[0]"
                :alt="carTitle || 'Véhicule'"
              />
              <div v-else class="turo-mosaic-empty">
                {{ selectedCar ? 'Photo indisponible' : 'Choisissez un véhicule ci-dessous' }}
              </div>
            </button>
            <button
              v-if="mosaicSlots[1]"
              type="button"
              class="turo-mosaic-cell turo-mosaic-side"
              @click="setActiveFromIndex(1)"
            >
              <img :src="mosaicSlots[1]" :alt="`${carTitle} — 2`" />
            </button>
            <button
              v-if="mosaicSlots[2]"
              type="button"
              class="turo-mosaic-cell turo-mosaic-side"
              @click="setActiveFromIndex(2)"
            >
              <img :src="mosaicSlots[2]" :alt="`${carTitle} — 3`" />
            </button>
            <div class="turo-mosaic-actions">
              <a href="#car-catalog" class="turo-change-car">Changer</a>
              <span v-if="galleryImages.length" class="turo-view-photos">
                Voir {{ galleryImages.length }} photo{{ galleryImages.length > 1 ? 's' : '' }}
              </span>
            </div>
          </div>

          <!-- Book card beside image -->
          <aside class="turo-sidebar">
            <div class="turo-book-card">
              <div class="turo-price-line">
                <span class="turo-price" data-testid="daily-price">{{ formatMoney(dailyPrice) }}</span>
                <span class="turo-price-unit">MAD / jour</span>
              </div>
              <p class="turo-est-link">
                {{ formatMoney(totalPrice) }} MAD est. total ·
                <span data-testid="duration-days">{{ rentalDays }} jour{{ rentalDays > 1 ? 's' : '' }}</span>
              </p>

              <div class="turo-trip-box">
                <div class="turo-trip-field">
                  <label for="start-at" class="turo-field-label">Début du voyage</label>
                  <input
                    id="start-at"
                    v-model="form.start_at"
                    type="datetime-local"
                    class="turo-input"
                    :min="minStartDate"
                    :class="{ error: errors.start_at }"
                    required
                    @change="onDateChange"
                  />
                  <p v-if="errors.start_at" class="turo-error">{{ errors.start_at[0] }}</p>
                </div>
                <div class="turo-trip-divider" aria-hidden="true" />
                <div class="turo-trip-field">
                  <label for="end-at" class="turo-field-label">Fin du voyage</label>
                  <input
                    id="end-at"
                    v-model="form.end_at"
                    type="datetime-local"
                    class="turo-input"
                    :min="form.start_at || minStartDate"
                    :class="{ error: errors.end_at }"
                    required
                    @change="onDateChange"
                  />
                  <p v-if="errors.end_at" class="turo-error">{{ errors.end_at[0] }}</p>
                </div>
              </div>

              <div v-if="dateError" data-testid="date-error" class="turo-error-box">{{ dateError }}</div>

              <div class="turo-book-voyage">
                <h3 class="turo-book-voyage-title">Votre voyage</h3>
                <p class="turo-book-voyage-hint">Lieu de prise en charge et de restitution.</p>
                <div class="turo-trip-box">
                  <div class="turo-trip-field">
                    <label for="pickup-point" class="turo-field-label">Prise en charge</label>
                    <select
                      id="pickup-point"
                      v-model="form.pickup_point_id"
                      class="turo-select"
                      :class="{ error: errors.pickup_point_id }"
                      required
                    >
                      <option value="" disabled>Choisir un lieu</option>
                      <option v-for="p in pickupPoints" :key="p.id" :value="p.id">
                        {{ pointLabel(p) }}
                      </option>
                    </select>
                    <p v-if="errors.pickup_point_id" class="turo-error">{{ errors.pickup_point_id[0] }}</p>
                    <p v-else-if="!pickupPoints.length" class="turo-error" data-testid="no-pickup-points">
                      Aucun lieu de prise en charge pour cette agence.
                    </p>
                  </div>
                  <div class="turo-trip-divider" aria-hidden="true" />
                  <div class="turo-trip-field">
                    <label for="return-point" class="turo-field-label">Restitution</label>
                    <select
                      id="return-point"
                      v-model="form.return_point_id"
                      class="turo-select"
                      :class="{ error: errors.return_point_id }"
                      required
                    >
                      <option value="" disabled>Choisir un lieu</option>
                      <option v-for="p in returnPoints" :key="p.id" :value="p.id">
                        {{ pointLabel(p) }}
                      </option>
                    </select>
                    <p v-if="errors.return_point_id" class="turo-error">{{ errors.return_point_id[0] }}</p>
                    <p v-else-if="!returnPoints.length" class="turo-error" data-testid="no-return-points">
                      Aucun lieu de restitution pour cette agence.
                    </p>
                  </div>
                </div>
              </div>

              <div class="turo-breakdown">
                <div class="turo-row">
                  <span>{{ formatMoney(dailyPrice) }} MAD × {{ rentalDays }} jour{{ rentalDays > 1 ? 's' : '' }}</span>
                  <span>{{ formatMoney(subtotal) }} MAD</span>
                </div>
                <div class="turo-row turo-row-total">
                  <span>Total estimé</span>
                  <span data-testid="total-price">{{ formatMoney(totalPrice) }} MAD</span>
                </div>
              </div>

              <button
                type="submit"
                class="turo-btn-primary turo-btn-block"
                data-testid="submit-button"
                :disabled="submitting || !isValid"
              >
                {{ submitting ? 'Envoi en cours…' : 'Continuer' }}
              </button>

              <p class="turo-note">Vous ne serez pas débité pour l’instant.</p>
            </div>
            <p class="turo-report">Signaler ce véhicule</p>
          </aside>

          <!-- Listing content under image -->
          <div class="turo-main">
            <header class="turo-heading">
              <h1 class="turo-car-title" data-testid="car-name">
                {{ carTitle || 'Sélectionnez un véhicule' }}
                <span v-if="selectedCar?.year" class="turo-title-year">{{ selectedCar.year }}</span>
              </h1>
              <div class="turo-meta-row">
                <span v-if="agency?.avg_rating" class="turo-rating">
                  <span class="turo-star">★</span>
                  {{ agency.avg_rating }}
                  <span v-if="agency.total_reviews" class="turo-muted">
                    ({{ agency.total_reviews }} avis)
                  </span>
                </span>
                <span v-if="agency?.name" class="turo-hosted-inline">
                  Proposé par <strong>{{ agency.name }}</strong>
                </span>
                <span v-if="locationLabel" class="turo-location">{{ locationLabel }}</span>
                <span
                  v-if="availabilityStatus"
                  data-testid="availability-pill"
                  class="turo-avail"
                  :class="availabilityStatus.available ? 'ok' : 'ko'"
                >
                  {{ availabilityStatus.available ? 'Disponible' : 'Indisponible' }}
                </span>
              </div>
            </header>

            <div v-if="specs.length" class="turo-specs-strip">
              <div v-for="spec in specs" :key="spec.label" class="turo-spec-chip">
                <span class="turo-spec-dot" aria-hidden="true" />
                <div>
                  <div class="turo-spec-value">{{ spec.value }}</div>
                  <div class="turo-spec-label">{{ spec.label }}</div>
                </div>
              </div>
            </div>

            <section v-if="agency" class="turo-section">
              <h2 class="turo-section-title">Proposé par</h2>
              <div class="turo-host">
                <div class="turo-host-avatar">{{ agencyInitials }}</div>
                <div class="turo-host-info">
                  <div class="turo-host-top">
                    <div>
                      <div class="turo-host-name">{{ agency.name }}</div>
                      <div class="turo-host-meta">
                        <span v-if="agency.avg_rating">★ {{ agency.avg_rating }}</span>
                        <span v-if="agency.total_reviews"> · {{ agency.total_reviews }} avis</span>
                        <span v-if="agency.phone"> · {{ agency.phone }}</span>
                      </div>
                    </div>
                    <span class="turo-allstar">All-Star Host</span>
                  </div>
                  <p class="turo-body turo-host-bio">
                    Agence vérifiée sur GlobalRental.
                    <template v-if="agency.address"> Basée à {{ agency.address }}.</template>
                  </p>
                </div>
              </div>
            </section>

            <section v-if="selectedCar" class="turo-section">
              <h2 class="turo-section-title">Description</h2>
              <p class="turo-body">
                Louez cette <strong>{{ carTitle }}</strong>
                <template v-if="selectedCar.year"> {{ selectedCar.year }}</template>
                <template v-if="locationLabel"> à {{ locationLabel }}</template>.
                Véhicule
                <template v-if="selectedCar.transmission"> en boîte {{ selectedCar.transmission }}</template>
                <template v-if="selectedCar.energy_type">, motorisation {{ selectedCar.energy_type }}</template>
                <template v-if="selectedCar.seats">, {{ selectedCar.seats }} places</template>.
              </p>
              <p class="turo-body turo-body-secondary">
                Remise des clés selon le point choisi. Pièce d’identité et permis requis au retrait.
              </p>
            </section>

            <section v-if="highlights.length" class="turo-section">
              <h2 class="turo-section-title">Équipements du véhicule</h2>
              <ul class="turo-features">
                <li v-for="item in highlights" :key="item">
                  <span class="turo-check" aria-hidden="true">✓</span>
                  {{ item }}
                </li>
              </ul>
            </section>

            <section class="turo-section">
              <h2 class="turo-section-title">Règles du voyage</h2>
              <div class="turo-guidelines">
                <div class="turo-guideline">
                  <strong>Âge minimum</strong>
                  <p>Conducteur âgé d’au moins 21 ans avec permis valide.</p>
                </div>
                <div class="turo-guideline">
                  <strong>Carburant</strong>
                  <p>Restituez le véhicule avec le même niveau de carburant.</p>
                </div>
                <div class="turo-guideline">
                  <strong>Kilométrage</strong>
                  <p>Usage normal inclus — confirmez les conditions avec l’agence.</p>
                </div>
                <div class="turo-guideline">
                  <strong>Fumeur</strong>
                  <p>Véhicule non-fumeur, sauf indication contraire de l’hôte.</p>
                </div>
              </div>
            </section>

            <section class="turo-section">
              <h2 class="turo-section-title">Politique d’annulation</h2>
              <div class="turo-policy">
                <span class="turo-policy-tag">Flexible</span>
                <h3>Annulation</h3>
                <p>
                  Annulation possible tant que la réservation est
                  <em>en attente</em> ou <em>confirmée</em>, selon les conditions de l’agence.
                  Vous ne serez pas débité avant confirmation.
                </p>
              </div>
            </section>
          </div>
        </div>

        <!-- Catalog at bottom (replaces dropdown) — same page -->
        <section id="car-catalog" class="turo-catalog turo-section">
          <div class="turo-pick-head">
            <p class="turo-eyebrow">Catalogue</p>
            <h2 class="turo-section-title turo-section-title-tight">Choisir un véhicule</h2>
            <p class="turo-meta">Sélectionnez une autre voiture pour mettre à jour la réservation.</p>
          </div>

          <div v-if="availableCars.length" class="turo-car-grid" data-testid="car-card-grid">
            <button
              v-for="car in availableCars"
              :key="car.id"
              type="button"
              class="turo-car-card"
              :class="{ selected: form.car_id === car.id }"
              :aria-pressed="form.car_id === car.id"
              @click="selectCarCard(car)"
            >
              <div class="turo-car-card-media">
                <img
                  v-if="cardImage(car)"
                  :src="cardImage(car)"
                  :alt="`${car.brand} ${car.model}`"
                />
                <div v-else class="turo-car-card-placeholder">Pas de photo</div>
                <span class="turo-heart" aria-hidden="true">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.4l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z" />
                  </svg>
                </span>
              </div>
              <div class="turo-car-card-body">
                <h3 class="turo-car-card-title">{{ car.brand }} {{ car.model }}</h3>
                <p class="turo-car-card-meta">
                  <template v-if="car.year">{{ car.year }}</template>
                  <template v-if="car.year && (car.agency?.avg_rating != null || car.transmission)">
                    <span aria-hidden="true">·</span>
                  </template>
                  <span v-if="car.agency?.avg_rating != null" class="turo-card-rating">
                    {{ Number(car.agency.avg_rating).toFixed(1) }}
                    <span class="turo-card-star" aria-hidden="true">★</span>
                    <span v-if="car.agency?.total_reviews">({{ car.agency.total_reviews }})</span>
                  </span>
                  <span v-else-if="car.transmission">{{ car.transmission }}</span>
                </p>
                <p class="turo-car-card-price">
                  <strong>{{ formatMoney(car.daily_price) }} MAD</strong>
                  <span>/ jour</span>
                </p>
                <p class="turo-car-card-total">
                  {{ formatMoney(Number(car.daily_price || 0) * rentalDays) }} MAD total
                  <template v-if="rentalDays > 1"> · {{ rentalDays }} j</template>
                </p>
              </div>
            </button>
          </div>

          <p v-else class="turo-hint">
            Aucune voiture disponible pour le moment.
          </p>
          <p v-if="errors.car_id" class="turo-error">{{ errors.car_id[0] }}</p>
        </section>

        <!-- Mobile sticky book bar -->
        <div class="turo-mobile-bar">
          <div>
            <div class="turo-mobile-price">
              <strong>{{ formatMoney(dailyPrice) }}</strong>
              <span>MAD / jour</span>
            </div>
            <div class="turo-mobile-est">
              {{ formatMoney(totalPrice) }} MAD est. · {{ rentalDays }} j
            </div>
          </div>
          <button type="submit" class="turo-btn-primary" :disabled="submitting || !isValid">
            {{ submitting ? '…' : 'Continuer' }}
          </button>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import reservationsService from '@/services/reservations'
import carsService from '@/services/cars'

const props = defineProps({
  carId: { type: String, default: null },
})

const router = useRouter()
const route = useRoute()

const loadingCar = ref(false)
const submitting = ref(false)
const globalError = ref('')
const dateError = ref('')
const errors = reactive({})
const createdReservation = ref(null)
const availabilityStatus = ref(null)
const activeImage = ref(null)

const selectedCar = ref(null)
const availableCars = ref([])
const agencyPoints = ref([])

const form = reactive({
  car_id: props.carId || route?.params?.carId || route?.query?.car_id || '',
  start_at: '',
  end_at: '',
  pickup_point_id: '',
  return_point_id: '',
})

const STATUS_LABELS = {
  pending: 'En attente',
  confirmed: 'Confirmée',
  picked_up: 'En cours',
  completed: 'Terminée',
  cancelled: 'Annulée',
  disputed: 'Litige',
  rejected: 'Refusée',
}

const minStartDate = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const pickupPoints = computed(() =>
  agencyPoints.value.filter((p) => p.allows_pickup !== false)
)
const returnPoints = computed(() =>
  agencyPoints.value.filter((p) => p.allows_return !== false)
)

const agency = computed(() => selectedCar.value?.agency || null)
const agencyInitials = computed(() => {
  const name = agency.value?.name || 'H'
  return (
    name
      .split(/\s+/)
      .slice(0, 2)
      .map((w) => w[0]?.toUpperCase() || '')
      .join('') || 'H'
  )
})

const carTitle = computed(() => {
  if (!selectedCar.value) return ''
  return `${selectedCar.value.brand || ''} ${selectedCar.value.model || ''}`.trim()
})

const locationLabel = computed(() => {
  return (
    selectedCar.value?.city?.name ||
    agency.value?.city?.name ||
    agency.value?.address ||
    ''
  )
})

const galleryImages = computed(() => {
  const images = selectedCar.value?.images || []
  return [...images].sort((a, b) => {
    if (a.is_primary === b.is_primary) return (a.display_order || 0) - (b.display_order || 0)
    return a.is_primary ? -1 : 1
  })
})

const imageUrl = (img) => img?.url || img?.image_url || null

const mosaicSlots = computed(() => {
  const urls = galleryImages.value.map(imageUrl).filter(Boolean)
  return [urls[0] || null, urls[1] || null, urls[2] || null]
})

const mosaicClass = computed(() => {
  const n = mosaicSlots.value.filter(Boolean).length
  if (n >= 3) return 'has-3'
  if (n === 2) return 'has-2'
  return 'has-1'
})

const specs = computed(() => {
  const car = selectedCar.value
  if (!car) return []
  const list = []
  if (car.energy_type) list.push({ label: 'Carburant', value: capitalize(car.energy_type) })
  if (car.seats) list.push({ label: 'Places', value: `${car.seats} places` })
  if (car.transmission) list.push({ label: 'Boîte', value: capitalize(car.transmission) })
  if (car.type) list.push({ label: 'Type', value: capitalize(car.type) })
  if (car.color) list.push({ label: 'Couleur', value: capitalize(car.color) })
  if (car.fuel_consumption) {
    list.push({ label: 'Conso.', value: `${car.fuel_consumption} L/100` })
  }
  if (car.electric_range) {
    list.push({ label: 'Autonomie', value: `${car.electric_range} km` })
  }
  return list
})

const highlights = computed(() => {
  const car = selectedCar.value
  if (!car) return []
  const items = []
  if (car.transmission) items.push(`Transmission ${car.transmission}`)
  if (car.seats) items.push(`${car.seats} places assises`)
  if (car.energy_type) items.push(`Motorisation ${car.energy_type}`)
  if (pickupPoints.value.length) items.push(`${pickupPoints.value.length} point(s) de retrait`)
  if (agency.value?.name) items.push(`Hôte : ${agency.value.name}`)
  items.push('Assistance pendant le voyage')
  return items
})

const dailyPrice = computed(() =>
  selectedCar.value ? Number(selectedCar.value.daily_price) || 0 : 0
)

const rentalDays = computed(() => {
  if (!form.start_at || !form.end_at) return 1
  const start = new Date(form.start_at)
  const end = new Date(form.end_at)
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) return 1
  const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24))
  return days > 0 ? days : 1
})

const subtotal = computed(() => rentalDays.value * dailyPrice.value)
const totalPrice = computed(() => subtotal.value)

const isValid = computed(
  () =>
    !!(
      form.car_id &&
      form.start_at &&
      form.end_at &&
      form.pickup_point_id &&
      form.return_point_id &&
      !dateError.value
    )
)

function capitalize(value) {
  if (!value) return ''
  return String(value).charAt(0).toUpperCase() + String(value).slice(1)
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('fr-MA')
}

function statusLabel(status) {
  return STATUS_LABELS[status] || status || 'En attente'
}

function pointLabel(p) {
  return `${p.name}${p.address ? ` — ${p.address}` : p.city ? ` — ${p.city}` : ''}`
}

function formatRange(start, end) {
  if (!start || !end) return '—'
  const opts = { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }
  const s = new Date(start)
  const e = new Date(end)
  if (Number.isNaN(s.getTime()) || Number.isNaN(e.getTime())) return '—'
  return `${s.toLocaleString('fr-FR', opts)} → ${e.toLocaleString('fr-FR', opts)}`
}

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/')
}

function selectCarCard(car) {
  form.car_id = car.id
  selectedCar.value = car
  delete errors.car_id
  syncPointsFromCar(car)
  syncGallery()
  checkAvailability()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function syncPointsFromCar(car) {
  const points =
    car?.agency?.agency_points ||
    car?.agency?.agencyPoints ||
    []
  agencyPoints.value = Array.isArray(points) ? points : []
  form.pickup_point_id = pickupPoints.value[0]?.id || ''
  form.return_point_id = returnPoints.value[0]?.id || ''
}

function cardImage(car) {
  const images = car?.images || []
  const primary = images.find((img) => img.is_primary) || images[0]
  return primary?.url || primary?.image_url || null
}

function syncGallery() {
  const first = galleryImages.value[0]
  activeImage.value = first ? imageUrl(first) : null
}

function setActiveFromIndex(i) {
  const img = galleryImages.value[i]
  if (img) activeImage.value = imageUrl(img)
}

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

async function checkAvailability() {
  if (!form.car_id || !form.start_at || !form.end_at || dateError.value) {
    availabilityStatus.value = null
    return
  }
  try {
    const res = await reservationsService.checkAvailability(form.car_id, {
      start_at: form.start_at,
      end_at: form.end_at,
    })
    availabilityStatus.value = res?.available !== undefined ? res : res?.data || null
  } catch {
    availabilityStatus.value = null
  }
}

function resetForm() {
  createdReservation.value = null
  globalError.value = ''
  form.start_at = ''
  form.end_at = ''
  form.pickup_point_id = pickupPoints.value[0]?.id || ''
  form.return_point_id = returnPoints.value[0]?.id || ''
  availabilityStatus.value = null
}

async function handleSubmit() {
  globalError.value = ''
  Object.keys(errors).forEach((key) => delete errors[key])
  if (!isValid.value) return

  submitting.value = true
  try {
    const response = await reservationsService.createReservation({
      car_id: form.car_id,
      pickup_point_id: form.pickup_point_id,
      return_point_id: form.return_point_id,
      start_at: form.start_at,
      end_at: form.end_at,
    })
    createdReservation.value =
      response?.reservation ||
      response?.data?.reservation ||
      response || {
        id: 'RES-' + Math.floor(Math.random() * 10000),
        total_amount: totalPrice.value,
        status: 'pending',
      }
  } catch (err) {
    if ((err.status === 422 || err.response?.status === 422) && (err.errors || err.response?.data?.errors)) {
      Object.assign(errors, err.errors || err.response?.data?.errors)
    } else {
      globalError.value =
        err.message || err.response?.data?.message || 'Une erreur est survenue lors de la réservation.'
    }
  } finally {
    submitting.value = false
  }
}

watch(galleryImages, syncGallery)

onMounted(async () => {
  const targetCarId = form.car_id
  loadingCar.value = true
  globalError.value = ''
  try {
    const carsRes = await carsService.getPublicCars()
    availableCars.value = carsRes?.cars || carsRes?.data?.cars || []

    if (targetCarId) {
      selectedCar.value = availableCars.value.find((c) => c.id === targetCarId) || null
      if (!selectedCar.value) {
        try {
          const single = await carsService.getPublicCar(targetCarId)
          selectedCar.value = single?.car || single?.data?.car || null
        } catch {
          /* ignore */
        }
      }
      if (selectedCar.value) {
        form.car_id = selectedCar.value.id
        syncPointsFromCar(selectedCar.value)
        syncGallery()
      }
    } else if (availableCars.value.length) {
      const car = availableCars.value[0]
      form.car_id = car.id
      selectedCar.value = car
      syncPointsFromCar(car)
      syncGallery()
    }
  } catch (e) {
    console.error('Erreur chargement voitures', e)
    globalError.value = e?.message || 'Impossible de charger le catalogue de voitures.'
    availableCars.value = []
  } finally {
    loadingCar.value = false
  }
})
</script>

<style scoped>
.turo-page {
  /* Align with global frontend tokens (style.css :root) */
  --turo-purple: var(--accent);
  --turo-purple-pressed: var(--ink-secondary);
  --turo-teal: var(--ink-muted);
  --turo-teal-deep: var(--ink-secondary);
  --turo-ink: var(--ink);
  --turo-muted: var(--ink-muted);
  --turo-canvas: var(--surface);
  --turo-surface: var(--bg);
  --turo-divider: var(--border);
  --turo-error: #ef4444;
  --turo-star: var(--ink);

  min-height: 100vh;
  background: var(--bg);
  color: var(--ink);
  font-family: 'DM Sans', system-ui, sans-serif;
  padding: 16px 16px 110px;
}

@media (min-width: 1024px) {
  .turo-page {
    padding: 20px 40px 64px;
  }
}

.turo-topbar {
  max-width: 1180px;
  margin: 0 auto 12px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 20px;
}

.turo-back {
  background: none;
  border: none;
  color: var(--turo-muted);
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  padding: 0;
}
.turo-back:hover {
  color: var(--turo-ink);
}

.turo-crumb {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  font-size: 0.78rem;
  color: var(--turo-muted);
}
.turo-crumb-sep {
  opacity: 0.5;
}
.turo-crumb-current {
  color: var(--turo-ink);
  font-weight: 600;
}

.turo-layout {
  max-width: 1180px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 28px;
}
.turo-columns {
  display: grid;
  gap: 20px;
  align-items: start;
}
.turo-mosaic {
  order: 1;
}
.turo-sidebar {
  order: 2;
  display: block;
}
.turo-main {
  order: 3;
}
@media (min-width: 1024px) {
  .turo-columns {
    grid-template-columns: 1fr 360px;
    gap: 28px 40px;
  }
  .turo-mosaic {
    grid-column: 1;
    grid-row: 1;
    order: unset;
  }
  .turo-sidebar {
    grid-column: 2;
    grid-row: 1 / span 2;
    position: sticky;
    top: 20px;
    order: unset;
  }
  .turo-main {
    grid-column: 1;
    grid-row: 2;
    order: unset;
  }
}

/* —— Photo mosaic (beside book card) —— */
.turo-mosaic {
  position: relative;
  display: grid;
  gap: 8px;
  border-radius: 16px;
  overflow: hidden;
  min-height: 240px;
  background: var(--accent);
}
.turo-mosaic.has-1 {
  grid-template-columns: 1fr;
}
.turo-mosaic.has-2 {
  grid-template-columns: 1.4fr 1fr;
}
.turo-mosaic.has-3 {
  grid-template-columns: 1.55fr 1fr;
  grid-template-rows: 1fr 1fr;
}
.turo-mosaic.has-3 .turo-mosaic-main {
  grid-row: 1 / span 2;
}
@media (max-width: 699px) {
  .turo-mosaic.has-2,
  .turo-mosaic.has-3 {
    grid-template-columns: 1fr;
    grid-template-rows: none;
  }
  .turo-mosaic.has-3 .turo-mosaic-main {
    grid-row: auto;
  }
  .turo-mosaic .turo-mosaic-side:nth-child(n + 2) {
    display: none;
  }
}
.turo-mosaic-cell {
  position: relative;
  margin: 0;
  padding: 0;
  border: none;
  background: var(--ink);
  cursor: pointer;
  overflow: hidden;
  min-height: 180px;
}
.turo-mosaic.has-3 .turo-mosaic-main,
.turo-mosaic.has-2 .turo-mosaic-main,
.turo-mosaic.has-1 .turo-mosaic-main {
  min-height: min(42vw, 380px);
}
.turo-mosaic.has-3 .turo-mosaic-side {
  min-height: 0;
}
.turo-mosaic-cell img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.turo-mosaic-empty {
  height: 100%;
  min-height: 280px;
  display: grid;
  place-items: center;
  color: var(--ink-muted);
  font-size: 0.9rem;
}
.turo-mosaic-actions {
  position: absolute;
  right: 14px;
  bottom: 14px;
  display: flex;
  gap: 8px;
  align-items: center;
  z-index: 2;
}
.turo-view-photos {
  background: var(--surface);
  color: var(--ink);
  font-size: 0.78rem;
  font-weight: 700;
  padding: 8px 12px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(10, 10, 11, 0.12);
}
.turo-hosted-inline {
  font-size: 0.88rem;
  color: var(--turo-muted);
}
.turo-hosted-inline strong {
  color: var(--turo-ink);
  font-weight: 700;
}

.turo-specs-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 18px;
  padding: 18px 0 8px;
  border-bottom: 1px solid var(--turo-divider);
  margin-bottom: 4px;
}
.turo-spec-chip {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 110px;
}
.turo-spec-dot {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--turo-surface);
  border: 1px solid var(--turo-divider);
  flex-shrink: 0;
  position: relative;
}
.turo-spec-dot::after {
  content: '';
  position: absolute;
  inset: 11px;
  border-radius: 3px;
  background: var(--accent);
}
.turo-spec-chip .turo-spec-value {
  font-weight: 700;
  font-size: 0.9rem;
  line-height: 1.2;
}
.turo-spec-chip .turo-spec-label {
  font-size: 0.72rem;
  color: var(--turo-muted);
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0;
  margin: 0;
}

.turo-trip-locations {
  display: grid;
  gap: 12px;
}
@media (min-width: 640px) {
  .turo-trip-locations {
    grid-template-columns: 1fr 1fr;
  }
}
.turo-trip-divider {
  height: 1px;
  background: var(--turo-divider);
  margin: 0;
}

.turo-eyebrow {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--turo-muted);
  margin-bottom: 6px;
}
.turo-car-title {
  font-family: 'Bricolage Grotesque', 'DM Sans', sans-serif;
  font-size: clamp(1.5rem, 3vw, 2rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  margin: 0 0 10px;
  line-height: 1.15;
}
.turo-title-year {
  color: var(--turo-muted);
  font-weight: 700;
  margin-left: 0.35em;
}

.turo-meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 14px;
  align-items: center;
  margin-bottom: 8px;
}
.turo-rating {
  font-weight: 700;
  font-size: 0.9rem;
}
.turo-star {
  color: var(--turo-star);
}
.turo-muted {
  color: var(--turo-muted);
  font-weight: 500;
}
.turo-location {
  color: var(--turo-muted);
  font-size: 0.88rem;
}
.turo-avail {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 5px 10px;
  border-radius: 999px;
}
.turo-avail.ok {
  background: var(--accent-subtle);
  color: var(--ink);
}
.turo-avail.ko {
  background: #fef2f2;
  color: var(--turo-error);
}

.turo-section {
  margin-top: 8px;
  padding-top: 28px;
  border-top: 1px solid var(--turo-divider);
}
.turo-section-flush {
  border-top: none;
  padding-top: 20px;
}
.turo-section-title {
  font-size: 1.15rem;
  font-weight: 700;
  margin: 0 0 16px;
  letter-spacing: -0.01em;
}
.turo-section-title-tight {
  margin-bottom: 6px;
}

/* —— Vehicle card picker —— */
.turo-pick {
  width: 100%;
}
.turo-pick-head {
  margin-bottom: 20px;
}
.turo-car-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px 16px;
}
@media (min-width: 560px) {
  .turo-car-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (min-width: 900px) {
  .turo-car-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
@media (min-width: 1200px) {
  .turo-car-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
.turo-car-card {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  text-align: left;
  padding: 0;
  margin: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  font: inherit;
  color: inherit;
  border-radius: 14px;
  transition: transform 0.18s ease;
}
.turo-car-card:hover {
  transform: translateY(-2px);
}
.turo-car-card:focus-visible {
  outline: 2px solid var(--turo-purple);
  outline-offset: 3px;
}
.turo-car-card.selected .turo-car-card-media {
  box-shadow: 0 0 0 2px var(--turo-purple);
}
.turo-car-card-media {
  position: relative;
  aspect-ratio: 4 / 3;
  border-radius: 14px;
  overflow: hidden;
  background: var(--turo-surface);
}
.turo-car-card-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.turo-car-card-placeholder {
  height: 100%;
  display: grid;
  place-items: center;
  color: var(--turo-muted);
  font-size: 0.82rem;
  font-weight: 600;
}
.turo-heart {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 34px;
  height: 34px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  background: rgba(10, 10, 11, 0.35);
  color: #fff;
  backdrop-filter: blur(6px);
  pointer-events: none;
}
.turo-car-card-body {
  padding: 10px 2px 4px;
}
.turo-car-card-title {
  font-family: 'Bricolage Grotesque', 'DM Sans', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 0 0 4px;
  line-height: 1.25;
  color: var(--turo-ink);
}
.turo-car-card-meta {
  margin: 0 0 8px;
  font-size: 0.84rem;
  color: var(--turo-muted);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
}
.turo-card-rating {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  color: var(--turo-ink);
  font-weight: 600;
}
.turo-card-star {
  color: var(--turo-purple);
  font-size: 0.78rem;
  line-height: 1;
}
.turo-car-card-price {
  margin: 0;
  font-size: 0.95rem;
  color: var(--turo-ink);
}
.turo-car-card-price strong {
  font-weight: 800;
}
.turo-car-card-price span {
  color: var(--turo-muted);
  font-weight: 500;
}
.turo-car-card-total {
  margin: 2px 0 0;
  font-size: 0.8rem;
  color: var(--turo-muted);
}

.turo-heading-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}
.turo-change-car {
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--ink);
  font-size: 0.8rem;
  font-weight: 700;
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
  white-space: nowrap;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}
.turo-change-car:hover {
  border-color: var(--ink);
  color: var(--ink);
}

.turo-catalog {
  width: 100%;
  scroll-margin-top: 24px;
}
.turo-catalog .turo-meta {
  margin: 0 0 18px;
  color: var(--turo-muted);
  font-size: 0.9rem;
}

.turo-specs {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}
@media (min-width: 640px) {
  .turo-specs {
    grid-template-columns: repeat(3, 1fr);
  }
}
@media (min-width: 900px) {
  .turo-specs {
    grid-template-columns: repeat(4, 1fr);
  }
}
.turo-spec {
  background: var(--turo-surface);
  border-radius: 14px;
  padding: 14px 16px;
}
.turo-spec-label {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--turo-muted);
  margin-bottom: 3px;
}
.turo-spec-value {
  font-weight: 700;
  font-size: 0.92rem;
}

.turo-features {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 10px;
}
@media (min-width: 640px) {
  .turo-features {
    grid-template-columns: 1fr 1fr;
  }
}
.turo-features li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.92rem;
  font-weight: 500;
  color: var(--ink-secondary);
}
.turo-check {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--accent);
  color: var(--surface);
  display: inline-grid;
  place-items: center;
  font-size: 0.7rem;
  font-weight: 800;
  flex-shrink: 0;
}

.turo-body {
  font-size: 0.95rem;
  line-height: 1.65;
  color: var(--ink-secondary);
  margin: 0 0 12px;
}
.turo-body-secondary {
  color: var(--turo-muted);
}

.turo-guidelines {
  display: grid;
  gap: 12px;
}
@media (min-width: 640px) {
  .turo-guidelines {
    grid-template-columns: 1fr 1fr;
  }
}
.turo-guideline {
  background: var(--turo-surface);
  border-radius: 14px;
  padding: 16px;
}
.turo-guideline strong {
  display: block;
  margin-bottom: 6px;
  font-size: 0.9rem;
}
.turo-guideline p {
  margin: 0;
  font-size: 0.84rem;
  color: var(--turo-muted);
  line-height: 1.5;
}

.turo-host {
  display: flex;
  gap: 16px;
  align-items: flex-start;
  background: var(--turo-surface);
  border-radius: 16px;
  padding: 20px;
}
.turo-host-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--accent);
  color: var(--surface);
  display: grid;
  place-items: center;
  font-weight: 800;
  flex-shrink: 0;
}
.turo-host-info {
  flex: 1;
  min-width: 0;
}
.turo-host-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
  flex-wrap: wrap;
}
.turo-host-name {
  font-weight: 800;
  font-size: 1.05rem;
}
.turo-host-meta {
  font-size: 0.85rem;
  color: var(--turo-muted);
  margin-top: 2px;
}
.turo-allstar {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink);
  background: var(--accent-subtle);
  padding: 5px 9px;
  border-radius: 6px;
}
.turo-host-bio {
  margin-top: 12px;
}
.turo-host-stats {
  display: flex;
  gap: 24px;
  margin-top: 14px;
}
.turo-host-stat-val {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-weight: 800;
  font-size: 1.1rem;
}
.turo-host-stat-label {
  font-size: 0.7rem;
  color: var(--turo-muted);
}

.turo-policy-cards {
  display: grid;
  gap: 12px;
}
@media (min-width: 640px) {
  .turo-policy-cards {
    grid-template-columns: 1fr 1fr;
  }
}
.turo-policy {
  border: 1px solid var(--turo-divider);
  border-radius: 14px;
  padding: 16px;
}
.turo-policy-tag {
  display: inline-block;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  background: var(--accent-subtle);
  color: var(--ink);
  padding: 4px 8px;
  border-radius: 6px;
  margin-bottom: 10px;
}
.turo-policy-tag.teal {
  background: var(--accent-subtle);
  color: var(--ink-secondary);
}
.turo-policy h3 {
  margin: 0 0 8px;
  font-size: 0.95rem;
}
.turo-policy p {
  margin: 0;
  font-size: 0.84rem;
  color: var(--turo-muted);
  line-height: 1.55;
}

.turo-book-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 22px;
  box-shadow: 0 10px 36px rgba(10, 10, 11, 0.06);
}
.turo-price-line {
  display: flex;
  align-items: baseline;
  gap: 6px;
}
.turo-price {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.75rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}
.turo-price-unit {
  color: var(--turo-muted);
  font-size: 0.85rem;
}
.turo-est-link {
  margin: 6px 0 14px;
  color: var(--ink-secondary);
  font-size: 0.8rem;
  font-weight: 700;
}

.turo-book-voyage {
  margin-bottom: 12px;
}
.turo-book-voyage-title {
  margin: 0 0 4px;
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--ink);
  letter-spacing: -0.01em;
}
.turo-book-voyage-hint {
  margin: 0 0 10px;
  font-size: 0.78rem;
  color: var(--ink-muted);
  line-height: 1.4;
}

.turo-trust-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 14px;
  font-size: 0.78rem;
  color: var(--turo-muted);
  font-weight: 600;
}

.turo-trip-box {
  border: 1px solid var(--turo-divider);
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 12px;
}
.turo-trip-box > .turo-trip-divider {
  height: 1px;
  background: var(--turo-divider);
}
.turo-trip-field {
  padding: 12px 14px;
}
.turo-trip-field + .turo-trip-field {
  border-top: 1px solid var(--turo-divider);
}
.turo-field-label {
  display: block;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--turo-muted);
  margin-bottom: 4px;
}
.turo-input,
.turo-select {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  font: inherit;
  font-weight: 700;
  color: var(--turo-ink);
  font-size: 0.95rem;
}
.turo-select-boxed {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 12px 14px;
  background: var(--surface);
}
.turo-input.error,
.turo-select.error {
  color: var(--turo-error);
}

.turo-breakdown {
  margin: 12px 0 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.turo-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 0.88rem;
  color: var(--turo-muted);
}
.turo-row-total {
  color: var(--turo-ink);
  font-weight: 800;
  border-top: 1px solid var(--turo-divider);
  padding-top: 10px;
  margin-top: 4px;
}

.turo-btn-primary {
  appearance: none;
  border: none;
  background: var(--accent);
  color: var(--surface);
  font-weight: 800;
  font-size: 1rem;
  border-radius: 10px;
  padding: 14px 28px;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.turo-btn-primary:hover:not(:disabled) {
  background: var(--ink-secondary);
}
.turo-btn-primary:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.turo-btn-block {
  width: 100%;
}
.turo-btn-ghost {
  appearance: none;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 12px 20px;
  font-weight: 700;
  cursor: pointer;
  color: var(--ink);
}

.turo-note {
  margin-top: 12px;
  text-align: center;
  font-size: 0.75rem;
  color: var(--turo-muted);
  line-height: 1.5;
}
.turo-teal-text {
  color: var(--ink-secondary);
  font-weight: 600;
}
.turo-report {
  margin-top: 16px;
  text-align: center;
  font-size: 0.75rem;
  color: var(--turo-muted);
}

.turo-mobile-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px calc(14px + env(safe-area-inset-bottom));
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  border-top: 1px solid var(--turo-divider);
}
@media (min-width: 1024px) {
  .turo-mobile-bar {
    display: none;
  }
}
.turo-mobile-price {
  font-size: 0.95rem;
}
.turo-mobile-price span {
  color: var(--turo-muted);
  font-size: 0.8rem;
  margin-left: 4px;
}
.turo-mobile-est {
  font-size: 0.72rem;
  color: var(--ink-secondary);
  font-weight: 700;
}

.turo-alert {
  max-width: 1180px;
  margin: 0 auto 16px;
  padding: 14px 16px;
  border-radius: 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  font-size: 0.9rem;
}
.turo-error {
  color: var(--turo-error);
  font-size: 0.75rem;
  margin-top: 4px;
}
.turo-error-box {
  background: #fef2f2;
  color: #b91c1c;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 0.8rem;
  margin-bottom: 12px;
}
.turo-hint {
  margin-top: 8px;
  font-size: 0.8rem;
  color: var(--turo-muted);
}
.turo-loading {
  text-align: center;
  padding: 80px 16px;
  color: var(--turo-muted);
}
.turo-spinner {
  width: 36px;
  height: 36px;
  margin: 0 auto 12px;
  border-radius: 50%;
  border: 3px solid var(--border);
  border-top-color: var(--accent);
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.turo-success {
  max-width: 540px;
  margin: 48px auto;
  padding: 32px;
  border: 1px solid var(--turo-divider);
  border-radius: 18px;
  text-align: center;
  box-shadow: 0 8px 30px rgba(21, 19, 31, 0.06);
}
.turo-success-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 12px;
  border-radius: 50%;
  background: var(--accent-subtle);
  color: var(--ink);
  display: grid;
  place-items: center;
  font-size: 1.5rem;
  font-weight: 800;
}
.turo-success-box {
  text-align: left;
  background: var(--bg);
  border-radius: 12px;
  padding: 14px;
  margin: 20px 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.turo-mono {
  font-family: ui-monospace, monospace;
  font-weight: 700;
}
.turo-teal-badge {
  background: var(--accent-subtle);
  color: var(--ink);
  font-size: 0.75rem;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 999px;
}
.turo-success-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
}
</style>
