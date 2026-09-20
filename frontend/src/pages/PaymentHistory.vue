<script setup>
import { computed, onMounted, ref } from 'vue'
import reservationsService from '@/services/reservations'

const loading = ref(true)
const error = ref('')
const reservations = ref([])

function extractReservations(data) {
  if (Array.isArray(data?.reservations)) return data.reservations
  if (Array.isArray(data?.data?.reservations)) return data.data.reservations
  return []
}

const PAID_STATUSES = ['confirmed', 'picked_up', 'completed', 'disputed']

const entries = computed(() =>
  reservations.value
    .map((item) => {
      if (item?.payment) {
        return {
          id: item.payment.id,
          reservationId: item.id,
          reference: item.reference,
          car: `${item.car?.brand || ''} ${item.car?.model || ''}`.trim(),
          amount: item.payment.amount,
          status: item.payment.status,
          paidAt: item.payment.paid_at,
          transactionId: item.payment.transaction_id,
          refund: item.payment.refund || null,
        }
      }

      // GET /reservations does not embed payment; confirmed+ implies POST /payments already ran.
      if (!PAID_STATUSES.includes(item?.status)) return null

      return {
        id: item.id,
        reservationId: item.id,
        reference: item.reference,
        car: `${item.car?.brand || ''} ${item.car?.model || ''}`.trim(),
        amount: item.total_amount,
        status: 'paid',
        paidAt: item.updated_at,
        transactionId: null,
        refund: null,
      }
    })
    .filter(Boolean)
)

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

function statusClass(status) {
  if (status === 'paid') return 'bg-emerald-50 text-emerald-800 border-emerald-200'
  if (status === 'refunded') return 'bg-amber-50 text-amber-800 border-amber-200'
  if (status === 'failed') return 'bg-rose-50 text-rose-800 border-rose-200'
  return 'bg-slate-100 text-slate-700 border-slate-200'
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await reservationsService.getReservations()
    reservations.value = extractReservations(data)
  } catch (err) {
    error.value = err?.message || 'Impossible de charger l’historique.'
    reservations.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC]">
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight font-bricolage">
          Historique paiements et remboursements
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          Historique construit depuis GET /reservations. Le paiement client passe par POST /payments.
        </p>
      </div>

      <div v-if="error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        {{ error }}
      </div>

      <div v-if="loading" class="text-center py-16 text-sm text-slate-500">Chargement…</div>

      <div
        v-else-if="entries.length === 0"
        data-testid="payments-empty"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-12 text-center text-slate-500"
      >
        Aucun paiement pour le moment.
      </div>

      <ul v-else data-testid="payments-list" class="space-y-4">
        <li
          v-for="entry in entries"
          :key="entry.id"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6"
        >
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
              <p class="font-extrabold text-[#0F172A]">{{ entry.car || 'Véhicule' }}</p>
              <p class="text-xs font-mono text-slate-400 mt-1">{{ entry.reference }}</p>
              <p class="text-xs text-slate-500 mt-2">Payé le {{ formatDate(entry.paidAt) }}</p>
              <p v-if="entry.transactionId" class="text-xs font-mono text-slate-400 mt-1">
                {{ entry.transactionId }}
              </p>
            </div>
            <div class="sm:text-right space-y-2">
              <span
                class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold border"
                :class="statusClass(entry.status)"
              >
                {{ entry.status }}
              </span>
              <p class="text-lg font-extrabold text-[#0F172A]">
                {{ formatMoney(entry.amount) }}
                <span class="text-xs font-normal text-slate-500">MAD</span>
              </p>
            </div>
          </div>

          <div
            v-if="entry.refund"
            data-testid="refund-block"
            class="mt-4 pt-4 border-t border-slate-100 text-sm text-slate-600 space-y-1"
          >
            <p class="font-semibold text-[#0F172A]">Remboursement</p>
            <p>Statut: {{ entry.refund.status }} · {{ entry.refund.percentage }}%</p>
            <p>Montant: {{ formatMoney(entry.refund.refunded_amount) }} MAD</p>
            <p v-if="entry.refund.reason" class="text-xs text-slate-500">{{ entry.refund.reason }}</p>
          </div>
        </li>
      </ul>
    </main>
  </div>
</template>
