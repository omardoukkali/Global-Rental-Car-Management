<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import reservationsService from '@/services/reservations'
import reviewsService from '@/services/reviews'
import carsService from '@/services/cars'

const route = useRoute()
const router = useRouter()

const reservationId = computed(() => String(route.params.id || ''))

const reservation = ref(null)
const review = ref(null)
const points = ref([])
const loading = ref(true)
const error = ref('')
const actionError = ref('')
const notice = ref('')
const busy = ref('')

const editing = ref(false)
const editForm = reactive({ start_at: '', end_at: '', pickup_point_id: '', return_point_id: '' })
const editErrors = reactive({})

const disputeOpen = ref(false)

const reviewForm = reactive({ car_rating: 5, agency_rating: 5, comment: '' })
const reviewErrors = reactive({})

const STATUS_LABELS = {
  pending: 'En attente',
  confirmed: 'Confirmée',
  picked_up: 'En cours',
  completed: 'Terminée',
  cancelled: 'Annulée',
  disputed: 'Litige',
  rejected: 'Refusée',
}

const STATUS_CLASSES = {
  pending: 'bg-amber-50 text-amber-800 border-amber-200',
  confirmed: 'bg-emerald-50 text-emerald-800 border-emerald-200',
  picked_up: 'bg-blue-50 text-blue-800 border-blue-200',
  completed: 'bg-slate-100 text-slate-700 border-slate-200',
  cancelled: 'bg-rose-50 text-rose-800 border-rose-200',
  disputed: 'bg-orange-50 text-orange-800 border-orange-200',
  rejected: 'bg-rose-50 text-rose-800 border-rose-200',
}

const status = computed(() => reservation.value?.status || '')
const car = computed(() => reservation.value?.car || null)
const agency = computed(() => reservation.value?.agency || null)

const carTitle = computed(() => (car.value ? `${car.value.brand || ''} ${car.value.model || ''}`.trim() : 'Véhicule'))

// GET /reservations/{id} returns the car without its images: the public car fills them in
const publicCar = ref(null)

const carImage = computed(() => {
  const images = car.value?.images?.length ? car.value.images : publicCar.value?.images || []
  const primary = images.find((img) => img.is_primary) || images[0]
  return primary?.url || primary?.image_url || null
})

const days = computed(() => {
  if (!reservation.value?.start_at || !reservation.value?.end_at) return 0
  const diff = new Date(reservation.value.end_at) - new Date(reservation.value.start_at)
  return diff > 0 ? Math.max(1, Math.ceil(diff / 86400000)) : 0
})

const isPaid = computed(() => reservation.value?.payment?.status === 'paid')

const canPay = computed(() => status.value === 'pending')
const canCancel = computed(() => ['pending', 'confirmed'].includes(status.value))
const canEdit = computed(() => ['pending', 'confirmed'].includes(status.value))
const canConfirmPickup = computed(() => status.value === 'confirmed' && !reservation.value?.client_pickup_confirmed_at)
const waitingAgencyPickup = computed(
  () => status.value === 'confirmed' && !!reservation.value?.client_pickup_confirmed_at && !reservation.value?.agency_pickup_confirmed_at
)
const canConfirmReturn = computed(() => status.value === 'picked_up' && !reservation.value?.client_return_confirmed_at)
const waitingAgencyReturn = computed(
  () => status.value === 'picked_up' && !!reservation.value?.client_return_confirmed_at && !reservation.value?.agency_return_confirmed_at
)
const canDispute = computed(() => status.value === 'picked_up')
const canReview = computed(() => status.value === 'completed' && !review.value)

const pickupPoints = computed(() => points.value.filter((p) => p.allows_pickup !== false && p.is_active !== false))
const returnPoints = computed(() => points.value.filter((p) => p.allows_return !== false && p.is_active !== false))

const editDays = computed(() => {
  if (!editForm.start_at || !editForm.end_at) return 0
  const diff = new Date(editForm.end_at) - new Date(editForm.start_at)
  return diff > 0 ? Math.max(1, Math.ceil(diff / 86400000)) : 0
})
const editTotal = computed(() => editDays.value * Number(reservation.value?.daily_price_snapshot || car.value?.daily_price || 0))
const editDateError = computed(() => {
  if (!editForm.start_at || !editForm.end_at) return ''
  return new Date(editForm.end_at) <= new Date(editForm.start_at) ? 'La date de retour doit être après le départ.' : ''
})

const minStart = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const timeline = computed(() => {
  const r = reservation.value
  if (!r) return []
  const steps = [
    { key: 'created', label: 'Demande envoyée', at: r.created_at, done: true },
    {
      key: 'confirmed',
      label: status.value === 'rejected' ? 'Refusée par l’agence' : 'Confirmée par l’agence',
      at: r.confirmed_at || r.rejected_at || null,
      done: ['confirmed', 'picked_up', 'completed', 'disputed', 'rejected'].includes(status.value),
      error: status.value === 'rejected',
    },
    {
      key: 'pickup',
      label: 'Prise en charge',
      at: r.agency_pickup_confirmed_at || r.client_pickup_confirmed_at || null,
      done: ['picked_up', 'completed', 'disputed'].includes(status.value),
    },
    {
      key: 'return',
      label: status.value === 'disputed' ? 'Litige ouvert' : 'Restitution',
      at: r.agency_return_confirmed_at || r.client_return_confirmed_at || null,
      done: ['completed', 'disputed'].includes(status.value),
      error: status.value === 'disputed',
    },
  ]
  if (status.value === 'cancelled') {
    return [steps[0], { key: 'cancelled', label: 'Annulée', at: r.cancelled_at || r.updated_at, done: true, error: true }]
  }
  return steps
})

function statusLabel(value) {
  return STATUS_LABELS[value] || value || '—'
}
function statusClass(value) {
  return STATUS_CLASSES[value] || 'bg-slate-100 text-slate-700 border-slate-200'
}

function formatDate(value, withTime = true) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
  })
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('fr-MA')
}

function toLocalInput(value) {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function extractReservation(data) {
  return data?.reservation || data?.data?.reservation || null
}

function applyUpdated(data) {
  const updated = extractReservation(data)
  if (updated) reservation.value = { ...reservation.value, ...updated }
}

async function loadPoints() {
  const r = reservation.value
  const fromReservation = [r?.pickup_point, r?.pickupPoint, r?.return_point, r?.returnPoint].filter(Boolean)
  let list = []
  try {
    if (r?.car_id) {
      const res = await carsService.getPublicCar(r.car_id)
      publicCar.value = res?.car || res?.data?.car || null
      list = publicCar.value?.agency?.agency_points || publicCar.value?.agency?.agencyPoints || []
    }
  } catch {
    list = []
  }
  const map = new Map()
  for (const p of [...list, ...fromReservation]) if (p?.id) map.set(p.id, p)
  points.value = [...map.values()]
}

async function loadReview() {
  if (status.value !== 'completed') return
  try {
    const res = await reviewsService.getMyReviews()
    const list = res?.reviews || res?.data?.reviews || []
    review.value = list.find((rv) => rv.reservation_id === reservation.value.id) || null
  } catch {
    review.value = null
  }
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await reservationsService.getReservation(reservationId.value)
    reservation.value = extractReservation(data)
    if (!reservation.value) throw new Error('Réservation introuvable.')
    await Promise.all([loadReview(), loadPoints()])
  } catch (err) {
    error.value = err?.status === 403 || err?.status === 404
      ? 'Cette réservation n’existe pas ou ne vous appartient pas.'
      : err?.message || 'Impossible de charger la réservation.'
    reservation.value = null
  } finally {
    loading.value = false
  }
}

async function run(action, key, successMessage) {
  if (busy.value) return
  busy.value = key
  actionError.value = ''
  notice.value = ''
  try {
    const data = await action()
    applyUpdated(data)
    if (successMessage) notice.value = successMessage
    return true
  } catch (err) {
    actionError.value = err?.message || 'Action impossible.'
    return false
  } finally {
    busy.value = ''
  }
}

function cancel() {
  if (!canCancel.value) return
  if (typeof window !== 'undefined' && !window.confirm('Annuler cette réservation ?')) return
  run(() => reservationsService.cancelReservation(reservation.value.id), 'cancel', 'Réservation annulée.')
}

function confirmPickup() {
  run(() => reservationsService.confirmPickupClient(reservation.value.id), 'pickup', 'Prise en charge confirmée.')
}

function confirmReturn() {
  run(() => reservationsService.confirmReturnClient(reservation.value.id), 'return', 'Retour confirmé.')
}

async function dispute() {
  const ok = await run(
    () => reservationsService.disputeReservation(reservation.value.id),
    'dispute',
    'Litige ouvert. L’agence et GlobalRental ont été notifiés.'
  )
  if (ok) disputeOpen.value = false
}

function startEdit() {
  Object.keys(editErrors).forEach((k) => delete editErrors[k])
  editForm.start_at = toLocalInput(reservation.value.start_at)
  editForm.end_at = toLocalInput(reservation.value.end_at)
  editForm.pickup_point_id = reservation.value.pickup_point_id || ''
  editForm.return_point_id = reservation.value.return_point_id || ''
  editing.value = true
}

async function saveEdit() {
  if (editDateError.value) return
  Object.keys(editErrors).forEach((k) => delete editErrors[k])
  // Inputs are local time: send absolute instants so the stored time is not shifted
  const toIso = (local) => new Date(local).toISOString()
  const payload = {}
  if (editForm.start_at !== toLocalInput(reservation.value.start_at)) payload.start_at = toIso(editForm.start_at)
  if (editForm.end_at !== toLocalInput(reservation.value.end_at)) payload.end_at = toIso(editForm.end_at)
  if (editForm.pickup_point_id && editForm.pickup_point_id !== reservation.value.pickup_point_id) payload.pickup_point_id = editForm.pickup_point_id
  if (editForm.return_point_id && editForm.return_point_id !== reservation.value.return_point_id) payload.return_point_id = editForm.return_point_id

  if (Object.keys(payload).length === 0) {
    editing.value = false
    return
  }

  busy.value = 'edit'
  actionError.value = ''
  notice.value = ''
  try {
    const data = await reservationsService.updateReservation(reservation.value.id, payload)
    applyUpdated(data)
    editing.value = false
    notice.value = 'Réservation mise à jour.'
  } catch (err) {
    if (err?.errors) Object.assign(editErrors, err.errors)
    actionError.value = err?.message || 'Modification impossible.'
  } finally {
    busy.value = ''
  }
}

async function submitReview() {
  Object.keys(reviewErrors).forEach((k) => delete reviewErrors[k])
  busy.value = 'review'
  actionError.value = ''
  notice.value = ''
  try {
    const data = await reviewsService.createReview({
      reservation_id: reservation.value.id,
      car_rating: Number(reviewForm.car_rating),
      agency_rating: Number(reviewForm.agency_rating),
      comment: reviewForm.comment.trim() || null,
    })
    review.value = data?.review || data?.data?.review || { ...reviewForm, created_at: new Date().toISOString() }
    notice.value = 'Merci pour votre avis !'
  } catch (err) {
    if (err?.errors) Object.assign(reviewErrors, err.errors)
    actionError.value = err?.message || 'Envoi de l’avis impossible.'
  } finally {
    busy.value = ''
  }
}

function goBack() {
  if (typeof window !== 'undefined' && window.history.length > 1) router.back()
  else router.push('/myreservations')
}

onMounted(load)
</script>

<template>
  <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC]" data-testid="reservation-detail">
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <button type="button" class="text-xs font-bold text-slate-500 hover:text-slate-800 inline-flex items-center gap-1" @click="goBack">
        ← Mes réservations
      </button>

      <div v-if="loading" data-testid="loading" class="text-center py-16">
        <div class="animate-spin rounded-full h-12 w-12 border-2 border-slate-200 border-b-slate-900 mx-auto"></div>
        <p class="mt-4 text-sm text-slate-500">Chargement de votre voyage…</p>
      </div>

      <div v-else-if="error" data-testid="detail-error" class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-10 text-center">
        <p class="text-red-600 mb-4">{{ error }}</p>
        <RouterLink to="/myreservations" class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold inline-block">
          Retour à mes réservations
        </RouterLink>
      </div>

      <template v-else-if="reservation">
        <!-- HEADER -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="grid grid-cols-1 md:grid-cols-5">
            <div class="md:col-span-2 aspect-[4/3] md:aspect-auto bg-slate-100 min-h-[200px]">
              <img v-if="carImage" :src="carImage" :alt="carTitle" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-sm">Photo indisponible</div>
            </div>
            <div class="md:col-span-3 p-6 sm:p-8 space-y-4">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    Réservation <span class="font-mono normal-case">{{ reservation.reference || reservation.id?.slice(0, 8) }}</span>
                  </p>
                  <h1 class="font-bricolage text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight mt-1" data-testid="car-title">
                    {{ carTitle }}
                    <span v-if="car?.year" class="text-slate-400 font-semibold text-lg">{{ car.year }}</span>
                  </h1>
                  <p class="text-sm text-slate-500 mt-1">
                    <RouterLink v-if="agency?.id" :to="`/agencies/${agency.id}`" class="font-semibold text-[#0F172A] hover:underline">
                      {{ agency.name }}
                    </RouterLink>
                    <span v-else>{{ agency?.name || 'Agence' }}</span>
                    <span v-if="agency?.phone"> · {{ agency.phone }}</span>
                  </p>
                </div>
                <span data-testid="status-badge" class="inline-flex px-3 py-1 rounded-full text-xs font-bold border" :class="statusClass(status)">
                  {{ statusLabel(status) }}
                </span>
              </div>

              <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                  <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Départ</p>
                  <p class="font-semibold text-[#0F172A] mt-0.5">{{ formatDate(reservation.start_at) }}</p>
                  <p class="text-xs text-slate-500 mt-1">📍 {{ reservation.pickup_point?.name || reservation.pickupPoint?.name || '—' }}</p>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                  <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Retour</p>
                  <p class="font-semibold text-[#0F172A] mt-0.5">{{ formatDate(reservation.end_at) }}</p>
                  <p class="text-xs text-slate-500 mt-1">📍 {{ reservation.return_point?.name || reservation.returnPoint?.name || '—' }}</p>
                </div>
              </div>

              <div class="flex flex-wrap items-end justify-between gap-3 pt-2 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                  {{ days }} jour{{ days > 1 ? 's' : '' }} × {{ formatMoney(reservation.daily_price_snapshot || car?.daily_price) }} MAD
                </p>
                <p class="text-2xl font-extrabold text-[#0F172A]">
                  {{ formatMoney(reservation.total_amount) }} <span class="text-sm font-normal text-slate-500">MAD</span>
                </p>
              </div>
            </div>
          </div>
        </section>

        <div v-if="actionError" data-testid="action-error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
          {{ actionError }}
        </div>
        <div v-if="notice" data-testid="action-notice" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
          {{ notice }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- TIMELINE -->
          <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-[#0F172A] mb-4">Suivi du voyage</h2>
            <ol class="space-y-4">
              <li v-for="(step, i) in timeline" :key="step.key" class="flex gap-3">
                <div class="flex flex-col items-center">
                  <span
                    class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold border"
                    :class="step.error
                      ? 'bg-rose-100 border-rose-300 text-rose-700'
                      : step.done
                        ? 'bg-emerald-500 border-emerald-500 text-white'
                        : 'bg-white border-slate-200 text-slate-400'"
                  >
                    <template v-if="step.error">✕</template>
                    <template v-else-if="step.done">✓</template>
                    <template v-else>{{ i + 1 }}</template>
                  </span>
                  <span v-if="i < timeline.length - 1" class="flex-1 w-px bg-slate-200 mt-1"></span>
                </div>
                <div class="pb-1">
                  <p class="text-sm font-semibold" :class="step.done ? 'text-[#0F172A]' : 'text-slate-400'">{{ step.label }}</p>
                  <p v-if="step.at && step.done" class="text-xs text-slate-400">{{ formatDate(step.at) }}</p>
                </div>
              </li>
            </ol>
          </section>

          <!-- ACTIONS -->
          <section class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
              <h2 class="font-bold text-[#0F172A] mb-4">Actions</h2>

              <div class="flex flex-wrap gap-2" data-testid="actions">
                <RouterLink
                  v-if="canPay"
                  :to="`/reservations/${reservation.id}/pay`"
                  class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold"
                  data-testid="pay-link"
                >
                  Payer maintenant
                </RouterLink>
                <button
                  v-if="canEdit && !editing"
                  type="button"
                  class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-[#0F172A] hover:bg-slate-50"
                  data-testid="edit-button"
                  @click="startEdit"
                >
                  Modifier dates & lieux
                </button>
                <button
                  v-if="canConfirmPickup"
                  type="button"
                  class="px-4 py-2 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50"
                  :disabled="!!busy"
                  data-testid="confirm-pickup-button"
                  @click="confirmPickup"
                >
                  {{ busy === 'pickup' ? 'Confirmation…' : 'Confirmer la prise en charge' }}
                </button>
                <button
                  v-if="canConfirmReturn"
                  type="button"
                  class="px-4 py-2 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50"
                  :disabled="!!busy"
                  data-testid="confirm-return-button"
                  @click="confirmReturn"
                >
                  {{ busy === 'return' ? 'Confirmation…' : 'Confirmer le retour' }}
                </button>
                <button
                  v-if="canDispute && !disputeOpen"
                  type="button"
                  class="px-4 py-2 rounded-xl border border-orange-200 bg-orange-50 text-orange-800 text-sm font-semibold"
                  data-testid="dispute-button"
                  @click="disputeOpen = true"
                >
                  Signaler un problème
                </button>
                <button
                  v-if="canCancel"
                  type="button"
                  class="px-4 py-2 rounded-xl border border-rose-200 text-rose-600 text-sm font-semibold hover:bg-rose-50 disabled:opacity-50"
                  :disabled="!!busy"
                  data-testid="cancel-button"
                  @click="cancel"
                >
                  {{ busy === 'cancel' ? 'Annulation…' : 'Annuler la réservation' }}
                </button>
              </div>

              <p v-if="waitingAgencyPickup" class="text-xs font-semibold text-slate-500 mt-3" data-testid="waiting-agency-pickup">
                Vous avez confirmé la prise en charge. En attente de la confirmation de l’agence.
              </p>
              <p v-if="waitingAgencyReturn" class="text-xs font-semibold text-slate-500 mt-3" data-testid="waiting-agency-return">
                Vous avez confirmé le retour. En attente de la confirmation de l’agence.
              </p>
              <p
                v-if="!canPay && !canEdit && !canConfirmPickup && !canConfirmReturn && !canDispute && !canCancel && !waitingAgencyPickup && !waitingAgencyReturn"
                class="text-sm text-slate-500"
              >
                {{ status === 'completed' ? 'Ce voyage est terminé. Merci d’avoir voyagé avec GlobalRental !' : 'Aucune action disponible pour ce statut.' }}
              </p>

              <!-- EDIT FORM -->
              <form v-if="editing" class="mt-5 pt-5 border-t border-slate-100 space-y-4" data-testid="edit-form" @submit.prevent="saveEdit">
                <p v-if="isPaid" class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                  Cette réservation est payée : seuls les lieux peuvent être modifiés, pas les dates.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <label class="block">
                    <span class="block text-xs font-bold text-slate-500 mb-1">Départ</span>
                    <input
                      v-model="editForm.start_at"
                      type="datetime-local"
                      :min="minStart"
                      :disabled="isPaid"
                      class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:bg-slate-50"
                      :class="{ 'border-rose-400': editErrors.start_at }"
                    />
                    <span v-if="editErrors.start_at" class="text-xs text-rose-600">{{ editErrors.start_at[0] }}</span>
                  </label>
                  <label class="block">
                    <span class="block text-xs font-bold text-slate-500 mb-1">Retour</span>
                    <input
                      v-model="editForm.end_at"
                      type="datetime-local"
                      :min="editForm.start_at || minStart"
                      :disabled="isPaid"
                      class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:bg-slate-50"
                      :class="{ 'border-rose-400': editErrors.end_at || editDateError }"
                    />
                    <span v-if="editDateError" class="text-xs text-rose-600">{{ editDateError }}</span>
                    <span v-else-if="editErrors.end_at" class="text-xs text-rose-600">{{ editErrors.end_at[0] }}</span>
                  </label>
                  <label class="block">
                    <span class="block text-xs font-bold text-slate-500 mb-1">Prise en charge</span>
                    <select v-model="editForm.pickup_point_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                      <option v-for="p in pickupPoints" :key="p.id" :value="p.id">{{ p.name }} — {{ p.address }}</option>
                    </select>
                  </label>
                  <label class="block">
                    <span class="block text-xs font-bold text-slate-500 mb-1">Restitution</span>
                    <select v-model="editForm.return_point_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                      <option v-for="p in returnPoints" :key="p.id" :value="p.id">{{ p.name }} — {{ p.address }}</option>
                    </select>
                  </label>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <p class="text-sm text-slate-600">
                    Nouveau total estimé :
                    <strong class="text-[#0F172A]">{{ formatMoney(editTotal) }} MAD</strong>
                    <span class="text-slate-400">({{ editDays }} j)</span>
                  </p>
                  <div class="flex gap-2">
                    <button type="button" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-500" @click="editing = false">Annuler</button>
                    <button
                      type="submit"
                      class="px-4 py-2 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50"
                      :disabled="!!busy || !!editDateError"
                      data-testid="edit-save"
                    >
                      {{ busy === 'edit' ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                  </div>
                </div>
              </form>

              <!-- DISPUTE -->
              <div v-if="disputeOpen" class="mt-5 pt-5 border-t border-slate-100 space-y-3" data-testid="dispute-panel">
                <p class="text-sm text-slate-700">
                  Un litige gèle la réservation et alerte GlobalRental. Utilisez-le en cas de véhicule non conforme,
                  de frais contestés ou de désaccord avec l’agence. Cette action est définitive.
                </p>
                <div class="flex gap-2">
                  <button type="button" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-500" @click="disputeOpen = false">Retour</button>
                  <button
                    type="button"
                    class="px-4 py-2 rounded-xl bg-orange-600 text-white text-sm font-semibold disabled:opacity-50"
                    :disabled="!!busy"
                    data-testid="dispute-confirm"
                    @click="dispute"
                  >
                    {{ busy === 'dispute' ? 'Ouverture…' : 'Ouvrir le litige' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- REVIEW -->
            <div v-if="status === 'completed'" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6" data-testid="review-section">
              <h2 class="font-bold text-[#0F172A] mb-1">Votre avis</h2>

              <div v-if="review" class="mt-3 p-4 rounded-xl bg-slate-50 border border-slate-100" data-testid="review-existing">
                <p class="text-amber-500 text-sm">
                  Véhicule {{ '★'.repeat(Math.round(Number(review.car_rating || 0))) }}
                  <span class="text-slate-400 ml-2">Agence {{ '★'.repeat(Math.round(Number(review.agency_rating || 0))) }}</span>
                </p>
                <p v-if="review.comment" class="text-sm text-slate-700 mt-2">{{ review.comment }}</p>
                <p class="text-xs text-slate-400 mt-2">Publié le {{ formatDate(review.created_at, false) }}</p>
              </div>

              <form v-else class="mt-3 space-y-4" data-testid="review-form" @submit.prevent="submitReview">
                <p class="text-sm text-slate-500">Votre voyage est terminé. Notez le véhicule et l’agence pour aider les prochains clients.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <p class="text-xs font-bold text-slate-500 mb-1">Véhicule</p>
                    <div class="flex gap-1">
                      <button
                        v-for="n in 5"
                        :key="'c' + n"
                        type="button"
                        class="text-2xl leading-none"
                        :class="n <= reviewForm.car_rating ? 'text-amber-500' : 'text-slate-300'"
                        :aria-label="`${n} étoile${n > 1 ? 's' : ''} véhicule`"
                        @click="reviewForm.car_rating = n"
                      >
                        ★
                      </button>
                    </div>
                    <p v-if="reviewErrors.car_rating" class="text-xs text-rose-600 mt-1">{{ reviewErrors.car_rating[0] }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-bold text-slate-500 mb-1">Agence</p>
                    <div class="flex gap-1">
                      <button
                        v-for="n in 5"
                        :key="'a' + n"
                        type="button"
                        class="text-2xl leading-none"
                        :class="n <= reviewForm.agency_rating ? 'text-amber-500' : 'text-slate-300'"
                        :aria-label="`${n} étoile${n > 1 ? 's' : ''} agence`"
                        @click="reviewForm.agency_rating = n"
                      >
                        ★
                      </button>
                    </div>
                    <p v-if="reviewErrors.agency_rating" class="text-xs text-rose-600 mt-1">{{ reviewErrors.agency_rating[0] }}</p>
                  </div>
                </div>
                <label class="block">
                  <span class="block text-xs font-bold text-slate-500 mb-1">Commentaire <span class="font-normal text-slate-400">(optionnel)</span></span>
                  <textarea
                    v-model="reviewForm.comment"
                    rows="3"
                    maxlength="5000"
                    placeholder="État du véhicule, accueil, ponctualité…"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                  ></textarea>
                </label>
                <button
                  type="submit"
                  class="px-5 py-2.5 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50"
                  :disabled="!!busy"
                  data-testid="review-submit"
                >
                  {{ busy === 'review' ? 'Envoi…' : 'Publier mon avis' }}
                </button>
              </form>
            </div>
          </section>
        </div>
      </template>
    </main>
  </div>
</template>
