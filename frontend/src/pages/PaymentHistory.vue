<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import paymentsService from '@/services/payments'

const loading = ref(true)
const error = ref('')
const payments = ref([])
const filter = ref('all')

function extractPayments(data) {
  if (Array.isArray(data?.payments)) return data.payments
  if (Array.isArray(data?.payments?.data)) return data.payments.data
  if (Array.isArray(data?.data)) return data.data
  return []
}

const entries = computed(() =>
  payments.value.map((payment) => {
    const reservation = payment.reservation || {}
    const car = `${reservation.car?.brand || ''} ${reservation.car?.model || ''}`.trim()
    const refund = payment.refund || null
    const amount = Number(payment.amount || 0)
    const refunded = refund && refund.status === 'processed' ? Number(refund.refunded_amount || 0) : 0

    return {
      id: payment.id,
      reservationId: payment.reservation_id || reservation.id,
      reference: reservation.reference,
      car,
      agency: reservation.agency?.name || '',
      startAt: reservation.start_at,
      endAt: reservation.end_at,
      reservationStatus: reservation.status,
      amount,
      status: payment.status,
      paidAt: payment.paid_at || payment.created_at,
      transactionId: payment.transaction_id,
      refund,
      refunded,
      kept: Math.max(amount - refunded, 0),
    }
  })
)

const filtered = computed(() => {
  if (filter.value === 'refunded') return entries.value.filter((e) => e.refund)
  if (filter.value === 'paid') return entries.value.filter((e) => e.status === 'paid' && !e.refund)
  return entries.value
})

const totals = computed(() => {
  const paid = entries.value.reduce((sum, e) => sum + e.amount, 0)
  const refunded = entries.value.reduce((sum, e) => sum + e.refunded, 0)
  const pending = entries.value.filter((e) => e.refund && e.refund.status !== 'processed').length
  return { paid, refunded, spent: Math.max(paid - refunded, 0), pending, count: entries.value.length }
})

const filters = computed(() => [
  { key: 'all', label: 'Tous', count: entries.value.length },
  { key: 'paid', label: 'Payés', count: entries.value.filter((e) => e.status === 'paid' && !e.refund).length },
  { key: 'refunded', label: 'Remboursements', count: entries.value.filter((e) => e.refund).length },
])

function formatMoney(value) {
  return Number(value || 0).toLocaleString('fr-MA')
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

const PAYMENT_LABELS = {
  paid: 'Payé',
  refunded: 'Remboursé',
  failed: 'Échoué',
  pending: 'En attente',
}

function paymentLabel(status) {
  return PAYMENT_LABELS[status] || status || '—'
}

const RESERVATION_LABELS = {
  pending: 'en attente',
  confirmed: 'confirmée',
  picked_up: 'en cours',
  completed: 'terminée',
  cancelled: 'annulée',
  rejected: 'refusée',
  disputed: 'en litige',
}

function reservationLabel(status) {
  return RESERVATION_LABELS[status] || status || '—'
}

function paymentClass(status) {
  if (status === 'paid') return 'bg-emerald-50 text-emerald-800 border-emerald-200'
  if (status === 'refunded') return 'bg-amber-50 text-amber-800 border-amber-200'
  if (status === 'failed') return 'bg-rose-50 text-rose-800 border-rose-200'
  return 'bg-slate-100 text-slate-700 border-slate-200'
}

function refundTitle(refund) {
  if (!refund) return ''
  if (refund.status === 'processed') return `Remboursement de ${Math.round(Number(refund.percentage || 0))} % effectué`
  if (refund.status === 'pending') return 'Remboursement en attente de l’agence'
  return `Remboursement ${refund.status}`
}

function refundDescription(entry) {
  const refund = entry.refund
  if (!refund) return ''
  const pct = Math.round(Number(refund.percentage || 0))
  if (refund.status === 'processed') {
    const source = refund.decision_source === 'agency' ? 'décision de l’agence' : 'règle automatique'
    return `${formatMoney(refund.refunded_amount)} MAD remboursés (${pct} %, ${source}) le ${formatDate(refund.processed_at || refund.decided_at)}.`
  }
  if (refund.status === 'pending') {
    return `Annulation tardive : ${formatMoney(refund.refunded_amount)} MAD (${pct} %) sont garantis. L’agence peut porter le remboursement jusqu’à 100 %.`
  }
  return `${formatMoney(refund.refunded_amount)} MAD (${pct} %).`
}

function refundClass(refund) {
  if (!refund) return ''
  if (refund.status === 'processed') return 'bg-emerald-50 border-emerald-200 text-emerald-900'
  if (refund.status === 'pending') return 'bg-amber-50 border-amber-200 text-amber-900'
  return 'bg-slate-50 border-slate-200 text-slate-700'
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await paymentsService.getPayments()
    payments.value = extractPayments(data)
  } catch (err) {
    error.value = err?.response?.data?.message || err?.message || 'Impossible de charger l’historique.'
    payments.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC]">
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight font-bricolage">
            Paiements et remboursements
          </h1>
          <p class="text-sm text-slate-500 mt-1">
            Chaque paiement effectué sur GlobalRental, avec le détail des remboursements.
          </p>
        </div>
        <button
          type="button"
          class="self-start sm:self-auto px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
          :disabled="loading"
          @click="load"
        >
          Actualiser
        </button>
      </div>

      <div v-if="error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        {{ error }}
      </div>

      <div v-if="loading" class="text-center py-16 text-sm text-slate-500">Chargement…</div>

      <template v-else>
        <section
          v-if="entries.length"
          data-testid="payments-summary"
          class="grid grid-cols-1 sm:grid-cols-3 gap-4"
        >
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total payé</p>
            <p class="mt-2 text-2xl font-extrabold text-[#0F172A]">
              {{ formatMoney(totals.paid) }} <span class="text-xs font-normal text-slate-500">MAD</span>
            </p>
            <p class="text-xs text-slate-500 mt-1">{{ totals.count }} paiement(s)</p>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Remboursé</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-700">
              {{ formatMoney(totals.refunded) }} <span class="text-xs font-normal text-slate-500">MAD</span>
            </p>
            <p class="text-xs text-slate-500 mt-1">
              <template v-if="totals.pending">{{ totals.pending }} décision(s) d’agence en attente</template>
              <template v-else>Aucun remboursement en attente</template>
            </p>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Dépense nette</p>
            <p class="mt-2 text-2xl font-extrabold text-[#0F172A]">
              {{ formatMoney(totals.spent) }} <span class="text-xs font-normal text-slate-500">MAD</span>
            </p>
            <p class="text-xs text-slate-500 mt-1">Payé moins remboursé</p>
          </div>
        </section>

        <div v-if="entries.length" data-testid="payments-filters" class="flex flex-wrap gap-2">
          <button
            v-for="f in filters"
            :key="f.key"
            type="button"
            class="px-3.5 py-1.5 rounded-full text-xs font-bold border transition"
            :class="filter === f.key
              ? 'bg-[#0F172A] text-white border-[#0F172A]'
              : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
            @click="filter = f.key"
          >
            {{ f.label }}
            <span class="ml-1 opacity-70">{{ f.count }}</span>
          </button>
        </div>

        <div
          v-if="entries.length === 0"
          data-testid="payments-empty"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-12 text-center text-slate-500"
        >
          <p class="font-semibold text-[#0F172A]">Aucun paiement pour le moment.</p>
          <p class="text-sm mt-1">Vos paiements apparaîtront ici dès qu’une réservation sera réglée.</p>
          <RouterLink
            to="/cars"
            class="inline-flex mt-5 px-4 py-2 rounded-xl bg-[#0F172A] text-white text-sm font-semibold hover:bg-slate-800 transition"
          >
            Trouver une voiture
          </RouterLink>
        </div>

        <div
          v-else-if="filtered.length === 0"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-10 text-center text-sm text-slate-500"
        >
          Aucun élément dans ce filtre.
        </div>

        <ul v-else data-testid="payments-list" class="space-y-4">
          <li
            v-for="entry in filtered"
            :key="entry.id"
            class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6"
          >
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
              <div class="min-w-0">
                <p class="font-extrabold text-[#0F172A]">{{ entry.car || 'Véhicule' }}</p>
                <p v-if="entry.agency" class="text-xs text-slate-500 mt-0.5">{{ entry.agency }}</p>
                <p class="text-xs text-slate-500 mt-2">
                  {{ formatDate(entry.startAt, false) }} → {{ formatDate(entry.endAt, false) }}
                </p>
                <p class="text-xs text-slate-500 mt-2">Payé le {{ formatDate(entry.paidAt) }}</p>
                <p class="text-xs font-mono text-slate-400 mt-1">
                  <span>{{ entry.reference }}</span>
                  <span v-if="entry.transactionId"> · {{ entry.transactionId }}</span>
                </p>
              </div>
              <div class="sm:text-right space-y-2 shrink-0">
                <span
                  class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold border"
                  :class="paymentClass(entry.status)"
                >
                  {{ paymentLabel(entry.status) }}
                </span>
                <p class="text-lg font-extrabold text-[#0F172A]">
                  {{ formatMoney(entry.amount) }}
                  <span class="text-xs font-normal text-slate-500">MAD</span>
                </p>
                <p v-if="entry.refunded" class="text-xs text-emerald-700 font-semibold">
                  − {{ formatMoney(entry.refunded) }} MAD remboursés
                </p>
              </div>
            </div>

            <div
              v-if="entry.refund"
              data-testid="refund-block"
              class="mt-4 rounded-xl border p-4 text-sm space-y-1"
              :class="refundClass(entry.refund)"
            >
              <p class="font-bold">{{ refundTitle(entry.refund) }}</p>
              <p>{{ refundDescription(entry) }}</p>
              <p v-if="entry.refund.reason" class="text-xs opacity-80 italic">« {{ entry.refund.reason }} »</p>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
              <span class="text-slate-500">
                Réservation <span class="font-semibold text-slate-700">{{ reservationLabel(entry.reservationStatus) }}</span>
              </span>
              <RouterLink
                v-if="entry.reservationId"
                :to="`/reservations/${entry.reservationId}`"
                class="font-semibold text-[#0F172A] hover:underline"
              >
                Voir la réservation →
              </RouterLink>
            </div>
          </li>
        </ul>
      </template>
    </main>
  </div>
</template>
