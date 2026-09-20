<template>
  <div class="min-h-[calc(100vh-4rem)] flex flex-col md:flex-row">
    <div class="hidden md:flex w-[45%] lg:w-1/2 left-panel-bg relative text-white flex-col justify-end p-10 lg:p-14">
      <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>
      <div class="relative z-10 fade-up">
        <h1 class="font-bricolage text-4xl lg:text-5xl leading-tight mb-8">
          Un oubli, ça arrive.
        </h1>
        <p class="text-lg opacity-90">Indiquez votre e-mail, nous vous envoyons un lien pour choisir un nouveau mot de passe.</p>
      </div>
    </div>

    <div class="flex-1 flex flex-col min-h-[calc(100vh-4rem)] bg-white relative">
      <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-[440px] fade-up fade-up-1">
          <RouterLink to="/login" class="text-xs font-bold text-slate-500 hover:text-slate-800 inline-flex items-center gap-1 mb-6">
            ← Retour à la connexion
          </RouterLink>

          <div class="mb-8">
            <h2 class="font-bricolage text-3xl mb-2">Mot de passe oublié</h2>
            <p style="color: var(--ink-muted);">Recevez un lien de réinitialisation valable 60 minutes.</p>
          </div>

          <div
            v-if="sent"
            data-testid="forgot-success"
            class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm space-y-2"
          >
            <p class="font-semibold">E-mail envoyé.</p>
            <p>{{ successMessage }}</p>
            <p class="text-xs text-emerald-700">
              Vous n’avez rien reçu ? Vérifiez vos spams ou
              <button type="button" class="font-bold underline" @click="sent = false">réessayez</button>.
            </p>
          </div>

          <form v-else @submit.prevent="handleSubmit" class="space-y-5" novalidate>
            <div>
              <label class="form-label" for="forgot-email">Adresse e-mail</label>
              <input
                id="forgot-email"
                v-model="email"
                type="email"
                class="form-input"
                :class="{ 'is-invalid': errors.email }"
                placeholder="vous@exemple.com"
                autocomplete="email"
                required
              />
              <p v-if="errors.email" class="text-xs mt-1" style="color: #EF4444;">{{ errors.email[0] }}</p>
            </div>

            <div
              v-if="globalError"
              class="p-3 rounded-lg text-sm"
              style="background: rgba(239,68,68,0.08); color: #EF4444; border: 1px solid rgba(239,68,68,0.25);"
            >
              {{ globalError }}
            </div>

            <button type="submit" class="btn-primary mt-6" :disabled="loading || !email">
              <span v-if="loading">Envoi…</span>
              <span v-else>Envoyer le lien</span>
            </button>
          </form>

          <p class="text-center mt-8 text-sm" style="color: var(--ink-secondary);">
            Pas encore de compte ?
            <RouterLink to="/register" class="font-bold hover:underline" style="color: var(--ink);">S'inscrire &rarr;</RouterLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const email = ref('')
const errors = reactive({})
const globalError = ref('')
const loading = ref(false)
const sent = ref(false)
const successMessage = ref('')

async function handleSubmit() {
  Object.keys(errors).forEach((k) => delete errors[k])
  globalError.value = ''
  loading.value = true
  try {
    await auth.forgotPassword(email.value.trim())
    successMessage.value = 'Si un compte existe pour cet e-mail, un lien de réinitialisation a été envoyé.'
    sent.value = true
  } catch (e) {
    if (e.status === 422 && e.errors) Object.assign(errors, e.errors)
    else if (e.status === 429) globalError.value = 'Trop de tentatives. Réessayez dans une minute.'
    else globalError.value = e.message || 'Impossible d’envoyer le lien.'
  } finally {
    loading.value = false
  }
}
</script>
