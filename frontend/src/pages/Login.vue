<template>
  <div class="min-h-screen flex flex-col md:flex-row">
    <!-- LEFT PANEL -->
    <div class="hidden md:flex w-[45%] lg:w-1/2 left-panel-bg relative text-white flex-col justify-between p-10 lg:p-14">
      <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>
      <div class="relative z-10 flex flex-col h-full justify-between">
        <RouterLink to="/" class="gr-logo text-white hover:opacity-80 transition-opacity">
          <span class="gr-logo-dot bg-white"></span>GlobalRental
        </RouterLink>
        <div class="mt-auto pb-10 fade-up">
          <h1 class="font-bricolage text-4xl lg:text-5xl leading-tight mb-8">
            Bon retour parmi nous.
          </h1>
          <p class="text-lg opacity-90">Connectez-vous pour accéder à votre espace et gérer vos réservations.</p>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="flex-1 flex flex-col min-h-screen bg-white relative">
      <header class="p-6 flex justify-between items-center w-full">
        <div class="md:hidden">
          <RouterLink to="/" class="gr-logo" style="color: var(--ink);">
            <span class="gr-logo-dot" style="background: var(--ink);"></span>GlobalRental
          </RouterLink>
        </div>
        <div class="ml-auto">
          <RouterLink to="/" class="text-sm font-semibold flex items-center gap-2" style="color: var(--ink-muted);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour à l'accueil
          </RouterLink>
        </div>
      </header>

      <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-[440px] fade-up fade-up-1">
          <div class="flex border-b mb-8" style="border-color: var(--border);">
            <button type="button" class="tab-btn active flex-1 text-center">Se connecter</button>
            <RouterLink to="/register" class="tab-btn flex-1 text-center">S'inscrire</RouterLink>
          </div>

          <div class="mb-8">
            <h2 class="font-bricolage text-3xl mb-2">Bon retour parmi nous</h2>
            <p style="color: var(--ink-muted);">Connectez-vous pour accéder à votre espace</p>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-5" novalidate>
            <div>
              <label class="form-label" for="login-email">Adresse e-mail</label>
              <input v-model="form.email" type="email" id="login-email" class="form-input" :class="{ 'is-invalid': errors.email }" placeholder="vous@exemple.com" required />
              <p v-if="errors.email" class="text-xs mt-1" style="color: #EF4444;">{{ errors.email[0] }}</p>
            </div>

            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="form-label mb-0" for="login-password">Mot de passe</label>
                <a href="#" class="text-sm font-medium hover:underline" style="color: var(--ink);" @click.prevent>Mot de passe oublié ?</a>
              </div>
              <input v-model="form.password" type="password" id="login-password" class="form-input" placeholder="••••••••" required />
            </div>

            <div v-if="globalError" class="p-3 rounded-lg text-sm" style="background: rgba(239,68,68,0.08); color: #EF4444; border: 1px solid rgba(239,68,68,0.25);">{{ globalError }}</div>

            <button type="submit" class="btn-primary mt-6" :disabled="loading">
              <span v-if="loading">Chargement…</span>
              <span v-else>Se connecter</span>
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
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const errors = reactive({})
const globalError = ref('')
const loading = ref(false)

async function handleSubmit() {
  Object.keys(errors).forEach(k => delete errors[k])
  globalError.value = ''
  loading.value = true
  try {
    await auth.login(form)
    router.push('/')
  } catch (e) {
    if (e.errors) Object.assign(errors, e.errors)
    else globalError.value = e.message
  } finally {
    loading.value = false
  }
}
</script>