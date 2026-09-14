<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import reservationsService from '@/services/reservations'

const reservationId = ref('')
const loading = ref(false)
const error = ref('')
const success = ref('')
const result = ref(null)

async function confirmPickup() {
  const id = reservationId.value.trim()
  if (!id || loading.value) return

  loading.value = true
  error.value = ''
  success.value = ''
  result.value = null

  try {
    const data = await reservationsService.confirmPickupAgency(id)
    const reservation = data?.reservation || data?.data?.reservation || null
    result.value = reservation
    success.value = data?.message || 'Prise en charge confirmée.'
  } catch (err) {
    error.value = err?.message || 'Échec de la confirmation du pickup.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC]">
    <header class="bg-white border-b border-slate-200">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        <RouterLink
          to="/agency/dashboard"
          class="font-bricolage font-extrabold text-lg text-[#0F172A] tracking-tight"
        >
          GlobalRental
        </RouterLink>
        <RouterLink
          to="/agency/dashboard"
          class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition-colors"
        >
          ← Retour au tableau de bord
        </RouterLink>
      </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight font-bricolage">
          Confirmer la prise en charge
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          Entrez l’identifiant de la réservation pour confirmer le pickup côté agence.
        </p>
      </div>

      <form
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4"
        @submit.prevent="confirmPickup"
      >
        <div>
          <label for="reservation-id" class="block text-sm font-semibold text-slate-700 mb-1.5">
            ID de réservation
          </label>
          <input
            id="reservation-id"
            v-model="reservationId"
            data-testid="reservation-id-input"
            type="text"
            required
            placeholder="UUID de la réservation"
            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
          />
        </div>

        <button
          type="submit"
          data-testid="agency-confirm-pickup-button"
          class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50"
          :disabled="loading || !reservationId.trim()"
        >
          {{ loading ? 'Confirmation…' : 'Confirmer le pickup' }}
        </button>
      </form>

      <div
        v-if="error"
        data-testid="agency-pickup-error"
        class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"
      >
        {{ error }}
      </div>

      <div
        v-if="success"
        data-testid="agency-pickup-success"
        class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm space-y-2"
      >
        <p class="font-semibold">{{ success }}</p>
        <p v-if="result?.status" class="text-xs font-mono text-emerald-700">
          Statut: {{ result.status }}
          <span v-if="result.reference"> · {{ result.reference }}</span>
        </p>
      </div>
    </main>
  </div>
</template>
