<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import api from '@/services/api'
import smartdriveService from '@/services/smartdrive'

const open = ref(false)
const view = ref('intro')
const loading = ref(false)
const error = ref('')
const cars = ref([])
const cities = ref([])
const citiesLoading = ref(false)
const recommendations = ref([])
const launcherNote = ref(0)

const launcherNotes = [
  'Je connais votre prochaine voiture.',
  'Quelques choix. Une recommandation claire.',
  'Votre trajet mérite mieux qu’un simple filtre.',
]
let launcherNoteTimer

const preferences = ref({
  cityId: '',
  startDate: '',
  endDate: '',
  passengers: 2,
  budget: 450,
  vehicleType: '',
  transmission: '',
  energy: '',
})

const hasResults = computed(() => recommendations.value.length > 0)

function openAssistant() {
  open.value = true
  view.value = 'intro'
  error.value = ''
  loadCities()
}

function rotateLauncherNote() {
  launcherNote.value = (launcherNote.value + 1) % launcherNotes.length
}

onMounted(() => {
  launcherNoteTimer = window.setInterval(rotateLauncherNote, 5200)
})

onUnmounted(() => {
  window.clearInterval(launcherNoteTimer)
})

function closeAssistant() {
  open.value = false
}

function normalize(value) {
  return String(value || '').trim().toLowerCase()
}

function carTitle(car) {
  return `${car?.brand || ''} ${car?.model || ''}`.trim() || 'Véhicule'
}

function carImage(car) {
  const image = (car?.images || []).find((item) => item.is_primary) || car?.images?.[0]
  return car?.image_url || image?.url || image?.image_url || null
}

function extractCities(response) {
  if (Array.isArray(response?.cities)) return response.cities
  if (Array.isArray(response?.value)) return response.value
  if (Array.isArray(response?.data?.cities)) return response.data.cities
  if (Array.isArray(response?.data?.value)) return response.data.value
  if (Array.isArray(response?.data)) return response.data
  return Array.isArray(response) ? response : []
}

async function loadCities() {
  if (cities.value.length || citiesLoading.value) return
  citiesLoading.value = true
  try {
    const response = await api.get('/cities')
    cities.value = extractCities(response).filter((city) => city?.id && city?.name)
  } catch {
    cities.value = []
  } finally {
    citiesLoading.value = false
  }
}

function scoreCar(car) {
  if (Number.isFinite(Number(car.score))) return Number(car.score)

  const choice = preferences.value
  let score = 52
  const type = car.type || car.category
  const energy = car.energy_type || car.energy

  if (choice.city && normalize(car.city?.name || car.city) === normalize(choice.city)) score += 18
  if (choice.vehicleType && normalize(type) === normalize(choice.vehicleType)) score += 18
  if (choice.transmission && normalize(car.transmission) === normalize(choice.transmission)) score += 14
  if (choice.energy && normalize(energy) === normalize(choice.energy)) score += 10
  if (Number(car.seats || 0) >= Number(choice.passengers)) score += 10
  if (Number(car.daily_price || 0) <= Number(choice.budget)) score += 10
  else score -= Math.min(20, Math.ceil((Number(car.daily_price) - Number(choice.budget)) / 50))

  return Math.max(1, Math.min(99, score))
}

function resultReason(car, index) {
  if (index === 0) return 'Le meilleur équilibre selon vos critères.'
  if (Number(car.daily_price || 0) <= Number(preferences.value.budget)) return 'Une option qui respecte votre budget.'
  return 'Une alternative confortable pour votre trajet.'
}

async function analyze() {
  loading.value = true
  error.value = ''
  view.value = 'loading'

  try {
    cars.value = await smartdriveService.getEligibleVehicles(preferences.value)

    await new Promise((resolve) => window.setTimeout(resolve, 650))
    recommendations.value = [...cars.value]
      .filter((car) => car.status === undefined || car.status === 'available')
      .map((car) => ({
        ...car,
        smartScore: scoreCar(car),
        smartConfidence: car.confidence ?? null,
        smartExplanation: car.explanation || { strengths: [resultReason(car, 0)], tradeoffs: [] },
        smartReason: car.explanation?.summary || resultReason(car, 0),
      }))
      .sort((first, second) => second.smartScore - first.smartScore)
      .slice(0, 3)

    if (!recommendations.value.length) {
      error.value = 'Aucun véhicule disponible pour ces critères.'
      view.value = 'empty'
    } else {
      recommendations.value = recommendations.value.map((car, index) => ({
        ...car,
        smartConfidence: car.confidence ?? null,
        smartExplanation: car.explanation || { strengths: [resultReason(car, index)], tradeoffs: [] },
        smartReason: car.explanation?.summary || resultReason(car, index),
      }))
      view.value = 'results'
    }
  } catch (err) {
    error.value = err?.message || 'Aucun véhicule disponible pour ces critères.'
    view.value = 'error'
  } finally {
    loading.value = false
  }
}

function editPreferences() {
  view.value = 'form'
}

function startOver() {
  recommendations.value = []
  preferences.value = { cityId: '', startDate: '', endDate: '', passengers: 2, budget: 450, vehicleType: '', transmission: '', energy: '' }
  view.value = 'form'
}

function reserve(car) {
  closeAssistant()
}
</script>

<template>
  <div class="smartdrive-root">
    <Transition name="smartdrive-note">
      <button
        v-if="!open"
        type="button"
        class="smartdrive-note"
        aria-label="Découvrir les recommandations SmartDrive"
        @click="openAssistant"
      >
        <span class="smartdrive-note-dot" aria-hidden="true" />
        {{ launcherNotes[launcherNote] }}
      </button>
    </Transition>
    <button
      v-if="!open"
      type="button"
      class="smartdrive-launcher"
      aria-label="Ouvrir SmartDrive AI pour choisir un véhicule"
      :aria-expanded="open"
      @click="openAssistant"
    >
      <span class="smartdrive-launcher-glow" aria-hidden="true" />
      <span class="smartdrive-launcher-icon" aria-hidden="true">
        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.7">
          <path d="M7 20.5h18l-1.4-6.3a2.6 2.6 0 0 0-2.5-2H10.9a2.6 2.6 0 0 0-2.5 2L7 20.5Z" />
          <path d="M6 20.5v3M26 20.5v3M10 20.5h.01M22 20.5h.01M10 12.2l1.6-3h8.8l1.6 3" />
        </svg>
        <span class="smartdrive-spark">✦</span>
      </span>
      <span class="smartdrive-launcher-copy"><strong>SmartDrive</strong><small>Votre copilote location</small></span>
      <span class="smartdrive-live-dot" aria-label="Assistant disponible" />
    </button>
      <Transition name="smartdrive-fade">
        <section v-if="open" class="smartdrive-panel" role="dialog" aria-modal="false" aria-labelledby="smartdrive-title">
        <div class="smartdrive-panel-bar" />
        <header class="smartdrive-header">
          <div class="smartdrive-avatar" aria-hidden="true"><span>✦</span></div>
          <div>
            <p class="smartdrive-kicker">GLOBALRENTAL AI</p>
            <h2 id="smartdrive-title">SmartDrive</h2>
            <p class="smartdrive-status"><span /> Prêt à vous guider</p>
          </div>
          <button type="button" class="smartdrive-close" aria-label="Fermer SmartDrive AI" @click="closeAssistant">×</button>
        </header>

        <div class="smartdrive-body">
          <template v-if="view === 'intro' || view === 'form'">
            <div class="smartdrive-intro">
              <div class="smartdrive-welcome-chip"><span>✦</span> Recommandation personnalisée</div>
              <h3>On trouve votre meilleur trajet.</h3>
              <p>Dites-nous ce qui compte. SmartDrive compare les options disponibles et vous explique son choix.</p>
            </div>
            <form class="smartdrive-form" @submit.prevent="analyze">
              <div class="smartdrive-field">
                <label for="smartdrive-city">Ville de départ</label>
                <select id="smartdrive-city" v-model="preferences.cityId" required :disabled="citiesLoading || !cities.length">
                  <option value="" disabled>{{ citiesLoading ? 'Chargement des villes…' : 'Choisir une ville' }}</option>
                  <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
                </select>
                <span v-if="!citiesLoading && !cities.length" class="smartdrive-field-hint">Les villes sont momentanément indisponibles.</span>
              </div>
              <div class="smartdrive-field">
                <label for="smartdrive-passengers">Voyageurs</label>
                <input id="smartdrive-passengers" v-model.number="preferences.passengers" type="number" min="1" max="9" required />
              </div>
              <div class="smartdrive-field">
                <label for="smartdrive-start-date">Départ</label>
                <input id="smartdrive-start-date" v-model="preferences.startDate" type="date" required />
              </div>
              <div class="smartdrive-field">
                <label for="smartdrive-end-date">Retour</label>
                <input id="smartdrive-end-date" v-model="preferences.endDate" type="date" :min="preferences.startDate" required />
              </div>
              <div class="smartdrive-field">
                <label for="smartdrive-budget">Budget / jour</label>
                <div class="smartdrive-input-suffix"><input id="smartdrive-budget" v-model.number="preferences.budget" type="number" min="0" required /><span>MAD</span></div>
              </div>
              <div class="smartdrive-field">
                <label for="smartdrive-type">Type</label>
                <select id="smartdrive-type" v-model="preferences.vehicleType">
                  <option value="">Tous les types</option><option value="hatchback">Citadine</option><option value="suv">SUV</option><option value="sedan">Berline</option><option value="van">Utilitaire</option>
                </select>
              </div>
              <div class="smartdrive-field">
                <label for="smartdrive-transmission">Boîte</label>
                <select id="smartdrive-transmission" v-model="preferences.transmission">
                  <option value="">Indifférent</option><option value="automatic">Automatique</option><option value="manual">Manuelle</option>
                </select>
              </div>
              <div class="smartdrive-field">
                <label for="smartdrive-energy">Énergie</label>
                <select id="smartdrive-energy" v-model="preferences.energy">
                  <option value="">Indifférent</option><option value="gasoline">Essence</option><option value="diesel">Diesel</option><option value="hybrid">Hybride</option><option value="electric">Électrique</option>
                </select>
              </div>
              <p v-if="error" class="smartdrive-error" role="alert">{{ error }}</p>
              <button type="submit" class="smartdrive-primary">Voir mes recommandations <span aria-hidden="true">→</span></button>
            </form>
          </template>

          <div v-else-if="view === 'loading'" class="smartdrive-loading" aria-live="polite">
            <div class="smartdrive-spinner" aria-hidden="true" />
            <h3>SmartDrive analyse les options</h3>
            <p>Nous comparons les véhicules disponibles selon vos priorités.</p>
            <div class="smartdrive-progress"><span /></div>
          </div>

          <div v-else-if="view === 'empty'" class="smartdrive-empty" role="status">
            <h3>Aucun véhicule disponible pour ces critères.</h3>
            <p>Essayez d’élargir vos critères de recherche.</p>
            <button type="button" class="smartdrive-secondary" @click="editPreferences">Modifier mes critères</button>
          </div>

          <div v-else-if="view === 'error'" class="smartdrive-empty" role="alert">
            <h3>SmartDrive AI est momentanément indisponible.</h3>
            <p>{{ error }}</p>
            <button type="button" class="smartdrive-secondary" @click="editPreferences">Modifier mes critères</button>
          </div>

          <template v-else-if="view === 'results' && hasResults">
            <div class="smartdrive-results-heading"><div><h3>Vos meilleurs matchs</h3><p>Des suggestions adaptées à votre recherche.</p></div><span class="smartdrive-mini-mark">✦</span></div>
            <article v-for="(car, index) in recommendations" :key="car.id" class="smartdrive-result">
              <span v-if="index === 0" class="smartdrive-best-badge">Meilleur choix</span>
              <div class="smartdrive-car-image">
                <img v-if="carImage(car)" :src="carImage(car)" :alt="carTitle(car)" />
                <span v-else aria-hidden="true">🚙</span>
              </div>
              <div class="smartdrive-result-main">
                <h4>{{ carTitle(car) }}</h4>
                <p>{{ car.type || 'Véhicule' }} · {{ car.transmission || 'Boîte standard' }}<br /><strong>{{ Number(car.daily_price || 0).toLocaleString('fr-MA') }} MAD</strong> / jour</p>
              </div>
              <div class="smartdrive-score"><strong>{{ car.smartScore }}%</strong><span>match</span></div>
              <div v-if="car.smartConfidence !== null" class="smartdrive-confidence">Confiance {{ car.smartConfidence }}%</div>
              <p class="smartdrive-reason"><b v-if="index === 0">Notre choix · </b>{{ car.smartReason }}</p>
              <ul v-if="car.smartExplanation?.strengths?.length" class="smartdrive-explanation">
                <li v-for="strength in car.smartExplanation.strengths.slice(0, 3)" :key="strength">{{ strength }}</li>
              </ul>
              <p v-if="car.smartExplanation?.tradeoffs?.length" class="smartdrive-tradeoff">À savoir : {{ car.smartExplanation.tradeoffs.join(' ; ') }}</p>
              <RouterLink
                class="smartdrive-book"
                :to="{ name: 'ReservationCreate', query: { car_id: car.id } }"
                @click="reserve(car)"
              >
                Choisir ce véhicule <span aria-hidden="true">→</span>
              </RouterLink>
            </article>
            <div class="smartdrive-result-actions"><button type="button" class="smartdrive-secondary" @click="editPreferences">Modifier</button><button type="button" class="smartdrive-secondary" @click="startOver">Nouvelle recherche</button></div>
          </template>
        </div>
      </section>
    </Transition>
  </div>
</template>

<style scoped>
.smartdrive-root { position: relative; z-index: 40; }
.smartdrive-launcher { position: fixed; right: 1.25rem; bottom: 1.25rem; display: inline-flex; align-items: center; gap: .65rem; padding: .65rem .9rem .65rem .65rem; color: #fff; background: #0f172a; border: 1px solid #334155; border-radius: .85rem; box-shadow: 0 12px 28px rgba(15,23,42,.22); font-size: .8125rem; font-weight: 700; transition: transform .2s ease, background .2s ease; }
.smartdrive-launcher:hover { transform: translateY(-2px); background: #1e293b; }
.smartdrive-launcher:focus-visible, .smartdrive-close:focus-visible, .smartdrive-primary:focus-visible, .smartdrive-secondary:focus-visible, .smartdrive-book:focus-visible { outline: 3px solid rgba(37,99,235,.35); outline-offset: 2px; }
.smartdrive-launcher-icon { position: relative; display: grid; place-items: center; width: 2rem; height: 2rem; color: #bfdbfe; background: #1d4ed8; border-radius: .65rem; }.smartdrive-launcher-icon svg { width: 1.35rem; height: 1.35rem; }.smartdrive-spark { position: absolute; top: -.35rem; right: -.35rem; color: #fbbf24; font-size: .85rem; }.smartdrive-launcher-label { display: none; }
.smartdrive-panel { position: fixed; right: 1.25rem; bottom: 4.8rem; width: min(430px, calc(100vw - 2rem)); max-height: calc(100vh - 7rem); overflow-y: auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 1.1rem; box-shadow: 0 24px 60px rgba(15,23,42,.22); }.smartdrive-panel-bar { height: .25rem; background: linear-gradient(90deg,#2563eb,#60a5fa,#10b981); }.smartdrive-header { display: flex; align-items: center; gap: .7rem; padding: 1.15rem 1.25rem 1rem; border-bottom: 1px solid #f1f5f9; }.smartdrive-avatar,.smartdrive-mini-mark { display: grid; place-items: center; color: #fff; background: #0f172a; border-radius: .7rem; }.smartdrive-avatar { width: 2.35rem; height: 2.35rem; font-size: 1.15rem; }.smartdrive-kicker { margin: 0 0 .15rem; color: #64748b; font-size: .6rem; font-weight: 700; letter-spacing: .14em; }.smartdrive-header h2 { margin: 0; color: #0f172a; font-family: Bricolage Grotesque,sans-serif; font-size: 1.2rem; }.smartdrive-close { margin-left: auto; padding: .2rem; color: #94a3b8; background: transparent; border: 0; font-size: 1.7rem; line-height: 1; }.smartdrive-body { padding: 1.2rem 1.25rem 1.25rem; }.smartdrive-intro h3,.smartdrive-loading h3,.smartdrive-results-heading h3 { margin: 0 0 .35rem; color: #0f172a; font-family: Bricolage Grotesque,sans-serif; font-size: 1.35rem; letter-spacing: -.02em; }.smartdrive-intro p,.smartdrive-loading p,.smartdrive-results-heading p { margin: 0 0 1.15rem; color: #64748b; font-size: .8rem; line-height: 1.5; }.smartdrive-form { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; }.smartdrive-field { display: flex; flex-direction: column; gap: .35rem; }.smartdrive-field label { color: #475569; font-size: .7rem; font-weight: 700; }.smartdrive-field input,.smartdrive-field select { width: 100%; min-width: 0; padding: .62rem .7rem; color: #0f172a; background: #fff; border: 1px solid #e2e8f0; border-radius: .55rem; font-size: .8rem; outline: none; }.smartdrive-field input:focus,.smartdrive-field select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }.smartdrive-input-suffix { position: relative; }.smartdrive-input-suffix input { padding-right: 2.75rem; }.smartdrive-input-suffix span { position: absolute; right: .65rem; top: 50%; color: #94a3b8; font-size: .65rem; font-weight: 700; transform: translateY(-50%); }.smartdrive-primary,.smartdrive-book { width: 100%; padding: .72rem .9rem; color: #fff; background: #0f172a; border: 0; border-radius: .55rem; font-size: .78rem; font-weight: 700; transition: background .2s ease, transform .2s ease; }.smartdrive-primary { grid-column: 1 / -1; margin-top: .25rem; }.smartdrive-primary:hover,.smartdrive-book:hover { background: #1e293b; transform: translateY(-1px); }.smartdrive-error { grid-column: 1 / -1; margin: 0; padding: .65rem .75rem; color: #be123c; background: #fff1f2; border: 1px solid #fecdd3; border-radius: .55rem; font-size: .72rem; }.smartdrive-loading { padding: 2rem 0 1rem; text-align: center; }.smartdrive-spinner { width: 2.7rem; height: 2.7rem; margin: 0 auto 1rem; border: .25rem solid #dbeafe; border-top-color: #2563eb; border-radius: 999px; animation: smartdrive-spin .8s linear infinite; }.smartdrive-loading h3 { font-size: 1.1rem; }.smartdrive-loading p { margin-bottom: 1.4rem; }.smartdrive-progress { height: .3rem; overflow: hidden; background: #e2e8f0; border-radius: 999px; }.smartdrive-progress span { display: block; width: 55%; height: 100%; background: #2563eb; animation: smartdrive-progress 1.3s ease-in-out infinite; }.smartdrive-results-heading { display: flex; justify-content: space-between; gap: .75rem; }.smartdrive-results-heading p { margin-bottom: .9rem; }.smartdrive-mini-mark { width: 1.9rem; height: 1.9rem; flex: 0 0 auto; color: #bfdbfe; font-size: .9rem; }.smartdrive-result { display: grid; grid-template-columns: 3.4rem 1fr auto; gap: .65rem; align-items: center; padding: .7rem; margin-top: .65rem; border: 1px solid #e2e8f0; border-radius: .75rem; }.smartdrive-car-image { width: 3.4rem; height: 3.4rem; display: grid; place-items: center; overflow: hidden; color: #2563eb; background: #eff6ff; border-radius: .55rem; font-size: 1.4rem; }.smartdrive-car-image img { width: 100%; height: 100%; object-fit: cover; }.smartdrive-result-main { min-width: 0; }.smartdrive-result-main h4 { margin: 0 0 .2rem; overflow: hidden; color: #0f172a; font-size: .8rem; text-overflow: ellipsis; white-space: nowrap; }.smartdrive-result-main p { margin: 0; color: #64748b; font-size: .65rem; line-height: 1.45; }.smartdrive-result-main strong { color: #0f172a; }.smartdrive-score { text-align: right; color: #087443; font-size: .6rem; font-weight: 700; }.smartdrive-score strong { display: block; font-size: 1rem; }.smartdrive-reason { grid-column: 2 / -1; margin: 0; padding-top: .55rem; color: #64748b; border-top: 1px solid #f1f5f9; font-size: .68rem; }.smartdrive-reason b { color: #0f172a; }.smartdrive-book { grid-column: 2 / -1; padding: .55rem; font-size: .7rem; }.smartdrive-result-actions { display: flex; gap: .6rem; margin-top: .8rem; }.smartdrive-secondary { flex: 1; padding: .6rem; color: #334155; background: #fff; border: 1px solid #e2e8f0; border-radius: .55rem; font-size: .7rem; font-weight: 700; }.smartdrive-secondary:hover { background: #f8fafc; }.smartdrive-fade-enter-active,.smartdrive-fade-leave-active { transition: opacity .2s ease, transform .2s ease; }.smartdrive-fade-enter-from,.smartdrive-fade-leave-to { opacity: 0; transform: translateY(.7rem) scale(.98); } @keyframes smartdrive-spin { to { transform: rotate(360deg); } } @keyframes smartdrive-progress { 0% { transform: translateX(-120%); } 100% { transform: translateX(220%); } }
@media (max-width: 640px) { .smartdrive-launcher { right: 1rem; bottom: 1rem; padding: .65rem; }.smartdrive-launcher-label { display: inline; }.smartdrive-panel { right: 1rem; bottom: 4.5rem; width: calc(100vw - 2rem); max-height: calc(100vh - 6rem); }.smartdrive-body { padding: 1rem; }.smartdrive-form { grid-template-columns: 1fr; }.smartdrive-primary,.smartdrive-error { grid-column: auto; }.smartdrive-result { grid-template-columns: 3rem 1fr auto; }.smartdrive-car-image { width: 3rem; height: 3rem; } }
.smartdrive-book { display: block; text-align: center; text-decoration: none; }
.smartdrive-launcher { position: fixed; z-index: 2; isolation: isolate; overflow: visible; }
.smartdrive-launcher-glow { position: absolute; inset: -.35rem; z-index: -1; border: 1px solid rgba(37,99,235,.32); border-radius: 1rem; animation: smartdrive-pulse 2.8s ease-out infinite; }
.smartdrive-live-dot { width: .42rem; height: .42rem; margin-left: -.2rem; background: #4ade80; border: 2px solid #0f172a; border-radius: 999px; box-sizing: content-box; }
.smartdrive-backdrop { position: fixed; inset: 0; z-index: 0; width: 100%; height: 100%; padding: 0; background: rgba(15,23,42,.08); border: 0; cursor: default; }
.smartdrive-panel { z-index: 2; }
.smartdrive-status { display: flex; align-items: center; gap: .3rem; margin: .1rem 0 0; color: #64748b; font-size: .62rem; }
.smartdrive-status span { width: .35rem; height: .35rem; background: #22c55e; border-radius: 50%; }
.smartdrive-welcome-chip { display: inline-flex; align-items: center; gap: .35rem; margin-bottom: .65rem; padding: .3rem .5rem; color: #1d4ed8; background: #eff6ff; border: 1px solid #dbeafe; border-radius: 999px; font-size: .62rem; font-weight: 700; }
.smartdrive-welcome-chip span { color: #f59e0b; font-size: .8rem; }
.smartdrive-result { position: relative; }
.smartdrive-best-badge { position: absolute; top: -.55rem; right: .7rem; padding: .2rem .42rem; color: #166534; background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 999px; font-size: .56rem; font-weight: 800; }
.smartdrive-field-hint { color: #be123c; font-size: .62rem; }
.smartdrive-confidence { grid-column: 1 / -1; color: #166534; font-size: .65rem; font-weight: 800; }
.smartdrive-explanation { grid-column: 1 / -1; margin: 0; padding-left: 1rem; color: #475569; font-size: .68rem; line-height: 1.45; }
.smartdrive-explanation li::marker { color: #16a34a; }
.smartdrive-tradeoff { grid-column: 1 / -1; margin: 0; color: #92400e; font-size: .66rem; line-height: 1.4; }
.smartdrive-note { position: fixed; right: 1.25rem; bottom: 5.85rem; z-index: 3; display: inline-flex; align-items: center; gap: .45rem; max-width: min(285px, calc(100vw - 2rem)); padding: .55rem .75rem; color: #3d3d3f; background: rgba(255,255,255,.96); border: 1px solid #e8e8ea; border-radius: .7rem; box-shadow: 0 12px 28px rgba(10,10,11,.12); font-size: .69rem; font-weight: 700; text-align: left; cursor: pointer; backdrop-filter: blur(16px); }
.smartdrive-note:hover { color: #0a0a0b; border-color: #b8c8ef; transform: translateY(-2px); }
.smartdrive-note:after { position: absolute; right: 1.3rem; bottom: -.35rem; width: .65rem; height: .65rem; content: ''; background: #fff; border-right: 1px solid #e8e8ea; border-bottom: 1px solid #e8e8ea; transform: rotate(45deg); }
.smartdrive-note-dot { width: .38rem; height: .38rem; flex: 0 0 auto; background: #2563eb; border-radius: 50%; box-shadow: 0 0 0 4px #dbeafe; }
.smartdrive-note-enter-active,.smartdrive-note-leave-active { transition: opacity .25s ease, transform .25s ease; }
.smartdrive-note-enter-from,.smartdrive-note-leave-to { opacity: 0; transform: translateY(.4rem); }
.smartdrive-launcher { right: 1.25rem; bottom: 1.25rem; gap: .7rem; min-height: 3.35rem; padding: .45rem .8rem .45rem .45rem; background: #0a0a0b; border: 1px solid #2b2b2e; border-radius: 1rem; box-shadow: 0 16px 38px rgba(10,10,11,.24), 0 0 0 1px rgba(255,255,255,.04) inset; }
.smartdrive-launcher:hover { background: #1f1f22; box-shadow: 0 20px 42px rgba(10,10,11,.3), 0 0 0 1px rgba(255,255,255,.08) inset; }
.smartdrive-launcher:active { transform: translateY(0) scale(.98); }
.smartdrive-launcher-icon { width: 2.45rem; height: 2.45rem; color: #dbeafe; background: linear-gradient(145deg,#2f6cf6,#1743a9); border: 1px solid rgba(255,255,255,.22); border-radius: .78rem; box-shadow: 0 6px 15px rgba(37,99,235,.32); }
.smartdrive-launcher-icon svg { width: 1.65rem; height: 1.65rem; animation: smartdrive-car-float 3.2s ease-in-out infinite; }
.smartdrive-spark { top: -.48rem; right: -.45rem; color: #ffd166; text-shadow: 0 0 10px rgba(255,209,102,.7); animation: smartdrive-sparkle 2.2s ease-in-out infinite; }
.smartdrive-launcher-copy { display: flex; flex-direction: column; gap: .05rem; min-width: 7.9rem; text-align: left; }
.smartdrive-launcher-copy strong { color: #fff; font-family: 'Bricolage Grotesque', sans-serif; font-size: .85rem; letter-spacing: -.01em; }
.smartdrive-launcher-copy small { color: #a8a8ad; font-size: .6rem; font-weight: 500; }
.smartdrive-live-dot { width: .42rem; height: .42rem; margin-left: .1rem; background: #52d88c; border-color: #0a0a0b; box-shadow: 0 0 0 3px rgba(82,216,140,.15); }
.smartdrive-panel { right: 1.25rem; bottom: 5.25rem; width: min(455px, calc(100vw - 2rem)); border-color: #e8e8ea; border-radius: 1.15rem; box-shadow: 0 28px 80px rgba(10,10,11,.2), 0 0 0 1px rgba(255,255,255,.7) inset; }
.smartdrive-panel-bar { height: .3rem; background: linear-gradient(90deg,#0a0a0b 0 18%,#2563eb 18% 68%,#52d88c 68% 100%); }
.smartdrive-header { padding: 1.2rem 1.35rem 1.05rem; }
.smartdrive-avatar { position: relative; overflow: hidden; width: 2.7rem; height: 2.7rem; background: #0a0a0b; border-radius: .85rem; box-shadow: 0 8px 18px rgba(10,10,11,.16); }
.smartdrive-avatar:before { position: absolute; inset: .35rem; content: ''; border: 1px solid rgba(255,255,255,.22); border-radius: .6rem; transform: rotate(45deg); }
.smartdrive-avatar span { position: relative; z-index: 1; color: #dbeafe; animation: smartdrive-sparkle 2.6s ease-in-out infinite; }
.smartdrive-header h2 { letter-spacing: -.04em; }
.smartdrive-status { color: #6b6b70; }
.smartdrive-status span { background: #52d88c; box-shadow: 0 0 0 3px rgba(82,216,140,.14); }
.smartdrive-body { padding: 1.4rem 1.35rem 1.35rem; }
.smartdrive-welcome-chip { color: #1743a9; background: #eef4ff; border-color: #d9e6ff; }
.smartdrive-intro h3 { max-width: 18rem; font-size: 1.5rem; line-height: 1.05; }
.smartdrive-intro p { max-width: 24rem; color: #6b6b70; }
.smartdrive-field input,.smartdrive-field select { border-color: #e1e1e4; border-radius: .65rem; }
.smartdrive-field input:focus,.smartdrive-field select:focus { border-color: #1a1a1c; box-shadow: 0 0 0 3px rgba(10,10,11,.08); }
.smartdrive-primary,.smartdrive-book { background: #0a0a0b; border-radius: .68rem; }
.smartdrive-primary:hover,.smartdrive-book:hover { background: #2b2b2e; }
.smartdrive-result { background: #fff; border-color: #e8e8ea; border-radius: .85rem; box-shadow: 0 5px 16px rgba(10,10,11,.035); transition: transform .22s ease, border-color .22s ease, box-shadow .22s ease; }
.smartdrive-result:hover { border-color: #b8c8ef; box-shadow: 0 12px 28px rgba(37,99,235,.08); transform: translateY(-2px); }
.smartdrive-car-image { background: #f1f5fb; border-radius: .65rem; }
.smartdrive-score { color: #16814f; }
.smartdrive-confidence { display: inline-flex; align-items: center; justify-self: end; padding: .25rem .42rem; color: #17623e; background: #eaf8ef; border: 1px solid #ccefd9; border-radius: 999px; font-size: .58rem; }
.smartdrive-explanation { padding: .5rem .65rem .5rem 1.15rem; background: #fafafa; border-radius: .55rem; }
.smartdrive-tradeoff { padding: .45rem .55rem; background: #fff8e8; border-radius: .45rem; }
.smartdrive-empty { padding: 2.7rem 1rem; text-align: center; }
.smartdrive-empty h3 { margin: 0 0 .5rem; color: #0a0a0b; font-family: 'Bricolage Grotesque', sans-serif; font-size: 1.2rem; }
.smartdrive-empty p { color: #6b6b70; font-size: .75rem; }
@keyframes smartdrive-car-float { 0%,100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-2px) rotate(-2deg); } }
@keyframes smartdrive-sparkle { 0%,100% { opacity: .75; transform: scale(.9) rotate(0); } 50% { opacity: 1; transform: scale(1.12) rotate(12deg); } }
@keyframes smartdrive-pulse { 0% { opacity: .7; transform: scale(.98); } 65%, 100% { opacity: 0; transform: scale(1.08); } }
@media (max-width: 640px) { .smartdrive-note { right: 1rem; bottom: 5.65rem; max-width: calc(100vw - 2rem); }.smartdrive-launcher { right: 1rem; bottom: 1rem; }.smartdrive-launcher-copy { min-width: 7rem; }.smartdrive-panel { right: 1rem; bottom: 4.7rem; width: calc(100vw - 2rem); } }
@media (prefers-reduced-motion: reduce) { .smartdrive-launcher-glow,.smartdrive-spinner,.smartdrive-progress span,.smartdrive-launcher-icon svg,.smartdrive-spark,.smartdrive-avatar span { animation: none; } .smartdrive-fade-enter-active,.smartdrive-fade-leave-active,.smartdrive-note-enter-active,.smartdrive-note-leave-active { transition: none; } }
</style>
