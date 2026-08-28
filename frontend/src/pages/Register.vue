<template>
  <div class="min-h-screen flex flex-col md:flex-row">
    <!-- LEFT DECORATIVE PANEL -->
    <div class="hidden md:flex w-[45%] lg:w-1/2 left-panel-bg relative text-white flex-col justify-between p-10 lg:p-14">
      <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>

      <div class="relative z-10 flex flex-col h-full justify-between">
        <RouterLink to="/" class="gr-logo text-white hover:opacity-80 transition-opacity">
          <span class="gr-logo-dot bg-white"></span>GlobalRental
        </RouterLink>

        <div class="mt-auto pb-10 fade-up">
          <h1 class="font-bricolage text-4xl lg:text-5xl leading-tight mb-8">
            La liberté de conduire ce que vous voulez, où vous voulez.
          </h1>

          <ul class="space-y-4 mb-10 text-lg opacity-90">
            <li class="flex items-center gap-3">
              <div class="bg-white/20 rounded-full p-1">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </div>
              Plus de 1 450 voyageurs satisfaits au Maroc
            </li>
            <li class="flex items-center gap-3">
              <div class="bg-white/20 rounded-full p-1">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </div>
              Assurance incluse sur chaque réservation
            </li>
            <li class="flex items-center gap-3">
              <div class="bg-white/20 rounded-full p-1">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </div>
              Annulation gratuite jusqu'à 24h avant
            </li>
          </ul>

          <div class="flex items-center gap-3 pt-6 border-t border-white/20">
            <div class="flex text-yellow-400 text-xl">★★★★★</div>
            <span class="font-medium">5.0 — Noté excellent par nos utilisateurs</span>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT FORM PANEL -->
    <div class="flex-1 flex flex-col min-h-screen bg-white relative">
      <header class="p-3 flex justify-between items-center w-full">
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

      <div class="flex-1 flex items-center justify-center p-4 lg:p-6">
        <div class="w-full max-w-[440px] fade-up fade-up-1">
          <!-- Tabs -->
          <div class="flex border-b mb-4" style="border-color: var(--border);">
            <RouterLink to="/login" class="tab-btn flex-1 text-center">Se connecter</RouterLink>
            <button type="button" class="tab-btn active flex-1 text-center">S'inscrire</button>
          </div>

          <div class="mb-4">
            <h2 class="font-bricolage text-2xl mb-1">Créer votre compte</h2>
            <p class="text-sm" style="color: var(--ink-muted);">Rejoignez des milliers de conducteurs au Maroc</p>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-3" novalidate>
            <!-- Role Selection -->
            <div class="mb-2">
              <label class="form-label mb-2">Vous êtes :</label>
              <div class="grid grid-cols-2 gap-3">
                <button type="button" class="role-card" :class="{ selected: form.role === 'client' }" @click="form.role = 'client'">
                  <div class="text-xl mb-1"><svg style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;" viewBox="0 0 24 24"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><path d="M9 17H5m0 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/><path d="M13 17h5m0 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/></svg></div>
                  <div class="font-semibold text-sm">Conducteur</div>
                  <div class="text-xs mt-1" style="color: var(--ink-muted);">Je cherche à louer une voiture</div>
                </button>
                <button type="button" class="role-card" :class="{ selected: form.role === 'agency' }" @click="form.role = 'agency'">
                  <div class="text-xl mb-1"><svg style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="18" rx="2"/><line x1="8" y1="21" x2="8" y2="3"/><line x1="16" y1="21" x2="16" y2="3"/><line x1="2" y1="9" x2="22" y2="9"/><line x1="2" y1="15" x2="22" y2="15"/></svg></div>
                  <div class="font-semibold text-sm">Agence / Hôte</div>
                  <div class="text-xs mt-1" style="color: var(--ink-muted);">Je veux proposer des véhicules</div>
                </button>
              </div>
            </div>

            <!-- Common Fields -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label" for="reg-prenom">Prénom</label>
                <input v-model="form.first_name" type="text" id="reg-prenom" class="form-input" :class="{ 'is-invalid': errors.first_name }" placeholder="Mehdi" required />
                <p v-if="errors.first_name" class="text-xs mt-1" style="color: #EF4444;">{{ errors.first_name[0] }}</p>
              </div>
              <div>
                <label class="form-label" for="reg-nom">Nom</label>
                <input v-model="form.last_name" type="text" id="reg-nom" class="form-input" :class="{ 'is-invalid': errors.last_name }" placeholder="El Fassi" required />
                <p v-if="errors.last_name" class="text-xs mt-1" style="color: #EF4444;">{{ errors.last_name[0] }}</p>
              </div>
            </div>

            <div>
              <label class="form-label" for="reg-email">Adresse e-mail</label>
              <input v-model="form.email" type="email" id="reg-email" class="form-input" :class="{ 'is-invalid': errors.email }" placeholder="mehdi@exemple.com" required />
              <p v-if="errors.email" class="text-xs mt-1" style="color: #EF4444;">{{ errors.email[0] }}</p>
            </div>

            <div>
              <label class="form-label" for="reg-phone">Numéro de téléphone</label>
              <div class="flex">
                <div class="flex items-center justify-center border border-r-0 rounded-l-lg px-3 text-sm font-medium" style="background: var(--bg); border-color: var(--border); color: var(--ink-secondary);">
                  Maroc +212
                </div>
                <input v-model="form.phone" type="tel" id="reg-phone" class="form-input rounded-l-none" :class="{ 'is-invalid': errors.phone }" placeholder="6 12 34 56 78" :required="form.role === 'agency'" />
              </div>
              <p v-if="errors.phone" class="text-xs mt-1" style="color: #EF4444;">{{ errors.phone[0] }}</p>
            </div>

            <!-- Agency Fields -->
            <div v-if="form.role === 'agency'" class="space-y-5 p-5 rounded-xl border mt-4" style="background: var(--bg); border-color: var(--border);">
              <h3 class="font-semibold text-sm uppercase tracking-wider mb-2" style="color: var(--ink-muted);">Informations de l'agence</h3>

              <div>
                <label class="form-label" for="reg-agency-name">Nom de l'agence</label>
                <input v-model="form.agency_name" type="text" id="reg-agency-name" class="form-input" :class="{ 'is-invalid': errors.agency_name }" placeholder="Ex: AutoMaroc Location" required />
                <p v-if="errors.agency_name" class="text-xs mt-1" style="color: #EF4444;">{{ errors.agency_name[0] }}</p>
              </div>

              <div>
                <label class="form-label" for="reg-agency-city">Ville principale</label>
                <select v-model="form.agency_city" id="reg-agency-city" class="form-input appearance-none" :class="{ 'is-invalid': errors.agency_city }" style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22292%22 height=%22292%22%3E%3Cpath fill=%22%231A1A1C%22 d=%22M287 69a17 17 0 0 0-13-5H18a17 17 0 0 0-13 5 17 17 0 0 0-5 13c0 5 2 9 5 13l128 128a18 18 0 0 0 26 0L287 95c3-4 5-8 5-13s-2-9-5-13z%22/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 0.65rem auto; padding-right: 2.5rem;" required>
                  <option value="" disabled>Sélectionnez une ville</option>
                  <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <p v-if="errors.agency_city" class="text-xs mt-1" style="color: #EF4444;">{{ errors.agency_city[0] }}</p>
              </div>

              <div>
                <label class="form-label" for="reg-address">Adresse</label>
                <input v-model="form.address" type="text" id="reg-address" class="form-input" :class="{ 'is-invalid': errors.address }" placeholder="15 Bd Mohammed V, Tanger" required />
                <p v-if="errors.address" class="text-xs mt-1" style="color: #EF4444;">{{ errors.address[0] }}</p>
              </div>

              <div>
                <label class="form-label" for="reg-agency-phone">Téléphone de l'agence</label>
                <div class="flex">
                  <div class="flex items-center justify-center border border-r-0 rounded-l-lg px-3 text-sm font-medium" style="background: var(--bg); border-color: var(--border); color: var(--ink-secondary);">
                    Maroc +212
                  </div>
                  <input v-model="form.agency_phone" type="tel" id="reg-agency-phone" class="form-input rounded-l-none" :class="{ 'is-invalid': errors.agency_phone }" placeholder="5 39 12 34 56" required />
                </div>
                <p v-if="errors.agency_phone" class="text-xs mt-1" style="color: #EF4444;">{{ errors.agency_phone[0] }}</p>
              </div>
            </div>

            <!-- Passwords -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label" for="reg-password">Mot de passe</label>
                <input v-model="form.password" type="password" id="reg-password" class="form-input" :class="{ 'is-invalid': errors.password }" placeholder="••••••••" minlength="8" required />
                <p v-if="errors.password" class="text-xs mt-1" style="color: #EF4444;">{{ errors.password[0] }}</p>
              </div>
              <div>
                <label class="form-label" for="reg-password-confirm">Confirmer le mot de passe</label>
                <input v-model="form.password_confirmation" type="password" id="reg-password-confirm" class="form-input" placeholder="••••••••" minlength="8" required />
              </div>
            </div>
            <p class="text-xs" style="color: var(--ink-muted);">8 caractères min, avec au moins une lettre et un chiffre.</p>

            <div class="flex items-start gap-3 mt-4">
              <input v-model="form.terms" type="checkbox" id="terms" class="checkbox mt-0.5" required />
              <label for="terms" class="text-sm cursor-pointer leading-tight" style="color: var(--ink-secondary);">
                J'accepte les Conditions Générales d'Utilisation et la Politique de Confidentialité
              </label>
            </div>

            <div v-if="globalError" class="p-3 rounded-lg text-sm" style="background: rgba(239,68,68,0.08); color: #EF4444; border: 1px solid rgba(239,68,68,0.25);">{{ globalError }}</div>
            <div v-if="success" class="p-3 rounded-lg text-sm" style="background: rgba(16,185,129,0.08); color: #10B981; border: 1px solid rgba(16,185,129,0.25);">{{ success }}</div>

            <button type="submit" class="btn-primary mt-6" :disabled="loading">
              <span v-if="loading">Chargement…</span>
              <span v-else>Créer mon compte</span>
            </button>
          </form>

          <p class="text-center mt-8 text-sm" style="color: var(--ink-secondary);">
            Déjà inscrit ?
            <RouterLink to="/login" class="font-bold hover:underline" style="color: var(--ink);">Se connecter &rarr;</RouterLink>
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

// Villes hardcodées (UUIDs de la base globalrental)
const cities = [
  { id: '0bb9936b-e4c3-47c8-9e87-d04008b75155', name: 'Agadir' },
  { id: '155d2c6a-3b8d-407e-8c31-f68fd5195b01', name: 'Casablanca' },
  { id: '93be8953-221f-403a-b047-dbbba0fac6f1', name: 'Fes' },
  { id: '97c810d2-4993-4670-82ed-6678baa9acb1', name: 'Marrakech' },
  { id: '769f553f-a4b2-45f2-8889-5354ee7eaeb6', name: 'Oujda' },
  { id: '71441af3-48e6-4c59-ae6c-0d6ed13be6a1', name: 'Rabat' },
  { id: '7c16c6fa-ce3e-407d-a70f-66a0ce5b27f7', name: 'Tanger' },
]

const form = reactive({
  role: 'client',
  first_name: '', last_name: '', email: '', phone: '',
  password: '', password_confirmation: '',
  agency_name: '', agency_city: '', address: '', agency_phone: '',
  terms: false,
})

const errors = reactive({})
const globalError = ref('')
const success = ref('')
const loading = ref(false)

function normalizePhone(local) {
  const digits = (local || '').replace(/\D/g, '').replace(/^0+/, '')
  return digits ? `+212${digits}` : ''
}

function resetErrors() {
  Object.keys(errors).forEach(k => delete errors[k])
  globalError.value = ''
  success.value = ''
}

async function handleSubmit() {
  console.log('1. handleSubmit called, role =', form.role)
  resetErrors()

  if (!form.terms) {
    console.log('2. STOP - CGU not accepted')
    globalError.value = 'Vous devez accepter les CGU pour continuer.'
    return
  }
  if (form.password !== form.password_confirmation) {
    console.log('3. STOP - passwords do not match')
    errors.password = ['Les deux mots de passe ne correspondent pas.']
    return
  }

  loading.value = true
  console.log('4. Local validation OK, about to call the store')

  try {
    if (form.role === 'client') {
      const payload = {
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        phone: form.phone ? normalizePhone(form.phone) : null,
        password: form.password,
        password_confirmation: form.password_confirmation,
      }
      console.log('5a. Calling auth.registerClient with:', payload)
      await auth.registerClient(payload)
      console.log('6a. registerClient SUCCESS, now calling login...')
      await auth.login({ email: form.email, password: form.password })
      console.log('7a. login SUCCESS, redirecting to /')
      router.push('/')
    } else {
      const payload = {
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        phone: normalizePhone(form.phone),
        password: form.password,
        password_confirmation: form.password_confirmation,
        agency_name: form.agency_name,
        agency_city: form.agency_city,
        address: form.address,
        agency_phone: normalizePhone(form.agency_phone),
      }
      console.log('5b. Calling auth.registerAgency with:', payload)
      await auth.registerAgency(payload)
      console.log('6b. registerAgency SUCCESS')
      success.value = 'Demande enregistrée. Votre agence est en attente de validation.'
      setTimeout(() => router.push('/login'), 3000)
    }
  } catch (e) {
    console.log('8. ERROR caught:', e)
    console.log('   status:', e?.status, '| message:', e?.message, '| errors:', e?.errors)
    if (e.status === 422 && e.errors) Object.assign(errors, e.errors)
    else globalError.value = e.message
  } finally {
    loading.value = false
    console.log('9. handleSubmit finished')
  }
}
</script>

//test oussama