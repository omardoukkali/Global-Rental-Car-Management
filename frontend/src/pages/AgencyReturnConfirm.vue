<script setup>
import { ref } from 'vue'
import AgencyLayout from '@/components/AgencyLayout.vue'
import reservationsService from '@/services/reservations'

const reservationId = ref('')
const loading = ref(false)
const error = ref('')
const success = ref('')
const result = ref(null)

async function confirmReturn() {
  const id = reservationId.value.trim()
  if (!id || loading.value) return

  loading.value = true
  error.value = ''
  success.value = ''
  result.value = null

  try {
    const data = await reservationsService.confirmReturnAgency(id)
    const reservation = data?.reservation || data?.data?.reservation || null
    result.value = reservation
    success.value = data?.message || 'Retour confirmé.'
  } catch (err) {
    error.value = err?.message || 'Échec de la confirmation du retour.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AgencyLayout>
    <div class="max-w-3xl space-y-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight font-bricolage">
          Confirmer le retour
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          Entrez l’identifiant de la réservation pour confirmer le retour côté agence.
        </p>
      </div>

      <form
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4"
        @submit.prevent="confirmReturn"
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
          data-testid="agency-confirm-return-button"
          class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50"
          :disabled="loading || !reservationId.trim()"
        >
          {{ loading ? 'Confirmation…' : 'Confirmer le retour' }}
        </button>
      </form>

      <div
        v-if="error"
        data-testid="agency-return-error"
        class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"
      >
        {{ error }}
      </div>

      <div
        v-if="success"
        data-testid="agency-return-success"
        class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm space-y-2"
      >
        <p class="font-semibold">{{ success }}</p>
        <p v-if="result?.status" class="text-xs font-mono text-emerald-700">
          Statut: {{ result.status }}
          <span v-if="result.reference"> · {{ result.reference }}</span>
        </p>
      </div>
    </div>
  </AgencyLayout>
</template>
