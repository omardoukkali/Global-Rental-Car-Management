<template>
  <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Signed in: confirm sign-out -->
      <section
        v-if="auth.isAuthenticated"
        data-testid="logout-card"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center fade-up"
      >
        <div
          class="mx-auto w-16 h-16 rounded-full bg-[#0F172A] text-white font-bricolage font-extrabold text-xl flex items-center justify-center"
          aria-hidden="true"
        >
          {{ initials }}
        </div>

        <h1 class="font-bricolage text-2xl text-[#0F172A] mt-5">{{ fullName }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ auth.user?.email }}</p>

        <div class="mt-8 pt-6 border-t border-slate-100">
          <h2 class="font-bricolage text-lg text-[#0F172A]">Se déconnecter ?</h2>
          <p class="text-sm text-slate-500 mt-1">
            Vous devrez vous reconnecter pour accéder à votre espace.
          </p>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
          <button
            type="button"
            class="btn-outline"
            :disabled="loading"
            @click="goBack"
          >
            Annuler
          </button>
          <button
            type="button"
            class="btn-primary"
            data-testid="logout-confirm"
            :disabled="loading"
            @click="handleLogout"
          >
            <span v-if="loading">Déconnexion…</span>
            <span v-else>Se déconnecter</span>
          </button>
        </div>

        <p
          v-if="error"
          class="mt-4 p-3 rounded-lg text-sm"
          style="background: rgba(239,68,68,0.08); color: #B91C1C;"
        >
          {{ error }}
        </p>
      </section>

      <!-- Signed out -->
      <section
        v-else
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center fade-up"
      >
        <h1 class="font-bricolage text-2xl text-[#0F172A]">Vous êtes déconnecté</h1>
        <p class="text-sm text-slate-500 mt-2">
          Connectez-vous ou créez un compte pour réserver un véhicule.
        </p>
        <div class="mt-6 flex flex-col gap-3">
          <RouterLink to="/login" class="btn-primary">Se connecter</RouterLink>
          <RouterLink to="/register" class="btn-outline">Créer un compte</RouterLink>
          <RouterLink to="/" class="text-sm font-semibold text-slate-600 hover:text-[#0F172A] mt-1">
            Retour à l’accueil
          </RouterLink>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const loading = ref(false)
const error = ref('')

const fullName = computed(() => {
  const user = auth.user || {}
  const name = [user.first_name, user.last_name].filter(Boolean).join(' ').trim()
  return name || 'Mon compte'
})

const initials = computed(() => {
  const user = auth.user || {}
  const letters = [user.first_name, user.last_name]
    .filter(Boolean)
    .map((part) => part.trim().charAt(0).toUpperCase())
    .join('')
  return letters || (user.email || '?').charAt(0).toUpperCase()
})

function homeForRole(user) {
  const role = user?.role
  if (role === 'agency') return '/agency/dashboard'
  if (role === 'admin') return '/admin/dashboard'
  return '/'
}

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push(homeForRole(auth.user))
}

async function handleLogout() {
  loading.value = true
  error.value = ''
  try {
    await auth.logout()
    router.push('/')
  } catch (e) {
    error.value = e?.message || 'Erreur lors de la déconnexion.'
  } finally {
    loading.value = false
  }
}
</script>
