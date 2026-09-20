<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import reservationsService from '@/services/reservations'
import paymentsService from '@/services/payments'

const route = useRoute()
const router = useRouter()

const reservation = ref(null)
const payment = ref(null)
const loading = ref(true)
const paying = ref(false)
const error = ref('')

const reservationId = computed(() => route.params.id)

function formatMoney(value) {
  return Number(value || 0).toLocaleString('fr-MA')
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

function extractReservation(data) {
  return data?.reservation || data?.data?.reservation || null
}

async function loadReservation() {
  loading.value = true
  error.value = ''
  try {
    const data = await reservationsService.getReservation(reservationId.value)
    reservation.value = extractReservation(data)
    if (reservation.value?.payment?.status === 'paid') {
      payment.value = reservation.value.payment
    }
  } catch (err) {
    error.value = err?.message || 'Impossible de charger la réservation.'
    reservation.value = null
  } finally {
    loading.value = false
  }
}

async function pay() {
  if (paying.value || reservation.value?.status !== 'pending') return
  paying.value = true
  error.value = ''
  try {
    const data = await paymentsService.createPayment(reservation.value.id)
    payment.value = data?.payment || data?.data?.payment
    if (reservation.value) {
      reservation.value = {
        ...reservation.value,
        status: payment.value?.reservation?.status || 'confirmed',
        payment: payment.value,
      }
    }
  } catch (err) {
    error.value = err?.message || 'Échec du paiement.'
  } finally {
    paying.value = false
  }
}

onMounted(loadReservation)
</script>

<template>
  <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC]">
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight font-bricolage">
          Paiement
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          Le paiement confirme la réservation (pending → confirmed) via POST /payments.
        </p>
      </div>

      <div
        v-if="error"
        data-testid="payment-error"
        class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"
      >
        {{ error }}
      </div>

      <div v-if="loading" class="text-center py-16 text-sm text-slate-500">Chargement…</div>

      <div
        v-else-if="reservation"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5"
      >
        <div class="space-y-3 text-sm">
          <div class="flex justify-between gap-4">
            <span class="text-slate-500">Référence</span>
            <span class="font-mono text-xs text-[#0F172A]">{{ reservation.reference || reservation.id }}</span>
          </div>
          <div class="flex justify-between gap-4">
            <span class="text-slate-500">Véhicule</span>
            <strong>{{ reservation.car?.brand }} {{ reservation.car?.model }}</strong>
          </div>
          <div class="flex justify-between gap-4">
            <span class="text-slate-500">Agence</span>
            <span>{{ reservation.agency?.name || '—' }}</span>
          </div>
          <div class="flex justify-between gap-4">
            <span class="text-slate-500">Période</span>
            <span>{{ formatDate(reservation.start_at) }} → {{ formatDate(reservation.end_at) }}</span>
          </div>
          <div class="flex justify-between gap-4">
            <span class="text-slate-500">Statut réservation</span>
            <span class="font-bold">{{ reservation.status }}</span>
          </div>
          <div class="flex justify-between gap-4 pt-2 border-t border-slate-100">
            <span class="text-slate-500">Montant</span>
            <span class="text-xl font-extrabold text-[#0F172A]">
              {{ formatMoney(reservation.total_amount) }}
              <span class="text-xs font-normal text-slate-500">MAD</span>
            </span>
          </div>
        </div>

        <div
          v-if="payment"
          data-testid="payment-success"
          class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm space-y-1"
        >
          <p class="font-semibold">Paiement enregistré.</p>
          <p class="font-mono text-xs">TXN: {{ payment.transaction_id }}</p>
          <p class="text-xs">Statut paiement: {{ payment.status }}</p>
          <div class="pt-2 flex gap-3">
            <button type="button" class="font-semibold underline underline-offset-4" @click="router.push('/myreservations')">
              Mes réservations
            </button>
            <button type="button" class="font-semibold underline underline-offset-4" @click="router.push('/payments')">
              Historique des paiements
            </button>
          </div>
        </div>

        <button
          v-else-if="reservation.status === 'pending'"
          type="button"
          data-testid="pay-button"
          class="w-full px-5 py-3 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50"
          :disabled="paying"
          @click="pay"
        >
          {{ paying ? 'Traitement…' : `Payer ${formatMoney(reservation.total_amount)} MAD` }}
        </button>

        <p v-else class="text-sm text-slate-500">
          Cette réservation n’est plus en attente de paiement.
        </p>
      </div>
    </main>
  </div>
</template>
