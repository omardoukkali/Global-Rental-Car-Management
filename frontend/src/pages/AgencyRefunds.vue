<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AgencyLayout from '@/components/AgencyLayout.vue'
import refundsService from '@/services/refunds'

const loading = ref(true)
const error = ref('')
const notice = ref('')
const refunds = ref([])

const tab = ref('pending')
const TABS = [
  { key: 'pending', label: 'À décider' },
  { key: 'processed', label: 'Traités' },
  { key: 'all', label: 'Tous' },
]

// Decision form (one at a time)
const deciding = ref(null)
const decision = reactive({ percentage: 50, reason: '' })
const busy = ref('')
const rowError = reactive({})

const STATUS = {
  pending: { label: 'En attente de décision', cls: 'bg-orange-50 text-orange-800 border-orange-200' },
  processing: { label: 'En traitement', cls: 'bg-blue-50 text-blue-800 border-blue-200' },
  processed: { label: 'Remboursé', cls: 'bg-emerald-50 text-emerald-800 border-emerald-200' },
  failed: { label: 'Échoué', cls: 'bg-rose-50 text-rose-800 border-rose-200' },
}

const SOURCE = {
  automatic: 'Règle automatique',
  agency: 'Décision agence',
  admin: 'Décision GlobalRental',
}

function extract(data) {
  if (Array.isArray(data?.refunds)) return data.refunds
  if (Array.isArray(data?.data?.refunds)) return data.data.refunds
  return Array.isArray(data) ? data : []
}

const pending = computed(() => refunds.value.filter((r) => r.status === 'pending'))
const processed = computed(() => refunds.value.filter((r) => r.status === 'processed'))

const pendingExposure = computed(() =>
  pending.value.reduce((acc, r) => acc + Number(r.payment?.amount || 0), 0)
)
const refundedTotal = computed(() =>
  processed.value.reduce((acc, r) => acc + Number(r.refunded_amount || 0), 0)
)

const filtered = computed(() => {
  if (tab.value === 'pending') return pending.value
  if (tab.value === 'processed') return refunds.value.filter((r) => r.status !== 'pending')
  return refunds.value
})

function reservation(r) {
  return r.payment?.reservation || null
}

function clientName(r) {
  const c = reservation(r)?.client
  const name = `${c?.first_name || ''} ${c?.last_name || ''}`.trim()
  return name || 'Client'
}

function carTitle(r) {
  const car = reservation(r)?.car
  return `${car?.brand || ''} ${car?.model || ''}`.trim() || 'Véhicule'
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('fr-MA', { maximumFractionDigits: 0 })
}

function formatDate(value, withTime = false) {
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

function amountFor(r, percentage) {
  return (Number(r.payment?.amount || 0) * Number(percentage || 0)) / 100
}

function openDecision(r) {
  deciding.value = r.id
  decision.percentage = Math.max(50, Math.min(100, Number(r.percentage || 50)))
  decision.reason = ''
  rowError[r.id] = ''
}

function closeDecision() {
  deciding.value = null
}

async function submitDecision(r) {
  if (busy.value) return
  const pct = Number(decision.percentage)
  if (Number.isNaN(pct) || pct < 50 || pct > 100) {
    rowError[r.id] = 'Le pourcentage doit être compris entre 50 et 100.'
    return
  }
  busy.value = r.id
  rowError[r.id] = ''
  notice.value = ''
  try {
    const payload = { percentage: pct }
    if (decision.reason.trim()) payload.reason = decision.reason.trim()
    const data = await refundsService.decideRefund(r.id, payload)
    const updated = data?.refund || data?.data?.refund
    const idx = refunds.value.findIndex((x) => x.id === r.id)
    if (idx !== -1) {
      // The decision endpoint does not embed the client/car: keep what the list gave us
      refunds.value[idx] = { ...refunds.value[idx], ...(updated || {}), payment: refunds.value[idx].payment }
      if (updated?.payment?.status) refunds.value[idx].payment = { ...refunds.value[idx].payment, status: updated.payment.status }
    }
    notice.value = `Remboursement de ${pct} % validé (${formatMoney(amountFor(r, pct))} MAD) pour ${clientName(r)}.`
    closeDecision()
  } catch (err) {
    rowError[r.id] = err?.message || 'Impossible d’enregistrer la décision.'
  } finally {
    busy.value = ''
  }
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await refundsService.getAgencyRefunds()
    refunds.value = extract(data)
  } catch (err) {
    error.value = err?.message || 'Impossible de charger les remboursements.'
    refunds.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <AgencyLayout>
    <main class="space-y-6" data-testid="agency-refunds">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
          <h1 class="font-bricolage text-3xl font-extrabold text-[#0F172A] tracking-tight">Remboursements</h1>
          <p class="text-sm text-slate-500 mt-1">
            Annulation à plus de 24 h du départ : le client est remboursé à 100 % automatiquement.
            Annulation tardive : 50 % minimum, à vous de décider jusqu’à 100 %.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-[#0F172A] hover:bg-slate-50 disabled:opacity-50"
          :disabled="loading"
          @click="load"
        >
          Actualiser
        </button>
      </div>

      <!-- SUMMARY -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" data-testid="summary">
        <div class="bg-white rounded-2xl p-5 border shadow-sm" :class="pending.length ? 'border-orange-200' : 'border-slate-200'">
          <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">À décider</div>
          <div class="font-bricolage text-3xl font-extrabold mt-1" :class="pending.length ? 'text-orange-600' : 'text-[#0F172A]'">
            {{ pending.length }}
          </div>
          <div class="text-xs text-slate-500 mt-1">{{ formatMoney(pendingExposure) }} MAD de paiements concernés</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Remboursé</div>
          <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-1">{{ formatMoney(refundedTotal) }} <span class="text-sm font-normal text-slate-400">MAD</span></div>
          <div class="text-xs text-slate-500 mt-1">{{ processed.length }} remboursement(s) traité(s)</div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
          <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Politique</div>
          <div class="text-sm text-[#0F172A] font-semibold mt-2">≥ 24 h avant : 100 %</div>
          <div class="text-sm text-[#0F172A] font-semibold">&lt; 24 h avant : 50 % → 100 % (votre choix)</div>
        </div>
      </div>

      <div class="flex flex-wrap gap-2" role="tablist" data-testid="tabs">
        <button
          v-for="t in TABS"
          :key="t.key"
          type="button"
          role="tab"
          :aria-selected="tab === t.key"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :class="tab === t.key ? 'bg-[#0F172A] text-white border-[#0F172A]' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
          @click="tab = t.key"
        >
          {{ t.label }}
          <span v-if="t.key === 'pending' && pending.length" class="ml-1 px-1.5 rounded-full bg-orange-500 text-white">{{ pending.length }}</span>
        </button>
      </div>

      <div v-if="notice" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm" data-testid="notice">{{ notice }}</div>
      <div v-if="error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm" data-testid="error">{{ error }}</div>

      <div v-if="loading" class="bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-500 shadow-sm">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-slate-800 mb-3"></div>
        <p class="text-sm font-medium">Chargement des remboursements…</p>
      </div>

      <div v-else-if="filtered.length === 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-14 text-center" data-testid="empty">
        <h3 class="font-bricolage text-lg font-bold text-[#0F172A]">
          {{ tab === 'pending' ? 'Aucune décision en attente' : 'Aucun remboursement' }}
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          {{ tab === 'pending' ? 'Les annulations tardives de vos clients apparaîtront ici.' : 'Les remboursements traités apparaîtront ici.' }}
        </p>
      </div>

      <ul v-else class="space-y-3" data-testid="refund-list">
        <li
          v-for="r in filtered"
          :key="r.id"
          class="bg-white rounded-2xl border shadow-sm overflow-hidden"
          :class="r.status === 'pending' ? 'border-orange-200' : 'border-slate-200'"
          :data-testid="`refund-${r.id}`"
        >
          <div class="p-5 grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
            <div class="lg:col-span-4">
              <p class="font-bold text-[#0F172A]">{{ clientName(r) }}</p>
              <p class="text-sm text-slate-600 mt-0.5">{{ carTitle(r) }}</p>
              <p class="text-xs text-slate-500 mt-1">
                {{ formatDate(reservation(r)?.start_at) }} → {{ formatDate(reservation(r)?.end_at) }}
              </p>
              <p class="text-[11px] font-mono text-slate-400 mt-1">{{ reservation(r)?.reference || r.payment_id }}</p>
            </div>

            <div class="lg:col-span-3 text-sm">
              <p class="text-slate-500">Paiement <span class="font-bold text-[#0F172A]">{{ formatMoney(r.payment?.amount) }} MAD</span></p>
              <p class="mt-1 text-slate-500">
                Remboursement
                <span class="font-bold" :class="r.status === 'pending' ? 'text-orange-700' : 'text-[#0F172A]'">
                  {{ Number(r.percentage) }} % · {{ formatMoney(r.refunded_amount) }} MAD
                </span>
              </p>
              <p class="text-xs text-slate-400 mt-1">{{ SOURCE[r.decision_source] || r.decision_source }}</p>
            </div>

            <div class="lg:col-span-3 text-xs text-slate-500 space-y-1">
              <p>Demandé le {{ formatDate(r.created_at, true) }}</p>
              <p v-if="r.processed_at">Traité le {{ formatDate(r.processed_at, true) }}</p>
              <p v-if="r.reason" class="italic text-slate-600">« {{ r.reason }} »</p>
            </div>

            <div class="lg:col-span-2 flex flex-col items-start lg:items-end gap-2">
              <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold border" :class="(STATUS[r.status] || STATUS.processing).cls">
                {{ (STATUS[r.status] || { label: r.status }).label }}
              </span>
              <button
                v-if="r.status === 'pending' && deciding !== r.id"
                type="button"
                class="px-3 py-1.5 rounded-lg bg-[#0F172A] text-white text-xs font-semibold"
                data-testid="decide-button"
                @click="openDecision(r)"
              >
                Décider
              </button>
            </div>
          </div>

          <p v-if="rowError[r.id]" class="px-5 pb-4 text-xs text-rose-600 -mt-2" data-testid="row-error">{{ rowError[r.id] }}</p>

          <!-- DECISION FORM -->
          <form
            v-if="deciding === r.id"
            class="px-5 pb-5 pt-4 border-t border-orange-100 bg-orange-50/40 space-y-4"
            data-testid="decision-form"
            @submit.prevent="submitDecision(r)"
          >
            <p class="text-sm text-slate-700">
              Annulation tardive : le client a droit à <strong>50 % minimum</strong>. Vous pouvez aller jusqu’à 100 %
              (geste commercial). Le paiement passera en <strong>remboursé</strong> ; cette décision est définitive.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label :for="`pct-${r.id}`" class="block text-xs font-semibold text-slate-700 mb-1">
                  Pourcentage remboursé : <span class="text-[#0F172A] font-extrabold">{{ decision.percentage }} %</span>
                </label>
                <input
                  :id="`pct-${r.id}`"
                  v-model.number="decision.percentage"
                  type="range"
                  min="50"
                  max="100"
                  step="5"
                  class="w-full accent-[#0F172A]"
                  data-testid="decision-percentage"
                />
                <div class="flex justify-between text-[11px] text-slate-400"><span>50 %</span><span>75 %</span><span>100 %</span></div>
                <div class="flex flex-wrap gap-2 mt-2">
                  <button
                    v-for="p in [50, 75, 100]"
                    :key="p"
                    type="button"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border"
                    :class="decision.percentage === p ? 'bg-[#0F172A] text-white border-[#0F172A]' : 'bg-white text-slate-600 border-slate-200'"
                    @click="decision.percentage = p"
                  >
                    {{ p }} %
                  </button>
                </div>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 p-4 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Paiement client</span><span class="font-semibold">{{ formatMoney(r.payment?.amount) }} MAD</span></div>
                <div class="flex justify-between mt-1"><span class="text-slate-500">Remboursé au client</span><span class="font-extrabold text-[#0F172A]" data-testid="decision-amount">{{ formatMoney(amountFor(r, decision.percentage)) }} MAD</span></div>
                <div class="flex justify-between mt-1 border-t border-slate-100 pt-1"><span class="text-slate-500">Conservé</span><span class="font-semibold text-emerald-700">{{ formatMoney(Number(r.payment?.amount || 0) - amountFor(r, decision.percentage)) }} MAD</span></div>
              </div>
            </div>

            <div>
              <label :for="`reason-${r.id}`" class="block text-xs font-semibold text-slate-700 mb-1">Message pour le client (optionnel)</label>
              <textarea
                :id="`reason-${r.id}`"
                v-model="decision.reason"
                rows="2"
                maxlength="2000"
                placeholder="Ex. : geste commercial, véhicule reloué sur la période"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                data-testid="decision-reason"
              ></textarea>
            </div>

            <div class="flex gap-2">
              <button type="button" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600" @click="closeDecision">Annuler</button>
              <button
                type="submit"
                class="px-4 py-1.5 rounded-lg bg-[#0F172A] text-white text-xs font-semibold disabled:opacity-50"
                :disabled="busy === r.id"
                data-testid="decision-submit"
              >
                {{ busy === r.id ? 'Enregistrement…' : `Rembourser ${decision.percentage} %` }}
              </button>
            </div>
          </form>
        </li>
      </ul>

      <p class="text-xs text-slate-400">
        Pour refuser une réservation confirmée (remboursement 100 %), passez par
        <RouterLink to="/agency/reservations" class="font-semibold text-blue-600 hover:underline">Réservations</RouterLink>.
      </p>
    </main>
  </AgencyLayout>
</template>
