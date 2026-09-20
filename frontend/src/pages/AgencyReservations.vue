<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import AgencyLayout from '@/components/AgencyLayout.vue'
import reservationsService from '@/services/reservations'

const loading = ref(true)
const error = ref('')
const notice = ref('')
const reservations = ref([])

const statusFilter = ref('all')
const carFilter = ref('')
const search = ref('')
const expanded = ref(null)

// Per-row busy flag ("<id>:<action>") so several rows can be handled in a row
const busy = ref('')
const rowError = reactive({})

// Reject form: only one open at a time
const rejecting = ref(null)
const rejectReason = ref('')

const STATUS_LABELS = {
  pending: 'En attente de paiement',
  confirmed: 'Confirmée',
  picked_up: 'En cours',
  completed: 'Terminée',
  cancelled: 'Annulée',
  rejected: 'Refusée',
  disputed: 'Litige',
}

const STATUS_CLASSES = {
  pending: 'bg-amber-50 text-amber-800 border-amber-200',
  confirmed: 'bg-emerald-50 text-emerald-800 border-emerald-200',
  picked_up: 'bg-blue-50 text-blue-800 border-blue-200',
  completed: 'bg-slate-100 text-slate-700 border-slate-200',
  cancelled: 'bg-rose-50 text-rose-800 border-rose-200',
  rejected: 'bg-rose-50 text-rose-800 border-rose-200',
  disputed: 'bg-orange-50 text-orange-800 border-orange-200',
}

const TABS = [
  { key: 'all', label: 'Toutes' },
  { key: 'action', label: 'À traiter' },
  { key: 'confirmed', label: 'Confirmées' },
  { key: 'picked_up', label: 'En cours' },
  { key: 'pending', label: 'Attente paiement' },
  { key: 'completed', label: 'Terminées' },
  { key: 'disputed', label: 'Litiges' },
  { key: 'closed', label: 'Annulées / refusées' },
]

function extract(data) {
  if (Array.isArray(data?.reservations)) return data.reservations
  if (Array.isArray(data?.data?.reservations)) return data.data.reservations
  return Array.isArray(data) ? data : []
}

function isPaid(r) {
  return r?.payment?.status === 'paid'
}

function needsPickupConfirmation(r) {
  return r.status === 'confirmed' && !r.agency_pickup_confirmed_at
}

function needsReturnConfirmation(r) {
  return r.status === 'picked_up' && !r.agency_return_confirmed_at
}

function canReject(r) {
  return r.status === 'confirmed' && isPaid(r) && !r.agency_pickup_confirmed_at
}

// Something the agency should do now: the client confirmed and we did not yet
function needsAction(r) {
  return (
    (needsPickupConfirmation(r) && !!r.client_pickup_confirmed_at) ||
    (needsReturnConfirmation(r) && !!r.client_return_confirmed_at) ||
    r.status === 'disputed'
  )
}

const counts = computed(() => ({
  action: reservations.value.filter(needsAction).length,
  pickup: reservations.value.filter((r) => needsPickupConfirmation(r) && r.client_pickup_confirmed_at).length,
  return: reservations.value.filter((r) => needsReturnConfirmation(r) && r.client_return_confirmed_at).length,
  upcoming: reservations.value.filter((r) => r.status === 'confirmed').length,
  ongoing: reservations.value.filter((r) => r.status === 'picked_up').length,
  disputed: reservations.value.filter((r) => r.status === 'disputed').length,
}))

const cars = computed(() => {
  const map = new Map()
  for (const r of reservations.value) {
    if (r.car && !map.has(r.car.id)) map.set(r.car.id, r.car)
  }
  return Array.from(map.values()).sort((a, b) => `${a.brand} ${a.model}`.localeCompare(`${b.brand} ${b.model}`))
})

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  return reservations.value.filter((r) => {
    if (statusFilter.value === 'action' && !needsAction(r)) return false
    if (statusFilter.value === 'closed' && !['cancelled', 'rejected'].includes(r.status)) return false
    if (!['all', 'action', 'closed'].includes(statusFilter.value) && r.status !== statusFilter.value) return false
    if (carFilter.value && r.car_id !== carFilter.value) return false
    if (q) {
      const hay = [
        r.reference,
        r.client?.first_name,
        r.client?.last_name,
        r.client?.email,
        r.client?.phone,
        r.car?.brand,
        r.car?.model,
        r.car?.plate_number,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
      if (!hay.includes(q)) return false
    }
    return true
  })
})

function clientName(r) {
  const name = `${r.client?.first_name || ''} ${r.client?.last_name || ''}`.trim()
  return name || 'Client'
}

function carTitle(r) {
  return `${r.car?.brand || ''} ${r.car?.model || ''}`.trim() || 'Véhicule'
}

function days(r) {
  const diff = new Date(r.end_at) - new Date(r.start_at)
  return diff > 0 ? Math.max(1, Math.ceil(diff / 86400000)) : 0
}

function formatDate(value, withTime = true) {
  if (!value) return '—'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
  })
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('fr-MA')
}

function paymentLabel(r) {
  if (!r.payment) return { label: 'Non payée', cls: 'bg-slate-100 text-slate-600' }
  if (r.payment.status === 'refunded') {
    const pct = r.payment.refund?.percentage
    return { label: pct ? `Remboursée ${Number(pct)}%` : 'Remboursée', cls: 'bg-amber-50 text-amber-800' }
  }
  if (r.payment.status === 'paid') {
    if (r.payment.refund?.status === 'pending') return { label: 'Remboursement à décider', cls: 'bg-orange-50 text-orange-800' }
    return { label: 'Payée', cls: 'bg-emerald-50 text-emerald-700' }
  }
  return { label: r.payment.status, cls: 'bg-slate-100 text-slate-600' }
}

function replaceRow(updated) {
  if (!updated?.id) return
  const idx = reservations.value.findIndex((r) => r.id === updated.id)
  if (idx === -1) return
  // Keep the client (the action endpoints do not embed it)
  reservations.value[idx] = { ...reservations.value[idx], ...updated, client: reservations.value[idx].client }
}

async function run(r, action, request, successMessage) {
  if (busy.value) return false
  busy.value = `${r.id}:${action}`
  rowError[r.id] = ''
  notice.value = ''
  try {
    const data = await request()
    replaceRow(data?.reservation || data?.data?.reservation)
    notice.value = successMessage
    return true
  } catch (err) {
    rowError[r.id] = err?.message || 'Action impossible.'
    return false
  } finally {
    busy.value = ''
  }
}

function confirmPickup(r) {
  return run(r, 'pickup', () => reservationsService.confirmPickupAgency(r.id), `Prise en charge confirmée pour ${r.reference}.`)
}

function confirmReturn(r) {
  return run(r, 'return', () => reservationsService.confirmReturnAgency(r.id), `Retour confirmé pour ${r.reference}.`)
}

function openReject(r) {
  rejecting.value = r.id
  rejectReason.value = ''
  rowError[r.id] = ''
}

function closeReject() {
  rejecting.value = null
  rejectReason.value = ''
}

async function reject(r) {
  const reason = rejectReason.value.trim()
  if (!reason) {
    rowError[r.id] = 'Indiquez le motif du refus : il est transmis au client.'
    return
  }
  const ok = await run(
    r,
    'reject',
    () => reservationsService.rejectReservation(r.id, reason),
    `Réservation ${r.reference} refusée. Le client est remboursé à 100 %.`
  )
  if (ok) closeReject()
}

function toggle(id) {
  expanded.value = expanded.value === id ? null : id
}

function isBusy(r, action) {
  return busy.value === `${r.id}:${action}`
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await reservationsService.getAgencyReservations()
    reservations.value = extract(data)
  } catch (err) {
    error.value = err?.message || 'Impossible de charger les réservations.'
    reservations.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <AgencyLayout>
    <main class="space-y-6" data-testid="agency-reservations">
      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
          <h1 class="font-bricolage text-3xl font-extrabold text-[#0F172A] tracking-tight">Réservations</h1>
          <p class="text-sm text-slate-500 mt-1">
            Toutes les demandes reçues sur votre flotte. Une réservation est confirmée dès que le client a payé ;
            vous pouvez la refuser (remboursement intégral) ou valider les prises en charge et retours.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-[#0F172A] hover:bg-slate-50 disabled:opacity-50"
          :disabled="loading"
          data-testid="refresh-button"
          @click="load"
        >
          <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.6 15A7 7 0 0 0 18.4 9M18.4 9L20 9M5.6 15L4 15" />
          </svg>
          Actualiser
        </button>
      </div>

      <!-- SUMMARY -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" data-testid="summary">
        <button
          type="button"
          class="text-left bg-white rounded-2xl p-5 border shadow-sm transition-colors"
          :class="statusFilter === 'action' ? 'border-[#0F172A]' : 'border-slate-200 hover:border-slate-300'"
          @click="statusFilter = 'action'"
        >
          <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">À traiter</div>
          <div class="font-bricolage text-3xl font-extrabold mt-1" :class="counts.action ? 'text-orange-600' : 'text-[#0F172A]'">
            {{ counts.action }}
          </div>
          <div class="text-xs text-slate-500 mt-1">
            {{ counts.pickup }} pickup · {{ counts.return }} retour · {{ counts.disputed }} litige
          </div>
        </button>
        <button
          type="button"
          class="text-left bg-white rounded-2xl p-5 border shadow-sm transition-colors"
          :class="statusFilter === 'confirmed' ? 'border-[#0F172A]' : 'border-slate-200 hover:border-slate-300'"
          @click="statusFilter = 'confirmed'"
        >
          <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">À venir</div>
          <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-1">{{ counts.upcoming }}</div>
          <div class="text-xs text-slate-500 mt-1">réservations confirmées</div>
        </button>
        <button
          type="button"
          class="text-left bg-white rounded-2xl p-5 border shadow-sm transition-colors"
          :class="statusFilter === 'picked_up' ? 'border-[#0F172A]' : 'border-slate-200 hover:border-slate-300'"
          @click="statusFilter = 'picked_up'"
        >
          <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">En cours</div>
          <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-1">{{ counts.ongoing }}</div>
          <div class="text-xs text-slate-500 mt-1">véhicules sur la route</div>
        </button>
        <button
          type="button"
          class="text-left bg-white rounded-2xl p-5 border shadow-sm transition-colors"
          :class="statusFilter === 'all' ? 'border-[#0F172A]' : 'border-slate-200 hover:border-slate-300'"
          @click="statusFilter = 'all'"
        >
          <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total</div>
          <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-1">{{ reservations.length }}</div>
          <div class="text-xs text-slate-500 mt-1">depuis l’ouverture</div>
        </button>
      </div>

      <!-- FILTERS -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
        <div class="flex flex-wrap gap-2" role="tablist" data-testid="status-tabs">
          <button
            v-for="tab in TABS"
            :key="tab.key"
            type="button"
            role="tab"
            :aria-selected="statusFilter === tab.key"
            class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
            :class="statusFilter === tab.key ? 'bg-[#0F172A] text-white border-[#0F172A]' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
            @click="statusFilter = tab.key"
          >
            {{ tab.label }}
            <span v-if="tab.key === 'action' && counts.action" class="ml-1 px-1.5 rounded-full bg-orange-500 text-white">{{ counts.action }}</span>
          </button>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
          <input
            v-model="search"
            type="search"
            placeholder="Référence, client, e-mail, immatriculation…"
            class="flex-1 rounded-xl border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
            data-testid="search-input"
          />
          <select
            v-model="carFilter"
            class="rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white sm:w-64"
            data-testid="car-filter"
          >
            <option value="">Tous les véhicules</option>
            <option v-for="c in cars" :key="c.id" :value="c.id">{{ c.brand }} {{ c.model }} · {{ c.plate_number }}</option>
          </select>
        </div>
      </div>

      <div v-if="notice" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm" data-testid="notice">
        {{ notice }}
      </div>
      <div v-if="error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm" data-testid="error">
        {{ error }}
      </div>

      <!-- LIST -->
      <div v-if="loading" class="bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-500 shadow-sm">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-slate-800 mb-3"></div>
        <p class="text-sm font-medium">Chargement des réservations…</p>
      </div>

      <div
        v-else-if="filtered.length === 0"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-14 text-center"
        data-testid="empty"
      >
        <div class="w-14 h-14 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
          </svg>
        </div>
        <h3 class="font-bricolage text-lg font-bold text-[#0F172A]">
          {{ reservations.length ? 'Aucune réservation pour ce filtre' : 'Aucune réservation pour le moment' }}
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          {{ reservations.length ? 'Essayez un autre statut ou effacez la recherche.' : 'Vos véhicules apparaissent dans le catalogue public ; les demandes arriveront ici.' }}
        </p>
      </div>

      <ul v-else class="space-y-3" data-testid="reservation-list">
        <li
          v-for="r in filtered"
          :key="r.id"
          class="bg-white rounded-2xl border shadow-sm overflow-hidden"
          :class="needsAction(r) ? 'border-orange-200' : 'border-slate-200'"
          :data-testid="`reservation-${r.id}`"
        >
          <div class="p-5 grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
            <!-- Client -->
            <div class="lg:col-span-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#0F172A] text-white flex items-center justify-center text-sm font-bold shrink-0">
                  {{ clientName(r).split(' ').map((p) => p[0]).join('').slice(0, 2).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <p class="font-bold text-[#0F172A] truncate">{{ clientName(r) }}</p>
                  <p class="text-xs text-slate-500 truncate">{{ r.client?.phone || r.client?.email || '—' }}</p>
                </div>
              </div>
              <p class="text-[11px] font-mono text-slate-400 mt-2">{{ r.reference }} · {{ formatDate(r.created_at, false) }}</p>
            </div>

            <!-- Car + dates -->
            <div class="lg:col-span-4">
              <p class="font-semibold text-[#0F172A]">
                {{ carTitle(r) }}
                <span class="text-xs font-mono text-slate-400 ml-1">{{ r.car?.plate_number }}</span>
              </p>
              <p class="text-sm text-slate-600 mt-1">
                {{ formatDate(r.start_at) }} <span class="text-slate-400">→</span> {{ formatDate(r.end_at) }}
              </p>
              <p class="text-xs text-slate-500 mt-1">
                {{ days(r) }} jour(s) · 📍 {{ r.pickup_point?.name || r.pickupPoint?.name || 'Point de retrait' }}
                <template v-if="(r.return_point?.name || r.returnPoint?.name) && (r.return_point?.id || r.returnPoint?.id) !== (r.pickup_point?.id || r.pickupPoint?.id)">
                  → {{ r.return_point?.name || r.returnPoint?.name }}
                </template>
              </p>
            </div>

            <!-- Amount + status -->
            <div class="lg:col-span-2">
              <p class="font-extrabold text-[#0F172A]">{{ formatMoney(r.total_amount) }} <span class="text-xs font-normal text-slate-500">MAD</span></p>
              <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-[11px] font-bold" :class="paymentLabel(r).cls">
                {{ paymentLabel(r).label }}
              </span>
            </div>

            <div class="lg:col-span-3 flex flex-col items-start lg:items-end gap-2">
              <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold border" :class="STATUS_CLASSES[r.status] || STATUS_CLASSES.completed">
                {{ STATUS_LABELS[r.status] || r.status }}
              </span>

              <p v-if="r.status === 'confirmed' && r.client_pickup_confirmed_at && !r.agency_pickup_confirmed_at" class="text-xs font-semibold text-orange-700">
                Le client a confirmé la prise en charge
              </p>
              <p v-else-if="r.status === 'confirmed' && r.agency_pickup_confirmed_at" class="text-xs text-slate-500">
                Pickup validé · en attente du client
              </p>
              <p v-else-if="r.status === 'picked_up' && r.client_return_confirmed_at && !r.agency_return_confirmed_at" class="text-xs font-semibold text-orange-700">
                Le client a confirmé le retour
              </p>
              <p v-else-if="r.status === 'picked_up' && r.agency_return_confirmed_at" class="text-xs text-slate-500">
                Retour validé · en attente du client
              </p>

              <div class="flex flex-wrap gap-2 lg:justify-end">
                <button
                  v-if="needsPickupConfirmation(r)"
                  type="button"
                  class="px-3 py-1.5 rounded-lg bg-[#0F172A] text-white text-xs font-semibold disabled:opacity-50"
                  :disabled="!!busy"
                  data-testid="confirm-pickup"
                  @click="confirmPickup(r)"
                >
                  {{ isBusy(r, 'pickup') ? 'Confirmation…' : 'Confirmer le pickup' }}
                </button>
                <button
                  v-if="needsReturnConfirmation(r)"
                  type="button"
                  class="px-3 py-1.5 rounded-lg bg-[#0F172A] text-white text-xs font-semibold disabled:opacity-50"
                  :disabled="!!busy"
                  data-testid="confirm-return"
                  @click="confirmReturn(r)"
                >
                  {{ isBusy(r, 'return') ? 'Confirmation…' : 'Confirmer le retour' }}
                </button>
                <button
                  v-if="canReject(r) && rejecting !== r.id"
                  type="button"
                  class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-600 text-xs font-semibold hover:bg-rose-50 disabled:opacity-50"
                  :disabled="!!busy"
                  data-testid="reject-button"
                  @click="openReject(r)"
                >
                  Refuser
                </button>
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50"
                  :aria-expanded="expanded === r.id"
                  data-testid="toggle-details"
                  @click="toggle(r.id)"
                >
                  {{ expanded === r.id ? 'Masquer' : 'Détails' }}
                </button>
              </div>
            </div>
          </div>

          <p v-if="rowError[r.id]" class="px-5 pb-4 text-xs text-rose-600 -mt-2" data-testid="row-error">{{ rowError[r.id] }}</p>

          <!-- REJECT FORM -->
          <form
            v-if="rejecting === r.id"
            class="px-5 pb-5 pt-4 border-t border-rose-100 bg-rose-50/40 space-y-3"
            data-testid="reject-form"
            @submit.prevent="reject(r)"
          >
            <p class="text-sm text-rose-800">
              Refuser cette réservation la passe en <strong>Refusée</strong> et rembourse le client à
              <strong>100 % ({{ formatMoney(r.payment?.amount || r.total_amount) }} MAD)</strong>. Cette action est définitive.
            </p>
            <div>
              <label :for="`reject-reason-${r.id}`" class="block text-xs font-semibold text-slate-700 mb-1">Motif (transmis au client)</label>
              <textarea
                :id="`reject-reason-${r.id}`"
                v-model="rejectReason"
                rows="2"
                maxlength="1000"
                required
                placeholder="Ex. : véhicule immobilisé pour réparation"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                data-testid="reject-reason"
              ></textarea>
            </div>
            <div class="flex gap-2">
              <button type="button" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600" @click="closeReject">
                Retour
              </button>
              <button
                type="submit"
                class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-semibold disabled:opacity-50"
                :disabled="!!busy || !rejectReason.trim()"
                data-testid="reject-confirm"
              >
                {{ isBusy(r, 'reject') ? 'Refus en cours…' : 'Confirmer le refus' }}
              </button>
            </div>
          </form>

          <!-- DETAILS -->
          <div v-if="expanded === r.id" class="px-5 pb-5 pt-4 border-t border-slate-100 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm" data-testid="details">
            <div>
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Client</p>
              <p class="text-[#0F172A] font-semibold">{{ clientName(r) }}</p>
              <p class="text-slate-600">{{ r.client?.email || '—' }}</p>
              <p class="text-slate-600">{{ r.client?.phone || '—' }}</p>
            </div>
            <div>
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Retrait / retour</p>
              <p class="text-slate-700">
                <span class="font-semibold">Départ :</span> {{ r.pickup_point?.name || r.pickupPoint?.name || '—' }}
                <span v-if="r.pickup_point?.address || r.pickupPoint?.address" class="text-slate-500"> · {{ r.pickup_point?.address || r.pickupPoint?.address }}</span>
              </p>
              <p class="text-slate-700 mt-1">
                <span class="font-semibold">Retour :</span> {{ r.return_point?.name || r.returnPoint?.name || '—' }}
                <span v-if="r.return_point?.address || r.returnPoint?.address" class="text-slate-500"> · {{ r.return_point?.address || r.returnPoint?.address }}</span>
              </p>
              <p class="text-xs text-slate-500 mt-2">
                {{ days(r) }} jour(s) × {{ formatMoney(r.daily_price_snapshot) }} MAD
              </p>
            </div>
            <div>
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Suivi</p>
              <ul class="space-y-1 text-slate-600">
                <li>Créée le {{ formatDate(r.created_at) }}</li>
                <li v-if="r.payment?.paid_at">Payée le {{ formatDate(r.payment.paid_at) }}</li>
                <li v-if="r.client_pickup_confirmed_at">Pickup client : {{ formatDate(r.client_pickup_confirmed_at) }}</li>
                <li v-if="r.agency_pickup_confirmed_at">Pickup agence : {{ formatDate(r.agency_pickup_confirmed_at) }}</li>
                <li v-if="r.client_return_confirmed_at">Retour client : {{ formatDate(r.client_return_confirmed_at) }}</li>
                <li v-if="r.agency_return_confirmed_at">Retour agence : {{ formatDate(r.agency_return_confirmed_at) }}</li>
                <li v-if="r.payment?.refund" class="text-amber-800">
                  Remboursement {{ Number(r.payment.refund.percentage) }}% · {{ formatMoney(r.payment.refund.refunded_amount) }} MAD
                  · {{ r.payment.refund.status }}
                  <span v-if="r.payment.refund.reason" class="block text-xs text-slate-500">{{ r.payment.refund.reason }}</span>
                </li>
              </ul>
            </div>
          </div>
        </li>
      </ul>
    </main>
  </AgencyLayout>
</template>
