<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAgencyStore } from '@/stores/agency'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const agencyStore = useAgencyStore()
const auth = useAuthStore()

const refreshing = ref(false)
const refreshedAt = ref(null)

const agency = computed(() => agencyStore.profile || {})
const status = computed(() => agencyStore.status)
const isRejected = computed(() => status.value === 'rejected')

const ownerName = computed(() => {
  const u = auth.user
  if (!u) return ''
  return [u.first_name, u.last_name].filter(Boolean).join(' ')
})

const steps = computed(() => [
  {
    label: 'Compte créé',
    detail: 'Votre compte propriétaire et la fiche agence sont enregistrés.',
    state: 'done',
  },
  {
    label: 'Vérification par GlobalRental',
    detail: isRejected.value
      ? 'Votre demande a été examinée et refusée.'
      : 'Un administrateur vérifie les informations de votre agence.',
    state: isRejected.value ? 'error' : 'current',
  },
  {
    label: 'Accès complet',
    detail: 'Flotte, points de retrait, réservations et statistiques.',
    state: 'todo',
  },
])

const checks = computed(() => [
  { label: 'Nom de l’agence', value: agency.value.name, ok: !!agency.value.name },
  { label: 'Adresse', value: agency.value.address, ok: !!agency.value.address },
  { label: 'Téléphone', value: agency.value.phone, ok: !!agency.value.phone },
  { label: 'E-mail de contact', value: agency.value.email, ok: !!agency.value.email },
])

function formatDate(value) {
  if (!value) return '—'
  const d = new Date(value)
  return Number.isNaN(d.getTime())
    ? '—'
    : d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })
}

async function refresh() {
  if (refreshing.value) return
  refreshing.value = true
  try {
    await agencyStore.fetchProfile({ force: true })
    refreshedAt.value = new Date()
    if (agencyStore.isApproved) {
      router.replace('/agency/dashboard')
    }
  } finally {
    refreshing.value = false
  }
}

onMounted(async () => {
  if (!agencyStore.loaded) await agencyStore.fetchProfile()
  if (agencyStore.isApproved) router.replace('/agency/dashboard')
})
</script>

<template>
  <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-6" data-testid="agency-pending">
    <div v-if="agencyStore.loading && !agencyStore.loaded" class="text-center py-16 text-slate-500">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-slate-800 mb-3"></div>
      <p class="text-sm">Chargement de votre dossier…</p>
    </div>

    <template v-else>
      <section
        class="rounded-3xl p-8 sm:p-10 text-white relative overflow-hidden"
        :class="isRejected ? 'bg-rose-900' : 'bg-[#0F172A]'"
      >
        <div class="absolute -right-10 -top-10 w-56 h-56 rounded-full opacity-10 bg-white"></div>
        <div class="relative space-y-4">
          <span
            class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
            :class="isRejected ? 'bg-rose-500/30 text-rose-100' : 'bg-amber-400/20 text-amber-200'"
          >
            <span class="w-2 h-2 rounded-full" :class="isRejected ? 'bg-rose-300' : 'bg-amber-300 animate-pulse'"></span>
            {{ isRejected ? 'Demande refusée' : 'En attente de validation' }}
          </span>

          <h1 class="font-bricolage text-3xl sm:text-4xl font-extrabold tracking-tight">
            <template v-if="isRejected">Votre agence n’a pas été validée</template>
            <template v-else>Bonjour {{ ownerName || 'et bienvenue' }}, votre agence est en cours de vérification</template>
          </h1>

          <p class="text-slate-200 max-w-2xl">
            <template v-if="isRejected">
              Un administrateur a examiné {{ agency.name || 'votre agence' }} et n’a pas pu l’approuver.
              Contactez l’équipe GlobalRental pour connaître le motif et soumettre une nouvelle demande.
            </template>
            <template v-else>
              Merci d’avoir inscrit <span class="font-bold text-white">{{ agency.name || 'votre agence' }}</span>.
              Notre équipe vérifie vos informations. Vous recevrez l’accès complet dès l’approbation, généralement sous 24 à 48 heures.
            </template>
          </p>

          <div class="flex flex-wrap gap-3 pt-2">
            <button
              type="button"
              class="px-5 py-2.5 rounded-xl bg-white text-[#0F172A] text-sm font-semibold disabled:opacity-60"
              :disabled="refreshing"
              data-testid="pending-refresh"
              @click="refresh"
            >
              {{ refreshing ? 'Vérification…' : 'Vérifier mon statut' }}
            </button>
            <RouterLink
              to="/agency/profile"
              class="px-5 py-2.5 rounded-xl border border-white/30 text-white text-sm font-semibold hover:bg-white/10"
            >
              Compléter ma fiche agence
            </RouterLink>
          </div>
          <p v-if="refreshedAt" class="text-xs text-slate-300">
            Statut vérifié à {{ refreshedAt.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }} :
            toujours {{ isRejected ? 'refusé' : 'en attente' }}.
          </p>
        </div>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <section class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <h2 class="font-bricolage text-lg font-bold text-[#0F172A] mb-5">Où en est ma demande ?</h2>
          <ol class="space-y-5">
            <li v-for="(step, index) in steps" :key="step.label" class="flex gap-4">
              <div class="flex flex-col items-center">
                <span
                  class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border"
                  :class="{
                    'bg-emerald-500 border-emerald-500 text-white': step.state === 'done',
                    'bg-amber-100 border-amber-300 text-amber-800': step.state === 'current',
                    'bg-rose-100 border-rose-300 text-rose-800': step.state === 'error',
                    'bg-slate-50 border-slate-200 text-slate-400': step.state === 'todo',
                  }"
                >
                  <template v-if="step.state === 'done'">✓</template>
                  <template v-else-if="step.state === 'error'">✕</template>
                  <template v-else>{{ index + 1 }}</template>
                </span>
                <span v-if="index < steps.length - 1" class="flex-1 w-px bg-slate-200 mt-2"></span>
              </div>
              <div class="pb-2">
                <p class="font-bold text-sm text-[#0F172A]">{{ step.label }}</p>
                <p class="text-sm text-slate-500 mt-0.5">{{ step.detail }}</p>
              </div>
            </li>
          </ol>
        </section>

        <section class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-bricolage text-lg font-bold text-[#0F172A] mb-4">Dossier transmis</h2>
            <ul class="space-y-3">
              <li v-for="check in checks" :key="check.label" class="flex items-start gap-3 text-sm">
                <span
                  class="mt-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0"
                  :class="check.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                >
                  {{ check.ok ? '✓' : '!' }}
                </span>
                <div class="min-w-0">
                  <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ check.label }}</p>
                  <p class="text-slate-700 truncate">{{ check.value || 'À compléter' }}</p>
                </div>
              </li>
            </ul>
            <p class="text-xs text-slate-400 mt-4">
              Inscrite le {{ formatDate(agency.created_at) }}
            </p>
          </div>

          <div class="bg-slate-50 rounded-2xl border border-dashed border-slate-200 p-5 text-sm text-slate-600">
            <p class="font-bold text-[#0F172A] mb-1">Pendant l’attente</p>
            <p>
              Vous pouvez mettre à jour la fiche de votre agence et vos paramètres.
              La flotte et les points de retrait se débloquent après validation.
            </p>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>
