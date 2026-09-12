<template>
  <div class="min-h-screen flex flex-col" style="background: var(--bg);">

    <header class="p-6 flex justify-between items-center w-full bg-white border-b" style="border-color: var(--border);">
      <RouterLink to="/" class="gr-logo" style="color: var(--ink);">
        <span class="gr-logo-dot" style="background: var(--ink);"></span>GlobalRental
      </RouterLink>
      <RouterLink to="/" class="text-sm font-semibold flex items-center gap-2" style="color: var(--ink-muted);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour au tableau de bord
      </RouterLink>
    </header>

    <main class="flex-1 flex items-start justify-center p-6 lg:p-12">
      <div class="w-full max-w-2xl fade-up fade-up-1">

        <div class="mb-8">
          <h1 class="font-bricolage text-3xl mb-2">Paramètres de l'agence</h1>
          <p style="color: var(--ink-muted);">Mettez à jour les informations publiques de votre agence de location.</p>
        </div>

        <div v-if="successMessage" class="mb-6 p-3 rounded-lg text-sm" style="background: rgba(4,120,87,0.08); color: #047857; border: 1px solid rgba(4,120,87,0.25);">
          {{ successMessage }}
        </div>

        <div v-if="globalError" class="mb-6 p-3 rounded-lg text-sm" style="background: rgba(239,68,68,0.08); color: #EF4444; border: 1px solid rgba(239,68,68,0.25);">
          {{ globalError }}
        </div>

        <form @submit.prevent="handleUpdate" class="space-y-5 bg-white p-8 rounded-2xl border" style="border-color: var(--border-soft); box-shadow: 0 1px 4px rgba(0,0,0,0.05);">

          <div>
            <label class="form-label" for="agency-name">Nom de l'agence</label>
            <input v-model="formData.name" type="text" id="agency-name" class="form-input" placeholder="Ex: Atlas Cars" required />
          </div>

          <div>
            <label class="form-label" for="agency-email">Email de contact</label>
            <input v-model="formData.email" type="email" id="agency-email" class="form-input" placeholder="contact@agence.ma" required />
          </div>

          <div>
            <label class="form-label" for="agency-phone">Numéro de téléphone</label>
            <input v-model="formData.phone" type="text" id="agency-phone" class="form-input" placeholder="+212 6XX XXX XXX" />
          </div>

          <div>
            <label class="form-label" for="agency-address">Adresse</label>
            <input v-model="formData.address" type="text" id="agency-address" class="form-input" placeholder="Ex: Casablanca, Maroc" />
          </div>

          <button type="submit" class="btn-primary mt-4" :disabled="isSubmitting">
            <span v-if="isSubmitting">Enregistrement…</span>
            <span v-else>Enregistrer les modifications</span>
          </button>

        </form>
      </div>
    </main>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import agencyService from '@/services/agency'

const formData = ref({
    name: '',
    email: '',
    phone: '',
    address: ''
})

const isSubmitting = ref(false)
const globalError = ref('')
const successMessage = ref('')

onMounted(async () => {
    try {
        const response = await agencyService.getProfile()
        const data = response.agency || response
        formData.value = {
            name: data.name || '',
            email: data.email || '',
            phone: data.phone || '',
            address: data.address || ''
        }
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error)
    }
})

const handleUpdate = async () => {
    isSubmitting.value = true
    globalError.value = ''
    successMessage.value = ''
    try {
        await agencyService.updateProfile(formData.value)
        successMessage.value = 'Les informations ont été mises à jour avec succès!'
    } catch (error) {
        globalError.value = error.message || 'Une erreur est survenue.'
    } finally {
        isSubmitting.value = false
    }
}
</script>