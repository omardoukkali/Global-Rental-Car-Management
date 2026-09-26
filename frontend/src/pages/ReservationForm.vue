<template>
  <div class="turo-page">
    <!-- ========== SUCCESS ========== -->
    <div v-if="createdReservation" data-testid="success-banner" class="turo-success">
      <div class="turo-success-icon">✓</div>
      <p class="turo-eyebrow turo-teal-text">Demande envoyée</p>
      <h1 class="turo-car-title">Réservation créée</h1>
      <p class="turo-meta">
        Statut pending. Le paiement confirme la réservation auprès de l’agence.
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
        <RouterLink
          v-if="createdReservation.id"
          :to="`/reservations/${createdReservation.id}/pay`"
          class="turo-btn-primary"
        >
          Payer maintenant
        </RouterLink>
        <RouterLink to="/myreservations" class="turo-btn-ghost">Voir mes réservations</RouterLink>
        <button type="button" class="turo-btn-ghost" @click="resetForm">Nouvelle réservation</button>
      </div>
    </div>

    <template v-else>
      <div v-if="globalError" data-testid="global-error" class="turo-alert">
        <strong>Impossible de finaliser la réservation</strong>
        <p>{{ globalError }}</p>
      </div>

      <div v-if="loadingCar" class="turo-loading">
        <div class="turo-spinner" />
        <p>Chargement du véhicule…</p>
      </div>

      <form v-else class="turo-layout" @submit.prevent="handleSubmit">
        <div class="turo-mosaic" :class="mosaicClass">
          <button
            type="button"
            class="turo-mosaic-cell turo-mosaic-main"
            @click="openGallery(0)"
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
            @click="openGallery(1)"
          >
            <img :src="mosaicSlots[1]" :alt="`${carTitle} — 2`" />
            <span
              class="turo-heart-btn"
              :class="{ on: saved }"
              role="presentation"
              @click.stop.prevent="saved = !saved"
            >
              <svg viewBox="0 0 24 24" width="18" height="18" :fill="saved ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2">
                <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.4l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z" />
              </svg>
            </span>
          </button>
          <button
            v-if="mosaicSlots[2]"
            type="button"
            class="turo-mosaic-cell turo-mosaic-side"
            @click="openGallery(2)"
          >
            <img :src="mosaicSlots[2]" :alt="`${carTitle} — 3`" />
            <span v-if="galleryImages.length" class="turo-view-photos">
              Voir {{ galleryImages.length }} photo{{ galleryImages.length > 1 ? 's' : '' }}
            </span>
          </button>
          <button
            v-else-if="galleryImages.length"
            type="button"
            class="turo-view-photos turo-view-photos-float"
            @click="openGallery(0)"
          >
            Voir {{ galleryImages.length }} photo{{ galleryImages.length > 1 ? 's' : '' }}
          </button>
        </div>

        <div class="turo-body-grid">
          <div class="turo-main">
            <header class="turo-heading">
              <h1 class="turo-car-title" data-testid="car-name">
                {{ carTitle || 'Sélectionnez un véhicule' }}
              </h1>
              <p class="turo-subtitle">
                <span v-if="selectedCar?.year">{{ selectedCar.year }}</span>
                <span v-if="selectedCar?.year && selectedCar?.type"> {{ capitalize(selectedCar.type) }}</span>
                <template v-if="agency?.avg_rating">
                  <span class="turo-dot">·</span>
                  <span class="turo-rating">
                    {{ Number(agency.avg_rating).toFixed(1) }}
                    <span class="turo-star">★</span>
                    <a v-if="agency.total_reviews" class="turo-trips" href="#reviews">
                      ({{ agency.total_reviews }} avis)
                    </a>
                  </span>
                </template>
                <span
                  v-if="availabilityStatus"
                  data-testid="availability-pill"
                  class="turo-avail"
                  :class="availabilityStatus.available ? 'ok' : 'ko'"
                >
                  {{ availabilityStatus.available ? 'Disponible' : 'Indisponible' }}
                </span>
              </p>
            </header>

            <div v-if="specs.length" class="turo-pills">
              <div v-for="spec in specs" :key="spec.label" class="turo-pill">
                <CarSpecIcon :name="spec.icon" compact />
                <span>{{ spec.pill || spec.value }}</span>
              </div>
            </div>

            <section v-if="featureGroups.length" class="turo-section">
              <h2 class="turo-section-title">Équipements du véhicule</h2>
              <div class="turo-feature-cols">
                <div v-for="group in featureGroups" :key="group.title" class="turo-feature-col">
                  <h3 class="turo-feature-heading">{{ group.title }}</h3>
                  <ul>
                    <li v-for="item in group.items" :key="item">{{ item }}</li>
                  </ul>
                </div>
              </div>
            </section>

            <section class="turo-section">
              <h2 class="turo-section-title">Inclus dans le prix</h2>
              <ul class="turo-included">
                <li>
                  <span class="turo-included-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 17h14v-5H5z"/><path d="M7 12V7h10v5"/><path d="M5 17l-2 4h4"/><path d="M19 17l2 4h-4"/></svg>
                  </span>
                  <div>
                    <strong>Retrait sans comptoir</strong>
                    <p>Instructions de prise en charge et de restitution via l’agence.</p>
                  </div>
                </li>
                <li>
                  <span class="turo-included-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                  </span>
                  <div>
                    <strong>Conducteurs supplémentaires</strong>
                    <p>Ajoutez un conducteur selon les conditions de l’agence.</p>
                  </div>
                </li>
                <li>
                  <span class="turo-included-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                  </span>
                  <div>
                    <strong>30 minutes de grâce au retour</strong>
                    <p>Pas besoin de prolonger le voyage si vous avez moins de 30 minutes de retard.</p>
                  </div>
                </li>
                <li>
                  <span class="turo-included-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V6l-8-3-8 3v6c0 6 8 10 8 10z"/></svg>
                  </span>
                  <div>
                    <strong>Assistance pendant le voyage</strong>
                    <p>Contactez l’agence en cas de besoin pendant la location.</p>
                  </div>
                </li>
              </ul>
            </section>

            <section v-if="agency" class="turo-section">
              <h2 class="turo-section-title">Proposé par</h2>
              <div class="turo-host">
                <div class="turo-host-avatar">{{ agencyInitials }}</div>
                <div class="turo-host-info">
                  <div class="turo-host-top">
                    <div>
                      <div class="turo-host-name">{{ agency.name }}</div>
                      <div class="turo-host-meta">
                        <span v-if="agency.avg_rating"><span class="turo-star">★</span> {{ Number(agency.avg_rating).toFixed(1) }}</span>
                        <span v-if="agency.total_reviews"> · {{ agency.total_reviews }} avis</span>
                        <span v-if="locationLabel"> · {{ locationLabel }}</span>
                      </div>
                    </div>
                    <span class="turo-allstar">Agence vérifiée</span>
                  </div>
                  <p class="turo-body turo-host-bio">
                    Agence partenaire GlobalRental.
                    <template v-if="agency.address"> Basée à {{ agency.address }}.</template>
                  </p>
                  <RouterLink
                    v-if="agency.id"
                    :to="`/agencies/${agency.id}`"
                    class="turo-change-car"
                    data-testid="agency-link"
                  >
                    Voir la flotte et les avis de l’agence →
                  </RouterLink>
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

            <section v-if="carReviews.length" id="reviews" class="turo-section">
              <h2 class="turo-section-title">Avis</h2>
              <p class="turo-review-score">
                <strong>{{ Number(agency?.avg_rating || avgReviewScore).toFixed(1) }}</strong>
                <span class="turo-star">★</span>
                <span>{{ carReviews.length }} avis</span>
              </p>
              <ul class="turo-reviews">
                <li v-for="review in carReviews.slice(0, 3)" :key="review.id">
                  <p class="turo-review-text">{{ review.comment || 'Séjour recommandé.' }}</p>
                  <p class="turo-review-meta">
                    {{ review.user?.first_name || 'Client' }}
                    <template v-if="review.car_rating"> · {{ Number(review.car_rating).toFixed(1) }}<span class="turo-star">★</span></template>
                  </p>
                </li>
              </ul>
            </section>

            <section class="turo-section">
              <h2 class="turo-section-title">Règles du voyage</h2>
              <ul class="turo-rules">
                <li>
                  <strong>Interdiction de fumer</strong>
                  <p>Véhicule non-fumeur. Un forfait de nettoyage peut s’appliquer.</p>
                </li>
                <li>
                  <strong>Gardez le véhicule propre</strong>
                  <p>Restituez-le dans un état raisonnable, sans déchets.</p>
                </li>
                <li>
                  <strong>Faites le plein</strong>
                  <p>Même niveau de carburant qu’au départ, sinon frais possibles.</p>
                </li>
                <li>
                  <strong>Âge minimum 21 ans</strong>
                  <p>Permis de conduire valide obligatoire au retrait.</p>
                </li>
              </ul>
            </section>
          </div>

          <aside class="turo-sidebar">
            <div class="turo-book-card">
              <div class="turo-price-block">
                <p class="turo-total-line">
                  <span class="turo-price" data-testid="daily-price">{{ formatMoney(dailyPrice) }}</span>
                  <span class="turo-price-unit">MAD / jour</span>
                </p>
                <p class="turo-est-link">
                  <strong data-testid="total-price">{{ formatMoney(totalPrice) }} MAD total</strong>
                  <span> · </span>
                  <span data-testid="duration-days">{{ rentalDays }} jour{{ rentalDays > 1 ? 's' : '' }}</span>
                </p>
                <p class="turo-before-tax">Avant frais éventuels de l’agence</p>
              </div>

              <div class="turo-book-voyage">
                <h3 class="turo-book-voyage-title">Votre voyage</h3>

                <input
                  id="start-at"
                  v-model="form.start_at"
                  type="datetime-local"
                  class="turo-sr-only"
                  :min="minStartDate"
                  @change="onDateChange"
                />
                <input
                  id="end-at"
                  v-model="form.end_at"
                  type="datetime-local"
                  class="turo-sr-only"
                  :min="form.start_at || minStartDate"
                  @change="onDateChange"
                />

                <label class="turo-dt-label">Début du voyage</label>
                <div class="turo-dt-row">
                  <input
                    :value="startDatePart"
                    type="date"
                    class="turo-dt-input"
                    :min="minStartDate.slice(0, 10)"
                    required
                    @input="setStartDate($event.target.value)"
                  />
                  <input
                    :value="startTimePart"
                    type="time"
                    class="turo-dt-input"
                    required
                    @input="setStartTime($event.target.value)"
                  />
                </div>
                <p v-if="errors.start_at" class="turo-error">{{ errors.start_at[0] }}</p>

                <label class="turo-dt-label">Fin du voyage</label>
                <div class="turo-dt-row">
                  <input
                    :value="endDatePart"
                    type="date"
                    class="turo-dt-input"
                    :min="(form.start_at || minStartDate).slice(0, 10)"
                    required
                    @input="setEndDate($event.target.value)"
                  />
                  <input
                    :value="endTimePart"
                    type="time"
                    class="turo-dt-input"
                    required
                    @input="setEndTime($event.target.value)"
                  />
                </div>
                <p v-if="errors.end_at" class="turo-error">{{ errors.end_at[0] }}</p>
              </div>

              <div v-if="dateError" data-testid="date-error" class="turo-error-box">{{ dateError }}</div>

              <div class="turo-location-block">
                <label for="pickup-point" class="turo-dt-label">Prise en charge</label>
                <select
                  id="pickup-point"
                  v-model="form.pickup_point_id"
                  class="turo-dt-input turo-dt-select"
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

                <label for="return-point" class="turo-dt-label">Restitution</label>
                <select
                  id="return-point"
                  v-model="form.return_point_id"
                  class="turo-dt-input turo-dt-select"
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

              <button
                type="submit"
                class="turo-btn-primary turo-btn-block"
                data-testid="submit-button"
                :disabled="submitting || !isValid"
              >
                {{ submitting ? 'Envoi en cours…' : submitLabel }}
              </button>

              <p v-if="isGuest" class="turo-note" data-testid="guest-note">
                Connectez-vous ou créez un compte pour envoyer la demande. Vos dates seront conservées.
              </p>
              <p v-else class="turo-note">Vous ne serez pas débité pour l’instant.</p>

              <div class="turo-card-policy">
                <h4>Politique d’annulation</h4>
                <p>
                  Annulation gratuite tant que la réservation est en attente.
                  Plus d’options flexibles au paiement.
                </p>
              </div>
            </div>
            <p class="turo-report">Signaler ce véhicule</p>
          </aside>
        </div>

        <div v-if="galleryOpen" class="turo-lightbox" role="dialog" aria-modal="true" @click.self="galleryOpen = false">
          <button type="button" class="turo-lightbox-close" @click="galleryOpen = false">Fermer</button>
          <button type="button" class="turo-lightbox-nav prev" @click="shiftGallery(-1)">‹</button>
          <img :src="lightboxUrl" :alt="carTitle" />
          <button type="button" class="turo-lightbox-nav next" @click="shiftGallery(1)">›</button>
        </div>

        <!-- Catalog at bottom (replaces dropdown) — same page -->
        <section id="car-catalog" class="turo-catalog turo-section">
          <div class="turo-pick-head">
            <h2 class="turo-section-title turo-section-title-tight">Parcourir d’autres véhicules</h2>
            <p class="turo-meta">D’autres voitures disponibles aux mêmes dates.</p>
          </div>

          <div v-if="similarCars.length" class="turo-car-grid" data-testid="car-card-grid">
            <button
              v-for="car in similarCars"
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
            {{ submitting ? '…' : submitLabel }}
          </button>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import reservationsService from '@/services/reservations'
import carsService from '@/services/cars'
import CarSpecIcon from '@/components/CarSpecIcon.vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  carId: { type: String, default: null },
})

const router = useRouter()
const route = useRoute()

// Public listing: guests can browse, they log in when they send the request
let auth = null
try {
  auth = useAuthStore()
} catch {
  auth = null
}
const isGuest = computed(() => !!auth && !auth.isAuthenticated)
const submitLabel = computed(() => (isGuest.value ? 'Se connecter pour réserver' : 'Continuer'))

const loadingCar = ref(false)
const submitting = ref(false)
const globalError = ref('')
const dateError = ref('')
const errors = reactive({})
const createdReservation = ref(null)
const availabilityStatus = ref(null)
const activeImage = ref(null)
const saved = ref(false)
const galleryOpen = ref(false)
const lightboxIndex = ref(0)
const carReviews = ref([])

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
  if (car.seats) {
    list.push({ icon: 'seats', label: 'Places', value: `${car.seats} places`, pill: `${car.seats} places` })
  }
  if (car.energy_type) {
    list.push({
      icon: energyIcon(car.energy_type),
      label: 'Carburant',
      value: capitalize(car.energy_type),
      pill: capitalize(car.energy_type),
    })
  }
  if (car.fuel_consumption) {
    list.push({
      icon: 'gauge',
      label: 'Conso.',
      value: `${car.fuel_consumption} L/100`,
      pill: `${car.fuel_consumption} L/100`,
    })
  }
  if (car.transmission) {
    list.push({
      icon: 'gearbox',
      label: 'Boîte',
      value: capitalize(car.transmission),
      pill: capitalize(car.transmission),
    })
  }
  if (car.electric_range) {
    list.push({
      icon: 'battery',
      label: 'Autonomie',
      value: `${car.electric_range} km`,
      pill: `${car.electric_range} km`,
    })
  }
  return list
})

const featureGroups = computed(() => {
  const car = selectedCar.value
  if (!car) return []
  const drive = []
  const comfort = []
  if (car.transmission) drive.push(`Boîte ${car.transmission}`)
  if (car.energy_type) drive.push(`Motorisation ${car.energy_type}`)
  if (car.type) drive.push(capitalize(car.type))
  if (car.seats) comfort.push(`${car.seats} places assises`)
  if (car.color) comfort.push(`Couleur ${car.color}`)
  if (pickupPoints.value.length) comfort.push(`${pickupPoints.value.length} point(s) de retrait`)
  const groups = []
  if (drive.length) groups.push({ title: 'Conduite', items: drive })
  if (comfort.length) groups.push({ title: 'Confort', items: comfort })
  return groups
})

const startDatePart = computed(() => form.start_at?.slice(0, 10) || '')
const startTimePart = computed(() => form.start_at?.slice(11, 16) || '10:00')
const endDatePart = computed(() => form.end_at?.slice(0, 10) || '')
const endTimePart = computed(() => form.end_at?.slice(11, 16) || '10:00')

const lightboxUrl = computed(() => {
  const img = galleryImages.value[lightboxIndex.value]
  return img ? imageUrl(img) : mosaicSlots.value[0]
})

const similarCars = computed(() => {
  const current = selectedCar.value
  const cars = availableCars.value || []
  if (!current) return cars.slice(0, 8)
  const others = cars.filter((car) => car.id !== current.id)
  const sameBrand = others.filter((car) => car.brand === current.brand)
  const rest = others.filter((car) => car.brand !== current.brand)
  return [...sameBrand, ...rest].slice(0, 8)
})

const avgReviewScore = computed(() => {
  if (!carReviews.value.length) return 0
  const sum = carReviews.value.reduce((acc, r) => acc + Number(r.car_rating || r.agency_rating || 0), 0)
  return sum / carReviews.value.length
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

function energyIcon(type) {
  const value = String(type || '').toLowerCase()
  if (value.includes('electric') || value.includes('électr')) return 'bolt'
  if (value.includes('hybrid') || value.includes('hybride')) return 'hybrid'
  return 'fuel'
}

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
  if (p.city && typeof p.city === 'string') return `${p.name} — ${p.city}`
  if (p.city?.name) return `${p.name} — ${p.city.name}`
  return p.name
}

function toLocalInput(date) {
  const pad = (n) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function applyDefaultTrip() {
  if (form.start_at && form.end_at) return
  // Dates carried over from the home search / login redirect
  const qStart = typeof route?.query?.start_at === 'string' ? route.query.start_at : ''
  const qEnd = typeof route?.query?.end_at === 'string' ? route.query.end_at : ''
  if (qStart && qEnd && new Date(qEnd) > new Date(qStart)) {
    form.start_at = qStart.slice(0, 16)
    form.end_at = qEnd.slice(0, 16)
    return
  }
  const start = new Date()
  start.setDate(start.getDate() + 1)
  start.setHours(10, 0, 0, 0)
  const end = new Date(start)
  end.setDate(end.getDate() + 3)
  form.start_at = toLocalInput(start)
  form.end_at = toLocalInput(end)
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
  else router.push('/cars')
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

function openGallery(index) {
  lightboxIndex.value = index
  galleryOpen.value = true
}

function shiftGallery(step) {
  const total = galleryImages.value.length
  if (!total) return
  lightboxIndex.value = (lightboxIndex.value + step + total) % total
}

function combineDateTime(date, time) {
  if (!date) return ''
  return `${date}T${time || '10:00'}`
}

function setStartDate(date) {
  form.start_at = combineDateTime(date, startTimePart.value)
  onDateChange()
}

function setStartTime(time) {
  form.start_at = combineDateTime(startDatePart.value || minStartDate.value.slice(0, 10), time)
  onDateChange()
}

function setEndDate(date) {
  form.end_at = combineDateTime(date, endTimePart.value)
  onDateChange()
}

function setEndTime(time) {
  const fallback = form.start_at?.slice(0, 10) || minStartDate.value.slice(0, 10)
  form.end_at = combineDateTime(endDatePart.value || fallback, time)
  onDateChange()
}

async function loadReviews(carId) {
  if (!carId || typeof carsService.getCarReviews !== 'function') {
    carReviews.value = []
    return
  }
  try {
    const res = await carsService.getCarReviews(carId)
    const payload = res?.reviews
    carReviews.value = payload?.data || (Array.isArray(payload) ? payload : [])
  } catch {
    carReviews.value = []
  }
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

  if (isGuest.value) {
    // Come back to this car with the same dates after login
    const redirect = router.resolve({
      path: `/cars/${form.car_id}`,
      query: { start_at: form.start_at, end_at: form.end_at },
    }).fullPath
    router.push({ name: 'login', query: { redirect } })
    return
  }

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
watch(
  () => selectedCar.value?.id,
  (id) => {
    if (id) loadReviews(id)
  }
)
watch(
  () => [form.start_at, form.end_at],
  () => onDateChange()
)

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
    applyDefaultTrip()
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
  --turo-purple: #593cfb;
  --turo-purple-pressed: #4a2ee0;
  --turo-teal: var(--ink-muted);
  --turo-teal-deep: var(--ink-secondary);
  --turo-ink: var(--ink);
  --turo-muted: var(--ink-muted);
  --turo-canvas: var(--surface);
  --turo-surface: var(--bg);
  --turo-divider: var(--border);
  --turo-error: #ef4444;
  --turo-star: #f59e0b; /* same amber as PublicCarCard / AgencyPublic / ReservationDetail */

  min-height: 100vh;
  background: #fff;
  color: var(--ink);
  font-family: 'DM Sans', system-ui, sans-serif;
  padding: 16px 16px 110px;
}

@media (min-width: 1024px) {
  .turo-page {
    padding: 24px 40px 64px;
  }
}

.turo-sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.turo-layout {
  max-width: 1120px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.turo-body-grid {
  display: grid;
  gap: 28px;
  align-items: start;
}
@media (min-width: 1024px) {
  .turo-body-grid {
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 40px 56px;
  }
  .turo-sidebar {
    position: sticky;
    top: 88px;
  }
}

/* —— Photo mosaic (full width, separate rounded tiles) —— */
.turo-mosaic {
  position: relative;
  display: grid;
  gap: 8px;
  background: transparent;
  overflow: visible;
  min-height: 0;
}
.turo-mosaic.has-1 {
  grid-template-columns: 1fr;
}
.turo-mosaic.has-2 {
  grid-template-columns: 1.7fr 1fr;
}
.turo-mosaic.has-3 {
  grid-template-columns: 1.75fr 1fr;
  grid-template-rows: 1fr 1fr;
  height: min(52vw, 430px);
}
.turo-mosaic.has-3 .turo-mosaic-main {
  grid-row: 1 / span 2;
}
@media (max-width: 699px) {
  .turo-mosaic.has-2,
  .turo-mosaic.has-3 {
    grid-template-columns: 1fr;
    grid-template-rows: none;
    height: auto;
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
  background: #111;
  cursor: pointer;
  overflow: hidden;
  min-height: 180px;
  border-radius: 12px;
}
.turo-mosaic.has-3 .turo-mosaic-main,
.turo-mosaic.has-2 .turo-mosaic-main,
.turo-mosaic.has-1 .turo-mosaic-main {
  min-height: min(42vw, 430px);
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
  background: #f4f4f5;
}
.turo-heart-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 36px;
  height: 36px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  background: #fff;
  color: #111;
  box-shadow: 0 1px 6px rgba(0, 0, 0, 0.12);
  z-index: 2;
}
.turo-heart-btn.on {
  color: #e11d48;
}
.turo-view-photos {
  position: absolute;
  right: 10px;
  bottom: 10px;
  background: #fff;
  color: #111;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 8px 12px;
  border-radius: 8px;
  box-shadow: 0 1px 8px rgba(0, 0, 0, 0.12);
  z-index: 2;
  border: none;
  cursor: pointer;
}
.turo-view-photos-float {
  position: absolute;
  right: 14px;
  bottom: 14px;
}
.turo-hosted-inline {
  font-size: 0.88rem;
  color: var(--turo-muted);
}
.turo-hosted-inline strong {
  color: var(--turo-ink);
  font-weight: 700;
}

.turo-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 16px 0 8px;
}
.turo-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 999px;
  background: #f4f4f5;
  font-size: 0.82rem;
  font-weight: 600;
  color: #111;
}
.turo-subtitle {
  margin: 6px 0 0;
  font-size: 0.95rem;
  color: #3f3f46;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
}
.turo-dot {
  color: #a1a1aa;
}
.turo-trips {
  color: #3f3f46;
  text-decoration: underline;
  text-underline-offset: 2px;
  font-weight: 600;
}
.turo-feature-cols {
  display: grid;
  gap: 20px;
}
@media (min-width: 640px) {
  .turo-feature-cols {
    grid-template-columns: 1fr 1fr;
  }
}
.turo-feature-heading {
  margin: 0 0 8px;
  font-size: 0.95rem;
  font-weight: 700;
}
.turo-feature-col ul {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 6px;
  font-size: 0.92rem;
  color: #3f3f46;
}
.turo-included {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 16px;
}
.turo-included li {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}
.turo-included-icon {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  color: #111;
}
.turo-included-icon svg {
  width: 24px;
  height: 24px;
}
.turo-included strong {
  display: block;
  font-size: 0.92rem;
}
.turo-included p {
  margin: 2px 0 0;
  font-size: 0.82rem;
  color: #71717a;
  line-height: 1.4;
}
.turo-rules {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 14px;
}
.turo-rules strong {
  display: block;
  font-size: 0.92rem;
  margin-bottom: 2px;
}
.turo-rules p {
  margin: 0;
  font-size: 0.84rem;
  color: #71717a;
}
.turo-review-score {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin: 0 0 16px;
}
.turo-review-score strong {
  font-size: 1.8rem;
  font-weight: 800;
}
.turo-reviews {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 16px;
}
.turo-review-text {
  margin: 0 0 6px;
  font-size: 0.95rem;
  line-height: 1.5;
}
.turo-review-meta {
  margin: 0;
  font-size: 0.82rem;
  color: #71717a;
  font-weight: 600;
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
  font-size: clamp(1.7rem, 3vw, 2.15rem);
  font-weight: 800;
  letter-spacing: -0.04em;
  margin: 0;
  line-height: 1.1;
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
  color: var(--turo-star);
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
  background: #fff;
  border: 1px solid #e4e4e7;
  border-radius: 16px;
  padding: 22px 22px 18px;
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.06);
}
.turo-price-block {
  margin-bottom: 18px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f4f4f5;
}
.turo-total-line {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin: 0;
}
.turo-price {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.35rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}
.turo-price-unit {
  color: var(--turo-muted);
  font-size: 0.85rem;
}
.turo-est-link {
  margin: 4px 0 2px;
  color: #111;
  font-size: 1.05rem;
}
.turo-est-link strong {
  font-size: 1.35rem;
  font-weight: 800;
}
.turo-before-tax {
  margin: 0;
  font-size: 0.78rem;
  color: #71717a;
}

.turo-book-voyage {
  margin-bottom: 14px;
}
.turo-book-voyage-title {
  margin: 0 0 12px;
  font-size: 1rem;
  font-weight: 800;
  color: var(--ink);
}
.turo-dt-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: #3f3f46;
  margin: 10px 0 6px;
}
.turo-dt-row {
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  gap: 8px;
}
.turo-dt-input {
  width: 100%;
  border: 1px solid #d4d4d8;
  border-radius: 8px;
  padding: 10px 12px;
  background: #fff;
  font: inherit;
  font-size: 0.88rem;
  font-weight: 600;
  color: #111;
}
.turo-dt-select {
  margin-bottom: 4px;
}
.turo-dt-input.error {
  border-color: var(--turo-error);
  color: var(--turo-error);
}
.turo-location-block {
  margin-bottom: 16px;
}
.turo-card-policy {
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid #f4f4f5;
}
.turo-card-policy h4 {
  margin: 0 0 6px;
  font-size: 0.95rem;
}
.turo-card-policy p {
  margin: 0;
  font-size: 0.8rem;
  color: #71717a;
  line-height: 1.45;
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
  background: var(--turo-purple);
  color: #fff;
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
  background: var(--turo-purple-pressed);
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

.turo-lightbox {
  position: fixed;
  inset: 0;
  z-index: 80;
  background: rgba(10, 10, 11, 0.92);
  display: grid;
  place-items: center;
  padding: 48px 72px;
}
.turo-lightbox img {
  max-width: 100%;
  max-height: 85vh;
  object-fit: contain;
  border-radius: 8px;
}
.turo-lightbox-close {
  position: absolute;
  top: 16px;
  right: 16px;
  background: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  font-weight: 700;
  cursor: pointer;
}
.turo-lightbox-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 999px;
  background: #fff;
  font-size: 1.6rem;
  cursor: pointer;
  line-height: 1;
}
.turo-lightbox-nav.prev {
  left: 16px;
}
.turo-lightbox-nav.next {
  right: 16px;
}
</style>
