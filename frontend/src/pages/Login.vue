<template>
  <div class="min-h-[calc(100vh-4rem)] flex flex-col md:flex-row">
    <!-- LEFT PANEL -->
    <div class="hidden md:flex w-[45%] lg:w-1/2 left-panel-bg relative text-white flex-col justify-end p-10 lg:p-14">
      <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>
      <div class="relative z-10 fade-up">
        <h1 class="font-bricolage text-4xl lg:text-5xl leading-tight mb-8">
          Bon retour parmi nous.
        </h1>
        <p class="text-lg opacity-90">Connectez-vous pour accéder à votre espace et gérer vos réservations.</p>
      </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="flex-1 flex flex-col min-h-[calc(100vh-4rem)] bg-white relative">
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

          <div
            v-if="sessionNotice"
            data-testid="session-notice"
            class="mb-6 p-3 rounded-lg text-sm"
            :style="sessionNotice.style"
          >
            {{ sessionNotice.text }}
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
                <RouterLink to="/forgot-password" class="text-sm font-medium hover:underline" style="color: var(--ink);">Mot de passe oublié ?</RouterLink>
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
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const errors = reactive({})
const globalError = ref('')
const loading = ref(false)

// Why the user landed here (set by the API client on 401 / 403, see services/api.js)
const WARNING_STYLE = 'background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A;'
const DANGER_STYLE = 'background: rgba(239,68,68,0.08); color: #B91C1C; border: 1px solid rgba(239,68,68,0.25);'

const sessionNotice = computed(() => {
  if (route.query.expired) {
    return { text: 'Votre session a expiré. Veuillez vous reconnecter.', style: WARNING_STYLE }
  }
  if (route.query.suspended) {
    return { text: 'Votre compte a été désactivé. Contactez le support si vous pensez qu’il s’agit d’une erreur.', style: DANGER_STYLE }
  }
  if (route.query.verified === '1') {
    return { text: 'Adresse e-mail confirmée. Vous pouvez vous connecter.', style: 'background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;' }
  }
  if (route.query.verified === '0') {
    return { text: 'Lien de confirmation invalide ou expiré. Inscrivez-vous à nouveau ou demandez un nouvel e-mail.', style: DANGER_STYLE }
  }
  return null
})

function homeForRole(user) {
  const role = user?.role
  if (role === 'agency') return '/agency/dashboard'
  if (role === 'admin') return '/admin/dashboard'
  return '/myreservations'
}

async function handleSubmit() {
  Object.keys(errors).forEach(k => delete errors[k])
  globalError.value = ''
  loading.value = true
  try {
    const user = await auth.login(form)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : ''
    router.push(redirect || homeForRole(user))
  } catch (e) {
    if (e.status === 422 && e.errors) Object.assign(errors, e.errors)
    else if (e.status === 401) globalError.value = 'E-mail ou mot de passe incorrect.'
    else if (e.status === 403) globalError.value = 'Votre compte a été suspendu. Contactez le support.'
    else globalError.value = e.message
  } finally {
    loading.value = false
  }
}
</script>