<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import reservationsService from '@/services/reservations'

const reservations = ref([])
const loading = ref(true)
const error = ref('')
const cancellingId = ref(null)
const actionError = ref('')

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

async function loadReservations() {
  loading.value = true
  error.value = ''
  actionError.value = ''
  try {
    const data = await reservationsService.getReservations()
    reservations.value = Array.isArray(data?.reservations)
      ? data.reservations
      : Array.isArray(data?.data?.reservations)
        ? data.data.reservations
        : []
  } catch (err) {
    error.value = err?.message || 'Impossible de charger vos réservations.'
    reservations.value = []
  } finally {
    loading.value = false
  }
}

function statusLabel(status) {
  return STATUS_LABELS[status] || status || '—'
}

function statusClass(status) {
  return STATUS_CLASSES[status] || 'bg-slate-100 text-slate-700 border-slate-200'
}

function canCancel(reservation) {
  return ['pending', 'confirmed'].includes(reservation?.status)
}

function carImageUrl(car) {
  const images = car?.images
  if (!images?.length) return null
  const primary = images.find((img) => img.is_primary) || images[0]
  return primary.url || primary.image_url || null
}

function formatDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? '—'
    : date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('fr-MA')
}

async function cancelReservation(reservation) {
  if (!canCancel(reservation) || cancellingId.value) return
  actionError.value = ''
  cancellingId.value = reservation.id
  try {
    const data = await reservationsService.cancelReservation(reservation.id)
    const updated = data?.reservation || data?.data?.reservation
    if (updated) {
      reservations.value = reservations.value.map((item) =>
        item.id === reservation.id ? { ...item, ...updated } : item
      )
    } else {
      await loadReservations()
    }
  } catch (err) {
    actionError.value = err?.message || 'Échec de l’annulation.'
  } finally {
    cancellingId.value = null
  }
}

onMounted(loadReservations)
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC]">
    <header class="bg-white border-b border-slate-200">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        <RouterLink to="/" class="font-bricolage font-extrabold text-lg text-[#0F172A] tracking-tight">
          GlobalRental
        </RouterLink>
        <div class="flex items-center gap-3 text-sm">
          <span class="font-bold text-[#0F172A]">Mes réservations</span>
          <RouterLink
            to="/reservations/new"
            class="font-semibold text-slate-500 hover:text-slate-800 transition-colors"
          >
            Nouvelle
          </RouterLink>
          <RouterLink
            to="/logout"
            class="font-semibold text-rose-600 hover:text-rose-700 transition-colors"
          >
            Déconnexion
          </RouterLink>
        </div>
      </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight font-bricolage">
          Mes réservations
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          Suivez vos locations et annulez celles encore en attente ou confirmées.
        </p>
      </div>

      <div
        v-if="actionError"
        data-testid="action-error"
        class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"
      >
        {{ actionError }}
      </div>

      <div v-if="loading" data-testid="loading" class="text-center py-16">
        <div class="animate-spin rounded-full h-12 w-12 border-2 border-slate-200 border-b-slate-900 mx-auto"></div>
        <p class="mt-4 text-sm text-slate-500">Chargement de vos réservations…</p>
      </div>

      <div
        v-else-if="error"
        data-testid="list-error"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-10 text-center"
      >
        <p class="text-red-600 mb-4">{{ error }}</p>
        <button
          type="button"
          class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold"
          @click="loadReservations"
        >
          Réessayer
        </button>
      </div>

      <div
        v-else-if="reservations.length === 0"
        data-testid="empty-state"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-12 text-center"
      >
        <p class="text-slate-500 text-lg">Vous n’avez pas encore de réservation.</p>
        <RouterLink
          to="/reservations/new"
          class="mt-4 inline-block font-semibold text-slate-900 underline underline-offset-4"
        >
          Réserver un véhicule
        </RouterLink>
      </div>

      <ul v-else data-testid="reservations-list" class="space-y-4" role="list">
        <li
          v-for="res in reservations"
          :key="res.id"
          data-testid="reservation-item"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
        >
          <div class="p-5 sm:p-6 flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between">
            <div class="flex items-start gap-4 min-w-0">
              <div class="h-14 w-14 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0 flex items-center justify-center">
                <img
                  v-if="carImageUrl(res.car)"
                  :src="carImageUrl(res.car)"
                  :alt="`${res.car?.brand || ''} ${res.car?.model || ''}`"
                  class="h-full w-full object-cover"
                />
                <span v-else class="text-xs text-slate-400 font-bold">N/A</span>
              </div>
              <div class="min-w-0">
                <p class="font-extrabold text-[#0F172A] truncate">
                  {{ res.car?.brand }} {{ res.car?.model }}
                  <span v-if="res.car?.year" class="text-slate-400 font-semibold text-sm">
                    ({{ res.car.year }})
                  </span>
                </p>
                <p class="text-sm text-slate-500 mt-0.5">
                  {{ res.agency?.name || 'Agence' }}
                </p>
                <p class="text-sm text-slate-600 mt-2">
                  Du {{ formatDate(res.start_at) }} au {{ formatDate(res.end_at) }}
                </p>
                <p v-if="res.reference" class="text-xs text-slate-400 mt-1 font-mono">
                  {{ res.reference }}
                </p>
              </div>
            </div>

            <div class="flex flex-col sm:items-end gap-3 flex-shrink-0">
              <span
                data-testid="status-badge"
                class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold border"
                :class="statusClass(res.status)"
              >
                {{ statusLabel(res.status) }}
              </span>
              <p class="text-lg font-extrabold text-[#0F172A]">
                {{ formatMoney(res.total_amount) }}
                <span class="text-xs font-normal text-slate-500">MAD</span>
              </p>
              <button
                v-if="canCancel(res)"
                type="button"
                data-testid="cancel-button"
                class="text-sm font-semibold text-rose-600 hover:text-rose-700 disabled:opacity-50"
                :disabled="cancellingId === res.id"
                @click="cancelReservation(res)"
              >
                {{ cancellingId === res.id ? 'Annulation…' : 'Annuler' }}
              </button>
            </div>
          </div>
        </li>
      </ul>
    </main>
  </div>
</template>
