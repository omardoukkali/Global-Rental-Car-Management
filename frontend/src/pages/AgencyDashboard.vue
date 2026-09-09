<template>
  <div class="min-h-screen bg-[#F8FAFC]">
    
    <!-- CONTENEUR GLOBAL AVEC SIDEBAR -->
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row gap-8 py-8 px-4 sm:px-6 lg:px-8">
      
      <!-- 1. SIDEBAR NAVIGATION -->
      <aside class="w-full md:w-64 flex-shrink-0 space-y-6">
        
        <!-- PROFIL AGENCE SIDEBAR -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm text-center">
          <div class="w-16 h-16 bg-[#0F172A] text-white rounded-full flex items-center justify-center font-extrabold text-xl mx-auto mb-3">
            {{ agency.name ? agency.name.substring(0, 2).toUpperCase() : 'AG' }}
          </div>
          <h3 class="font-bricolage font-bold text-lg text-[#0F172A]">{{ agency.name || 'Mon Agence' }}</h3>
          
          <span 
            v-if="agency.status === 'approved'" 
            class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full mt-1"
          >
            ✓ Agence Certifiée
          </span>
          <span 
            v-else 
            class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-full mt-1"
          >
            ⏳ En attente de validation
          </span>

          <p class="text-xs text-slate-500 mt-2 font-medium">
            📍 {{ agency.city?.name || agency.address || 'Localisation non renseignée' }}
          </p>
        </div>

        <!-- LIENS DE NAVIGATION -->
        <nav class="bg-white rounded-2xl p-3 border border-slate-200 shadow-sm space-y-1">
          <RouterLink to="/agency/dashboard" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-bold bg-[#0F172A] text-white">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            Tableau de bord
          </RouterLink>

          <RouterLink to="/agency/cars" class="flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-[#0F172A] transition-colors">
            <span class="flex items-center gap-3">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3m-7 9a3 3 0 100-6 3 3 0 000 6zm9 0a3 3 0 100-6 3 3 0 000 6z"/></svg>
              Ma flotte
            </span>
            <span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full font-bold text-slate-600">{{ totalCars }}</span>
          </RouterLink>

          <RouterLink to="/agency/profile" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-[#0F172A] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Mon agence
          </RouterLink>

          <RouterLink to="/agency/settings" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-[#0F172A] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
            Paramètres
          </RouterLink>

          <div class="border-t border-slate-100 my-2"></div>

          <RouterLink to="/logout" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Se déconnecter
          </RouterLink>
        </nav>
      </aside>

      <!-- 2. CONTENU PRINCIPAL DU DASHBOARD -->
      <main class="flex-1 space-y-8">
        
        <!-- EN-TÊTE BIENVENUE -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="font-bricolage text-3xl font-extrabold text-[#0F172A] tracking-tight">
              Bonjour, {{ agency.name || 'Mon Agence' }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
              Aperçu en temps réel de votre flotte et de vos points de service.
            </p>
          </div>

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

        <!-- ÉTAT DE CHARGEMENT -->
        <div v-if="loading" class="bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-500 shadow-sm">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-slate-800 mb-3"></div>
          <p class="text-sm font-medium">Chargement des données du tableau de bord...</p>
        </div>

        <template v-else>
          <!-- 4 CARTES KPI STATS (100% Réelles depuis la base de données) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- KPI 1 : Total Flotte -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Flotte Totale</div>
              <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-2">
                {{ totalCars }} <span class="text-sm font-normal text-slate-400">véhicules</span>
              </div>
              <span class="inline-flex items-center text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md mt-2">
                {{ availableCars }} dispo • {{ maintenanceCars }} maintenance
              </span>
            </div>

            <!-- KPI 2 : Taux de disponibilité -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Disponibilité</div>
              <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-2">
                {{ availabilityRate }}%
              </div>
              <span class="inline-flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md mt-2">
                {{ availableCars }} véhicule(s) prêt(s) à louer
              </span>
            </div>

            <!-- KPI 3 : Tarif journalier moyen -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tarif Moyen / Jour</div>
              <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-2">
                {{ avgDailyPrice }} <span class="text-sm font-normal text-slate-400">MAD</span>
              </div>
              <div class="text-xs text-slate-400 mt-2 font-medium">Moyenne du catalogue</div>
            </div>

            <!-- KPI 4 : Note et avis réels de l'agence -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Note moyenne</div>
              <div class="font-bricolage text-3xl font-extrabold text-[#0F172A] mt-2">
                {{ Number(agency.avg_rating || 5.0).toFixed(1) }} ★
              </div>
              <div class="text-xs text-slate-400 mt-2 font-medium">
                Sur {{ agency.total_reviews ?? 0 }} avis clients
              </div>
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
              <span class="text-xs font-bold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">
                {{ points.length }} point(s) enregistré(s)
              </span>
            </div>

            <div v-if="points.length === 0" class="p-6 text-center text-slate-400 text-sm bg-slate-50 rounded-xl border border-dashed border-slate-200">
              Aucun point de retrait configuré. Vos véhicules sont gérés à l'adresse principale de votre agence.
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

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import carsService from '@/services/cars'

const loading = ref(true)
const agency = ref({})
const cars = ref([])
const points = ref([])

// Calculs dynamiques 100% issus des données réelles de la base
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

    // 2. Flotte réelle de l'agence
    const resCars = await carsService.getCars()
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