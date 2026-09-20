<template>
  <AgencyLayout :agency="agency" :total-cars="totalCars">
      <main class="space-y-8">
        
        <!-- EN-TÊTE BIENVENUE -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="font-bricolage text-3xl font-extrabold text-[#0F172A] tracking-tight">
              Bonjour, {{ agency.name || 'Mon Agence' }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
              Revenus, réservations, occupation et flotte : vos chiffres en temps réel.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row gap-2">
            <RouterLink
              to="/agency/reservations"
              class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm border border-slate-200 bg-white text-[#0F172A] hover:bg-slate-50 transition-all"
            >
              Réservations
            </RouterLink>
            <RouterLink 
              to="/agency/cars/new" 
              class="btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold shadow-sm hover:opacity-90 transition-all text-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Ajouter un véhicule
            </RouterLink>
          </div>
        </div>

        <!-- ÉTAT DE CHARGEMENT -->
        <div v-if="loading" class="bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-500 shadow-sm">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-slate-800 mb-3"></div>
          <p class="text-sm font-medium">Chargement des données du tableau de bord...</p>
        </div>

        <template v-else>
          <div
            v-if="statsError"
            class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm"
            data-testid="stats-error"
          >
            {{ statsError }}
          </div>

          <!-- KPI : chiffres réels GET /agency/stats -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" data-testid="kpis">

            <!-- KPI 1 : Revenus nets de l'année -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Revenus nets {{ year }}</div>
              <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-2" data-testid="kpi-revenue">
                {{ formatMoney(stats.total_revenue) }} <span class="text-sm font-normal text-slate-400">MAD</span>
              </div>
              <div class="text-xs text-slate-400 mt-2 font-medium">
                Votre part après commission GlobalRental ({{ commissionRate }} %)
              </div>
            </div>

            <!-- KPI 2 : Réservations de l'année -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Réservations {{ year }}</div>
              <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-2" data-testid="kpi-reservations">
                {{ yearReservations }}
              </div>
              <div class="text-xs text-slate-400 mt-2 font-medium">
                {{ statusCount('confirmed') }} à venir · {{ statusCount('picked_up') }} en cours
              </div>
            </div>

            <!-- KPI 3 : Taux d'occupation du mois -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Occupation ce mois</div>
              <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-2" data-testid="kpi-occupancy">
                {{ Number(stats.occupancy_rate || 0).toFixed(0) }}<span class="text-sm font-normal text-slate-400">%</span>
              </div>
              <div class="mt-2 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full bg-[#0F172A]" :style="{ width: `${Math.min(100, Number(stats.occupancy_rate || 0))}%` }"></div>
              </div>
              <div class="text-xs text-slate-400 mt-2 font-medium">
                Jours loués / jours disponibles · {{ totalCars }} véhicule(s)
              </div>
            </div>

            <!-- KPI 4 : Note moyenne -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Note moyenne</div>
              <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-2" data-testid="kpi-rating">
                <template v-if="ratingValue !== null">
                  {{ ratingValue.toFixed(1) }} <span class="text-amber-500">★</span>
                </template>
                <template v-else>—</template>
              </div>
              <div class="text-xs text-slate-400 mt-2 font-medium">
                {{ reviewsCount ? `Sur ${reviewsCount} avis clients` : 'Aucun avis pour le moment' }}
              </div>
            </div>
          </div>

          <!-- REVENUS MENSUELS + FINANCES -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6" data-testid="monthly-chart">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                <div>
                  <h2 class="font-bricolage text-xl font-bold text-[#0F172A]">Revenus mensuels</h2>
                  <p class="text-xs text-slate-500 mt-0.5">Part agence nette, remboursements déduits.</p>
                </div>
                <div class="flex items-center gap-2">
                  <label for="stats-year" class="text-xs font-semibold text-slate-500">Année</label>
                  <select
                    id="stats-year"
                    v-model.number="year"
                    class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm bg-white"
                    data-testid="year-select"
                    @change="loadStats"
                  >
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                  </select>
                </div>
              </div>

              <div v-if="statsLoading" class="h-48 flex items-center justify-center text-sm text-slate-400">Chargement…</div>

              <div v-else-if="!hasRevenue" class="h-48 flex flex-col items-center justify-center text-center text-sm text-slate-400">
                <p class="font-semibold text-slate-500">Aucun revenu encaissé en {{ year }}</p>
                <p class="text-xs mt-1">Les paiements des clients apparaîtront ici mois par mois.</p>
              </div>

              <div v-else>
                <div class="flex items-end gap-1.5 sm:gap-2 h-48">
                  <div
                    v-for="m in monthly"
                    :key="m.month"
                    class="flex-1 flex flex-col items-center justify-end h-full group"
                    :title="`${monthLabel(m.month, true)} ${year} : ${formatMoney(m.revenue)} MAD · ${m.reservations} réservation(s)`"
                  >
                    <span class="text-[10px] font-bold text-slate-500 mb-1 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                      {{ formatMoney(m.revenue) }}
                    </span>
                    <div
                      class="w-full rounded-t-md transition-colors"
                      :class="m.month === currentMonth && year === currentYear ? 'bg-[#0F172A]' : 'bg-slate-300 group-hover:bg-slate-400'"
                      :style="{ height: `${barHeight(m.revenue)}%` }"
                    ></div>
                  </div>
                </div>
                <div class="flex gap-1.5 sm:gap-2 mt-2">
                  <div v-for="m in monthly" :key="m.month" class="flex-1 text-center text-[10px] font-semibold text-slate-400 uppercase">
                    {{ monthLabel(m.month) }}
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4" data-testid="finances">
              <div>
                <h2 class="font-bricolage text-xl font-bold text-[#0F172A]">Finances {{ year }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">Estimation à partir des paiements encaissés.</p>
              </div>
              <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                  <dt class="text-slate-500">Chiffre d’affaires brut</dt>
                  <dd class="font-bold text-[#0F172A]">{{ formatMoney(grossRevenue) }} MAD</dd>
                </div>
                <div class="flex items-center justify-between">
                  <dt class="text-slate-500">Commission GlobalRental ({{ commissionRate }} %)</dt>
                  <dd class="font-bold text-rose-600">− {{ formatMoney(commissionAmount) }} MAD</dd>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                  <dt class="font-semibold text-[#0F172A]">Revenus nets</dt>
                  <dd class="font-extrabold text-[#0F172A]">{{ formatMoney(stats.total_revenue) }} MAD</dd>
                </div>
                <div class="flex items-center justify-between">
                  <dt class="text-slate-500">Meilleur mois</dt>
                  <dd class="font-semibold text-[#0F172A]">
                    {{ bestMonth ? `${monthLabel(bestMonth.month, true)} · ${formatMoney(bestMonth.revenue)} MAD` : '—' }}
                  </dd>
                </div>
                <div class="flex items-center justify-between">
                  <dt class="text-slate-500">Panier moyen / réservation</dt>
                  <dd class="font-semibold text-[#0F172A]">{{ yearReservations ? formatMoney(stats.total_revenue / yearReservations) : '—' }} MAD</dd>
                </div>
              </dl>
            </div>
          </div>

          <!-- RÉSERVATIONS PAR STATUT + FLOTTE -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6" data-testid="status-breakdown">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h2 class="font-bricolage text-xl font-bold text-[#0F172A]">Réservations par statut</h2>
                  <p class="text-xs text-slate-500 mt-0.5">Toutes vos réservations depuis l’ouverture.</p>
                </div>
                <RouterLink to="/agency/reservations" class="text-xs font-bold text-blue-600 hover:underline">Voir tout →</RouterLink>
              </div>
              <div v-if="totalReservations === 0" class="p-6 text-center text-slate-400 text-sm bg-slate-50 rounded-xl border border-dashed border-slate-200">
                Aucune réservation pour le moment.
              </div>
              <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <RouterLink
                  v-for="s in STATUS_ORDER"
                  :key="s.key"
                  :to="{ path: '/agency/reservations', query: { status: s.key } }"
                  class="p-3 rounded-xl border border-slate-200 hover:border-slate-300 transition-colors"
                >
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="s.dot"></span>
                    <span class="text-xs font-semibold text-slate-500">{{ s.label }}</span>
                  </div>
                  <div class="font-bricolage text-2xl font-extrabold text-[#0F172A] mt-1">{{ statusCount(s.key) }}</div>
                  <div class="text-[11px] text-slate-400">{{ totalReservations ? Math.round((statusCount(s.key) / totalReservations) * 100) : 0 }} %</div>
                </RouterLink>
              </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6" data-testid="fleet-summary">
              <h2 class="font-bricolage text-xl font-bold text-[#0F172A]">Ma flotte</h2>
              <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-2">
                {{ totalCars }} <span class="text-sm font-normal text-slate-400">véhicule(s)</span>
              </div>
              <dl class="mt-4 space-y-2 text-sm">
                <div class="flex items-center justify-between">
                  <dt class="text-slate-500">Disponibles</dt>
                  <dd class="font-bold text-emerald-700">{{ availableCars }}</dd>
                </div>
                <div class="flex items-center justify-between">
                  <dt class="text-slate-500">En maintenance</dt>
                  <dd class="font-bold text-amber-700">{{ maintenanceCars }}</dd>
                </div>
                <div class="flex items-center justify-between">
                  <dt class="text-slate-500">Taux de disponibilité</dt>
                  <dd class="font-bold text-[#0F172A]">{{ availabilityRate }} %</dd>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-2">
                  <dt class="text-slate-500">Tarif moyen / jour</dt>
                  <dd class="font-bold text-[#0F172A]">{{ avgDailyPrice }} MAD</dd>
                </div>
              </dl>
            </div>
          </div>

          <!-- APERÇU DE LA FLOTTE RÉCENTE (Données réelles de la DB) -->
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h2 class="font-bricolage text-xl font-bold text-[#0F172A]">Ma flotte récente</h2>
                <p class="text-xs text-slate-500 mt-0.5">Derniers véhicules enregistrés dans votre agence.</p>
              </div>
              <RouterLink to="/agency/cars" class="text-xs font-bold text-blue-600 hover:underline">
                Voir toute la flotte ({{ totalCars }}) →
              </RouterLink>
            </div>

            <!-- ÉTAT VIDE (0 VOITURE) -->
            <div v-if="cars.length === 0" class="p-12 text-center text-slate-500">
              <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3m-7 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm9 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                </svg>
              </div>
              <h3 class="font-bricolage text-lg font-bold text-[#0F172A]">Aucun véhicule enregistré</h3>
              <p class="text-sm text-slate-500 max-w-md mx-auto mt-1 mb-6">
                Votre agence n'a pas encore ajouté de véhicule à son catalogue.
              </p>
              <RouterLink to="/agency/cars/new" class="btn-primary px-4 py-2 rounded-lg text-sm font-medium">
                + Ajouter votre première voiture
              </RouterLink>
            </div>

            <!-- TABLEAU DES VOITURES RÉELLES -->
            <div v-else class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-sm">
                <thead>
                  <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6">Véhicule</th>
                    <th class="py-3 px-6">Immatriculation</th>
                    <th class="py-3 px-6">Statut</th>
                    <th class="py-3 px-6">Prix / jour</th>
                    <th class="py-3 px-6 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="car in cars.slice(0, 5)" :key="car.id" class="hover:bg-slate-50/60 transition-colors">
                    
                    <td class="py-3.5 px-6">
                      <div class="font-bold text-[#0F172A]">{{ car.brand }} {{ car.model }}</div>
                      <div class="text-xs text-slate-400">{{ car.year || 'Année N/A' }} • {{ car.type || 'Standard' }}</div>
                    </td>

                    <td class="py-3.5 px-6 font-mono text-xs text-slate-600">
                      {{ car.plate_number || 'Non renseignée' }}
                    </td>

                    <td class="py-3.5 px-6">
                      <span 
                        v-if="car.status === 'available'" 
                        class="inline-flex px-2 py-0.5 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700"
                      >
                        Disponible
                      </span>
                      <span 
                        v-else-if="car.status === 'maintenance'" 
                        class="inline-flex px-2 py-0.5 text-[11px] font-bold rounded-full bg-amber-50 text-amber-700"
                      >
                        En maintenance
                      </span>
                      <span 
                        v-else 
                        class="inline-flex px-2 py-0.5 text-[11px] font-bold rounded-full bg-slate-100 text-slate-700"
                      >
                        {{ car.status || 'Indisponible' }}
                      </span>
                    </td>

                    <td class="py-3.5 px-6 font-bold text-[#0F172A]">
                      {{ car.daily_price }} MAD
                    </td>

                    <td class="py-3.5 px-6 text-right">
                      <RouterLink :to="`/agency/cars/${car.id}/edit`" class="text-xs font-bold text-blue-600 hover:underline">
                        Modifier
                      </RouterLink>
                    </td>

                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- POINTS DE RETRAIT & AGENCES DE SERVICE (Données réelles de la DB) -->
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="font-bricolage text-xl font-bold text-[#0F172A]">Points de retrait & service</h2>
                <p class="text-xs text-slate-500 mt-0.5">Emplacements où vos clients récupèrent leurs véhicules.</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">
                  {{ points.length }} point(s) enregistré(s)
                </span>
                <RouterLink
                  to="/agency/locations"
                  class="text-xs font-bold text-blue-600 hover:underline"
                >
                  Gérer
                </RouterLink>
              </div>
            </div>

            <div v-if="points.length === 0" class="p-6 text-center text-slate-400 text-sm bg-slate-50 rounded-xl border border-dashed border-slate-200 space-y-3">
              <p>Aucun point de retrait configuré. Sans point, le client ne peut pas réserver.</p>
              <RouterLink
                to="/agency/locations"
                class="inline-flex px-4 py-2 rounded-xl bg-[#0F172A] text-white text-xs font-semibold"
              >
                Ajouter un point
              </RouterLink>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div 
                v-for="point in points" 
                :key="point.id" 
                class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between"
              >
                <div>
                  <div class="flex items-center justify-between">
                    <h4 class="font-bold text-sm text-[#0F172A]">{{ point.name }}</h4>
                    <span 
                      class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                      :class="point.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                    >
                      {{ point.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-500 mt-1">📍 {{ point.address }}</p>
                </div>
                <div class="text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-100 font-mono">
                  {{ point.phone || agency.phone || 'Standard agence' }}
                </div>
              </div>
            </div>
          </div>

        </template>

      </main>
  </AgencyLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AgencyLayout from '@/components/AgencyLayout.vue'
import api from '@/services/api'
import carsService from '@/services/cars'
import agencyService from '@/services/agency'

const loading = ref(true)
const agency = ref({})
const cars = ref([])
const points = ref([])

// ---- Statistiques réelles (GET /agency/stats) ----
const now = new Date()
const currentYear = now.getFullYear()
const currentMonth = now.getMonth() + 1
const year = ref(currentYear)
const years = [currentYear, currentYear - 1, currentYear - 2]

const EMPTY_STATS = {
  total_revenue: 0,
  monthly: [],
  reservations_by_status: {},
  occupancy_rate: 0,
  avg_rating: null,
  total_reviews: 0,
}
const stats = ref({ ...EMPTY_STATS })
const statsLoading = ref(false)
const statsError = ref('')

const STATUS_ORDER = [
  { key: 'pending', label: 'Attente paiement', dot: 'bg-amber-400' },
  { key: 'confirmed', label: 'Confirmées', dot: 'bg-emerald-500' },
  { key: 'picked_up', label: 'En cours', dot: 'bg-blue-500' },
  { key: 'completed', label: 'Terminées', dot: 'bg-slate-400' },
  { key: 'cancelled', label: 'Annulées', dot: 'bg-rose-400' },
  { key: 'rejected', label: 'Refusées', dot: 'bg-rose-600' },
  { key: 'disputed', label: 'Litiges', dot: 'bg-orange-500' },
]

const MONTHS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.']
const MONTHS_LONG = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']

function monthLabel(month, long = false) {
  return (long ? MONTHS_LONG : MONTHS)[month - 1] || ''
}

function formatMoney(value) {
  return Math.round(Number(value) || 0).toLocaleString('fr-MA')
}

function statusCount(key) {
  return Number(stats.value.reservations_by_status?.[key] || 0)
}

// 12 entries, always in month order even if the API skips a month
const monthly = computed(() => {
  const byMonth = new Map((stats.value.monthly || []).map((m) => [Number(m.month), m]))
  return Array.from({ length: 12 }, (_, i) => {
    const m = byMonth.get(i + 1)
    return { month: i + 1, revenue: Number(m?.revenue || 0), reservations: Number(m?.reservations || 0) }
  })
})

const maxRevenue = computed(() => Math.max(0, ...monthly.value.map((m) => m.revenue)))
const hasRevenue = computed(() => maxRevenue.value > 0)
const bestMonth = computed(() => (hasRevenue.value ? monthly.value.reduce((a, b) => (b.revenue > a.revenue ? b : a)) : null))
const yearReservations = computed(() => monthly.value.reduce((acc, m) => acc + m.reservations, 0))
const totalReservations = computed(() => Object.values(stats.value.reservations_by_status || {}).reduce((a, b) => a + Number(b || 0), 0))

function barHeight(revenue) {
  if (!maxRevenue.value) return 0
  return Math.max(revenue > 0 ? 3 : 0, Math.round((revenue / maxRevenue.value) * 100))
}

// Commission: stored on the agency (defaults to the platform's 15 %)
const commissionRate = computed(() => Number(agency.value?.commission_rate ?? 15))
// Net = gross × (1 − rate) ⇒ gross = net / (1 − rate)
const grossRevenue = computed(() => {
  const net = Number(stats.value.total_revenue || 0)
  const keep = 1 - commissionRate.value / 100
  return keep > 0 ? net / keep : net
})
const commissionAmount = computed(() => grossRevenue.value - Number(stats.value.total_revenue || 0))

const ratingValue = computed(() => {
  const v = stats.value.avg_rating ?? agency.value?.avg_rating
  return v === null || v === undefined || v === '' ? null : Number(v)
})
const reviewsCount = computed(() => Number(stats.value.total_reviews ?? agency.value?.total_reviews ?? 0))

async function loadStats() {
  statsLoading.value = true
  statsError.value = ''
  try {
    const res = await agencyService.getStats({ year: year.value })
    const data = res?.data && !res?.monthly ? res.data : res
    stats.value = { ...EMPTY_STATS, ...(data || {}) }
  } catch (e) {
    statsError.value = e?.message || 'Statistiques indisponibles pour le moment.'
    stats.value = { ...EMPTY_STATS }
  } finally {
    statsLoading.value = false
  }
}

// ---- Flotte (GET /agency/cars) ----
const totalCars = computed(() => cars.value.length)
const availableCars = computed(() => cars.value.filter(c => c.status === 'available').length)
const maintenanceCars = computed(() => cars.value.filter(c => c.status === 'maintenance').length)

const availabilityRate = computed(() => {
  if (totalCars.value === 0) return 0
  return Math.round((availableCars.value / totalCars.value) * 100)
})

const avgDailyPrice = computed(() => {
  if (totalCars.value === 0) return 0
  const sum = cars.value.reduce((acc, c) => acc + Number(c.daily_price || 0), 0)
  return Math.round(sum / totalCars.value)
})

onMounted(async () => {
  loading.value = true
  try {
    // 1. Profil réel de l'agence
    const resProfile = await api.get('/agency/profile')
    agency.value = resProfile.data?.agency || resProfile.data || resProfile.agency || resProfile

    // 2. Flotte réelle + statistiques (en parallèle)
    const [resCars] = await Promise.all([carsService.getCars(), loadStats()])
    cars.value = resCars.cars || resCars.data || (Array.isArray(resCars) ? resCars : [])

    // 3. Points de retrait réels de l'agence
    try {
      const resPoints = await api.get('/agency/points')
      points.value = resPoints.data?.points || resPoints.points || (Array.isArray(resPoints) ? resPoints : [])
    } catch (errPoints) {
      console.warn('Points de retrait non disponibles ou non configurés:', errPoints)
    }
  } catch (e) {
    console.error('Erreur lors du chargement du tableau de bord:', e)
  } finally {
    loading.value = false
  }
})
</script>