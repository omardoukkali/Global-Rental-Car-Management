<template>
  <div class="min-h-screen flex flex-col" style="background: var(--bg);">

    <header class="p-6 flex justify-between items-center w-full bg-white border-b" style="border-color: var(--border);">
      <RouterLink to="/" class="gr-logo" style="color: var(--ink);">
        <span class="gr-logo-dot" style="background: var(--ink);"></span>GlobalRental
      </RouterLink>
      <div class="flex items-center gap-4">
        <RouterLink to="/agency/settings" class="btn-outline text-sm py-2 px-4">
          Modifier le profil
        </RouterLink>
        <RouterLink to="/" class="text-sm font-semibold flex items-center gap-2" style="color: var(--ink-muted);">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Accueil
        </RouterLink>
      </div>
    </header>

    <main class="flex-1 max-w-4xl w-full mx-auto p-6 lg:p-12">
      <div v-if="loading" class="text-center py-20">
        <p class="text-lg font-medium" style="color: var(--ink-muted);">Chargement du profil...</p>
      </div>

      <div v-else-if="error" class="p-4 rounded-xl text-sm mb-6" style="background: rgba(239,68,68,0.08); color: #EF4444; border: 1px solid rgba(239,68,68,0.25);">
        {{ error }}
      </div>

      <div v-else class="space-y-6 fade-up fade-up-1">

        <div class="bg-white p-8 rounded-2xl border flex flex-col md:flex-row items-start md:items-center justify-between gap-6" style="border-color: var(--border-soft); box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
          <div class="flex items-center gap-5">
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-2xl font-extrabold text-white" style="background: var(--ink);">
              {{ agencyInitials }}
            </div>
            <div>
              <div class="flex items-center gap-3">
                <h1 class="font-bricolage text-2xl md:text-3xl text-gray-900">{{ agency.name || 'Mon Agence' }}</h1>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full" style="background: #ECFDF5; color: #047857;">
                  ✓ Agence Certifiée
                </span>
              </div>
              <p class="text-sm mt-1" style="color: var(--ink-muted);">
                📍 {{ agency.address || 'Adresse non renseignée' }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-6 border-t md:border-t-0 md:border-l pt-4 md:pt-0 md:pl-6 w-full md:w-auto justify-around md:justify-start" style="border-color: var(--border-soft);">
            <div class="text-center">
              <div class="font-bricolage text-2xl">{{ agency.avg_rating || '5.0' }} ★</div>
              <div class="text-xs font-medium" style="color: var(--ink-muted);">Note moyenne</div>
            </div>
            <div class="text-center">
              <div class="font-bricolage text-2xl">{{ agency.total_reviews || '0' }}</div>
              <div class="text-xs font-medium" style="color: var(--ink-muted);">Avis clients</div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

          <div class="bg-white p-6 rounded-2xl border" style="border-color: var(--border-soft);">
            <h2 class="font-bricolage text-lg mb-4">Coordonnées</h2>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between py-2 border-b" style="border-color: var(--border-soft);">
                <span style="color: var(--ink-muted);">E-mail :</span>
                <span class="font-medium">{{ agency.email || '-' }}</span>
              </div>
              <div class="flex justify-between py-2 border-b" style="border-color: var(--border-soft);">
                <span style="color: var(--ink-muted);">Téléphone :</span>
                <span class="font-medium">{{ agency.phone || '-' }}</span>
              </div>
              <div class="flex justify-between py-2">
                <span style="color: var(--ink-muted);">Adresse :</span>
                <span class="font-medium">{{ agency.address || '-' }}</span>
              </div>
            </div>
          </div>

          <div class="bg-white p-6 rounded-2xl border flex flex-col justify-between" style="border-color: var(--border-soft);">
            <div>
              <h2 class="font-bricolage text-lg mb-4">Statut du compte</h2>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between py-2 border-b" style="border-color: var(--border-soft);">
                  <span style="color: var(--ink-muted);">Statut :</span>
                  <span class="font-bold uppercase text-xs px-2 py-0.5 rounded" style="background: #ECFDF5; color: #047857;">
                    {{ agency.status || 'Actif' }}
                  </span>
                </div>
                <div class="flex justify-between py-2">
                  <span style="color: var(--ink-muted);">Taux de commission :</span>
                  <span class="font-medium">{{ agency.commission_rate ? agency.commission_rate + '%' : '15%' }}</span>
                </div>
              </div>
            </div>

            <div class="mt-6 pt-4 border-t" style="border-color: var(--border-soft);">
              <RouterLink to="/agency/settings" class="btn-primary text-center">
                Mettre à jour les informations
              </RouterLink>
            </div>
          </div>

        </div>

      </div>
    </main>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import agencyService from '@/services/agency'

const agency = ref({})
const loading = ref(true)
const error = ref('')

const agencyInitials = computed(() => {
  if (!agency.value.name) return 'AG'
  return agency.value.name
    .split(' ')
    .map(word => word[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
})

onMounted(async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await agencyService.getProfile()
    agency.value = response.agency || response
  } catch (err) {
    error.value = err.message || 'Impossible de charger le profil de l’agence.'
  } finally {
    loading.value = false
  }
})
</script>
