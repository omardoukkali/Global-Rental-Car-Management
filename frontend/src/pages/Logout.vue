<template>
  <div class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md text-center">
      <RouterLink to="/" class="gr-logo justify-center mb-8" style="color: var(--ink);">
        <span class="gr-logo-dot" style="background: var(--ink);"></span>GlobalRental
      </RouterLink>

      <!-- Si connecté -->
      <div v-if="auth.isAuthenticated">
        <h1 class="font-bricolage text-3xl mb-2">Bonjour {{ auth.user?.first_name }} 👋</h1>
        <p class="mb-2" style="color: var(--ink-muted);">{{ auth.user?.email }}</p>
        <p class="mb-8 text-sm inline-block px-3 py-1 rounded-full" style="background: var(--accent-subtle); color: var(--ink-secondary);">
          Rôle : {{ auth.user?.role }}
        </p>

        <button @click="handleLogout" class="btn-primary" :disabled="loading">
          <span v-if="loading">Déconnexion…</span>
          <span v-else>Se déconnecter</span>
        </button>

        <div v-if="error" class="mt-4 p-3 rounded-lg text-sm" style="background: rgba(239,68,68,0.08); color: #EF4444;">{{ error }}</div>
      </div>

      <!-- Si non connecté -->
      <div v-else>
        <h1 class="font-bricolage text-3xl mb-2">Bienvenue</h1>
        <p class="mb-8" style="color: var(--ink-muted);">Connectez-vous ou créez un compte pour commencer.</p>
        <div class="flex flex-col gap-3">
          <RouterLink to="/login" class="btn-primary">Se connecter</RouterLink>
          <RouterLink to="/register" class="btn-outline">Créer un compte</RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const loading = ref(false)
const error = ref('')

async function handleLogout() {
  loading.value = true
  error.value = ''
  try {
    await auth.logout()
    router.push('/login')
  } catch (e) {
    error.value = e.message || 'Erreur lors de la déconnexion.'
  } finally {
    loading.value = false
  }
}
</script>