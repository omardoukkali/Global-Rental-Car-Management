<template>
  <div class="min-h-[calc(100vh-4rem)] flex flex-col md:flex-row">
    <div class="hidden md:flex w-[45%] lg:w-1/2 left-panel-bg relative text-white flex-col justify-end p-10 lg:p-14">
      <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>
      <div class="relative z-10 fade-up">
        <h1 class="font-bricolage text-4xl lg:text-5xl leading-tight mb-8">
          Choisissez un nouveau mot de passe.
        </h1>
        <p class="text-lg opacity-90">8 caractères minimum, avec au moins une lettre et un chiffre.</p>
      </div>
    </div>

    <div class="flex-1 flex flex-col min-h-[calc(100vh-4rem)] bg-white relative">
      <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-[440px] fade-up fade-up-1">
          <div class="mb-8">
            <h2 class="font-bricolage text-3xl mb-2">Réinitialiser le mot de passe</h2>
            <p style="color: var(--ink-muted);">
              Pour <span class="font-semibold" style="color: var(--ink);">{{ form.email || 'votre compte' }}</span>
            </p>
          </div>

          <div
            v-if="!hasToken"
            data-testid="reset-invalid-link"
            class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm space-y-3"
          >
            <p class="font-semibold">Ce lien est incomplet.</p>
            <p>Ouvrez le lien reçu par e-mail, ou demandez un nouveau lien de réinitialisation.</p>
            <RouterLink to="/forgot-password" class="inline-block font-bold underline">Demander un nouveau lien</RouterLink>
          </div>

          <div
            v-else-if="done"
            data-testid="reset-success"
            class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm space-y-3"
          >
            <p class="font-semibold">Mot de passe modifié.</p>
            <p>Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
            <RouterLink to="/login" class="btn-primary inline-flex justify-center mt-2">Se connecter</RouterLink>
          </div>

          <form v-else @submit.prevent="handleSubmit" class="space-y-5" novalidate>
            <div>
              <label class="form-label" for="reset-email">Adresse e-mail</label>
              <input
                id="reset-email"
                v-model="form.email"
                type="email"
                class="form-input"
                :class="{ 'is-invalid': errors.email }"
                autocomplete="email"
                required
              />
              <p v-if="errors.email" class="text-xs mt-1" style="color: #EF4444;">{{ errors.email[0] }}</p>
            </div>

            <div>
              <label class="form-label" for="reset-password">Nouveau mot de passe</label>
              <input
                id="reset-password"
                v-model="form.password"
                type="password"
                class="form-input"
                :class="{ 'is-invalid': errors.password }"
                placeholder="••••••••"
                autocomplete="new-password"
                required
              />
              <p v-if="errors.password" class="text-xs mt-1" style="color: #EF4444;">{{ errors.password[0] }}</p>
              <p v-else class="text-xs mt-1" style="color: var(--ink-muted);">Minimum 8 caractères, une lettre et un chiffre.</p>
            </div>

            <div>
              <label class="form-label" for="reset-password-confirm">Confirmer le mot de passe</label>
              <input
                id="reset-password-confirm"
                v-model="form.password_confirmation"
                type="password"
                class="form-input"
                :class="{ 'is-invalid': mismatch }"
                placeholder="••••••••"
                autocomplete="new-password"
                required
              />
              <p v-if="mismatch" class="text-xs mt-1" style="color: #EF4444;">Les deux mots de passe ne correspondent pas.</p>
            </div>

            <div
              v-if="globalError"
              data-testid="reset-error"
              class="p-3 rounded-lg text-sm"
              style="background: rgba(239,68,68,0.08); color: #EF4444; border: 1px solid rgba(239,68,68,0.25);"
            >
              {{ globalError }}
              <RouterLink v-if="expired" to="/forgot-password" class="block font-bold underline mt-1">
                Demander un nouveau lien
              </RouterLink>
            </div>

            <button type="submit" class="btn-primary mt-6" :disabled="loading || !canSubmit">
              <span v-if="loading">Enregistrement…</span>
              <span v-else>Enregistrer le mot de passe</span>
            </button>
          </form>

          <p class="text-center mt-8 text-sm" style="color: var(--ink-secondary);">
            <RouterLink to="/login" class="font-bold hover:underline" style="color: var(--ink);">&larr; Retour à la connexion</RouterLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()

const token = typeof route.query.token === 'string' ? route.query.token : ''
const hasToken = computed(() => token.length > 0)

const form = reactive({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  password: '',
  password_confirmation: '',
})
const errors = reactive({})
const globalError = ref('')
const expired = ref(false)
const loading = ref(false)
const done = ref(false)

const mismatch = computed(
  () => form.password_confirmation.length > 0 && form.password !== form.password_confirmation
)
const canSubmit = computed(
  () => form.email && form.password.length >= 8 && form.password === form.password_confirmation
)

async function handleSubmit() {
  Object.keys(errors).forEach((k) => delete errors[k])
  globalError.value = ''
  expired.value = false
  loading.value = true
  try {
    await auth.resetPassword({
      token,
      email: form.email.trim(),
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    done.value = true
  } catch (e) {
    if (e.status === 422 && e.errors) {
      Object.assign(errors, e.errors)
    } else if (e.status === 422) {
      expired.value = true
      globalError.value = e.message || 'Ce lien de réinitialisation est invalide ou a expiré.'
    } else if (e.status === 429) {
      globalError.value = 'Trop de tentatives. Réessayez dans une minute.'
    } else {
      globalError.value = e.message || 'Impossible de réinitialiser le mot de passe.'
    }
  } finally {
    loading.value = false
  }
}
</script>
