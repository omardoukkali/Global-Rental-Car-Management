<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import AgencyLayout from '@/components/AgencyLayout.vue'
import agencyService from '@/services/agency'
import api from '@/services/api'

const points = ref([])
const cities = ref([])
const loading = ref(true)
const saving = ref(false)
const togglingId = ref(null)
const error = ref('')
const success = ref('')
const fieldErrors = reactive({})

const filter = ref('all')

const emptyForm = () => ({
  id: null,
  city_id: '',
  name: '',
  address: '',
  instructions: '',
  allows_pickup: true,
  allows_return: true,
})
const form = ref(emptyForm())
const isEditing = computed(() => !!form.value.id)

function extractPoints(data) {
  if (Array.isArray(data?.points)) return data.points
  if (Array.isArray(data?.data?.points)) return data.data.points
  if (Array.isArray(data)) return data
  return []
}

function extractCities(data) {
  if (Array.isArray(data?.cities)) return data.cities
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

const activeCount = computed(() => points.value.filter((p) => p.is_active).length)
const pickupCount = computed(() => points.value.filter((p) => p.is_active && p.allows_pickup).length)
const returnCount = computed(() => points.value.filter((p) => p.is_active && p.allows_return).length)

const visiblePoints = computed(() => {
  if (filter.value === 'active') return points.value.filter((p) => p.is_active)
  if (filter.value === 'inactive') return points.value.filter((p) => !p.is_active)
  return points.value
})

function cityName(point) {
  if (point?.city?.name) return point.city.name
  const city = cities.value.find((c) => c.id === point?.city_id)
  return city?.name || ''
}

function clearMessages() {
  error.value = ''
  success.value = ''
  Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k])
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [pointsRes, citiesRes] = await Promise.all([
      agencyService.getPoints(),
      api.get('/cities'),
    ])
    points.value = extractPoints(pointsRes)
    cities.value = extractCities(citiesRes)
    if (!form.value.city_id && cities.value[0]?.id) {
      form.value.city_id = cities.value[0].id
    }
  } catch (err) {
    error.value = err?.message || 'Impossible de charger les points.'
    points.value = []
  } finally {
    loading.value = false
  }
}

function startEdit(point) {
  clearMessages()
  form.value = {
    id: point.id,
    city_id: point.city_id || '',
    name: point.name || '',
    address: point.address || '',
    instructions: point.instructions || '',
    allows_pickup: !!point.allows_pickup,
    allows_return: !!point.allows_return,
  }
  if (typeof window !== 'undefined') window.scrollTo({ top: 0, behavior: 'smooth' })
}

function cancelEdit() {
  clearMessages()
  const cityId = form.value.city_id || cities.value[0]?.id || ''
  form.value = { ...emptyForm(), city_id: cityId }
}

function payloadFromForm() {
  return {
    city_id: form.value.city_id,
    name: form.value.name.trim(),
    address: form.value.address.trim(),
    instructions: form.value.instructions.trim() || null,
    allows_pickup: !!form.value.allows_pickup,
    allows_return: !!form.value.allows_return,
  }
}

async function submit() {
  if (saving.value) return
  clearMessages()

  if (!form.value.allows_pickup && !form.value.allows_return) {
    fieldErrors.allows_pickup = ['Autorisez au moins la prise en charge ou la restitution.']
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await agencyService.updatePoint(form.value.id, payloadFromForm())
      success.value = 'Point mis à jour.'
    } else {
      await agencyService.createPoint(payloadFromForm())
      success.value = 'Point créé. Il apparaîtra dans le formulaire de réservation.'
    }
    cancelEdit()
    await load()
  } catch (err) {
    if (err?.errors) Object.assign(fieldErrors, err.errors)
    error.value = err?.message || 'Enregistrement impossible.'
  } finally {
    saving.value = false
  }
}

async function toggle(point) {
  if (togglingId.value) return
  clearMessages()
  togglingId.value = point.id
  try {
    const data = await agencyService.togglePointStatus(point.id)
    const updated = data?.point || data?.data?.point
    if (updated) {
      points.value = points.value.map((p) => (p.id === point.id ? { ...p, ...updated } : p))
    } else {
      await load()
    }
    success.value = point.is_active
      ? 'Point désactivé : il n’est plus proposé aux clients.'
      : 'Point réactivé.'
  } catch (err) {
    error.value = err?.message || 'Changement de statut impossible.'
  } finally {
    togglingId.value = null
  }
}

onMounted(load)
</script>

<template>
  <AgencyLayout>
    <div class="space-y-6" data-testid="agency-locations">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight font-bricolage">
            Points de retrait & restitution
          </h1>
          <p class="text-sm text-slate-500 mt-1">
            Ces lieux alimentent les listes « Prise en charge » et « Restitution » du formulaire client.
          </p>
        </div>
        <div class="flex gap-3 text-xs">
          <div class="px-3 py-2 rounded-xl bg-white border border-slate-200">
            <div class="text-slate-400 font-bold uppercase tracking-wider text-[10px]">Actifs</div>
            <div class="font-extrabold text-[#0F172A] text-base">{{ activeCount }}<span class="text-slate-400 font-medium"> / {{ points.length }}</span></div>
          </div>
          <div class="px-3 py-2 rounded-xl bg-white border border-slate-200">
            <div class="text-slate-400 font-bold uppercase tracking-wider text-[10px]">Prise en charge</div>
            <div class="font-extrabold text-[#0F172A] text-base">{{ pickupCount }}</div>
          </div>
          <div class="px-3 py-2 rounded-xl bg-white border border-slate-200">
            <div class="text-slate-400 font-bold uppercase tracking-wider text-[10px]">Restitution</div>
            <div class="font-extrabold text-[#0F172A] text-base">{{ returnCount }}</div>
          </div>
        </div>
      </div>

      <div v-if="error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        {{ error }}
      </div>
      <div v-if="success" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
        {{ success }}
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <form
          class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4 self-start"
          data-testid="point-form"
          @submit.prevent="submit"
        >
          <div class="flex items-center justify-between">
            <h2 class="font-bold text-[#0F172A]">{{ isEditing ? 'Modifier le point' : 'Ajouter un point' }}</h2>
            <button
              v-if="isEditing"
              type="button"
              class="text-xs font-semibold text-slate-500 hover:text-slate-800"
              @click="cancelEdit"
            >
              Annuler
            </button>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="point-city">Ville</label>
            <select
              id="point-city"
              v-model="form.city_id"
              required
              class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
            >
              <option disabled value="">Choisir une ville</option>
              <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
            </select>
            <p v-if="fieldErrors.city_id" class="text-xs text-red-500 mt-1">{{ fieldErrors.city_id[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="point-name">Nom</label>
            <input
              id="point-name"
              v-model="form.name"
              required
              type="text"
              placeholder="Ex: Agence Anfa"
              class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
            />
            <p v-if="fieldErrors.name" class="text-xs text-red-500 mt-1">{{ fieldErrors.name[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="point-address">Adresse</label>
            <input
              id="point-address"
              v-model="form.address"
              required
              type="text"
              placeholder="Ex: Boulevard d'Anfa, Casablanca"
              class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
            />
            <p v-if="fieldErrors.address" class="text-xs text-red-500 mt-1">{{ fieldErrors.address[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="point-instructions">
              Instructions pour le client <span class="text-slate-400 font-normal">(optionnel)</span>
            </label>
            <textarea
              id="point-instructions"
              v-model="form.instructions"
              rows="2"
              placeholder="Ex: Parking niveau -1, présentez votre pièce d'identité."
              class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
            ></textarea>
          </div>

          <div class="flex flex-wrap gap-4 text-sm">
            <label class="inline-flex items-center gap-2 font-semibold text-slate-700">
              <input v-model="form.allows_pickup" type="checkbox" class="rounded border-slate-300" />
              Prise en charge
            </label>
            <label class="inline-flex items-center gap-2 font-semibold text-slate-700">
              <input v-model="form.allows_return" type="checkbox" class="rounded border-slate-300" />
              Restitution
            </label>
          </div>
          <p v-if="fieldErrors.allows_pickup" class="text-xs text-red-500 -mt-2">{{ fieldErrors.allows_pickup[0] }}</p>

          <button
            type="submit"
            class="w-full px-5 py-2.5 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50"
            :disabled="saving"
          >
            <template v-if="saving">Enregistrement…</template>
            <template v-else>{{ isEditing ? 'Enregistrer les modifications' : 'Créer le point' }}</template>
          </button>
        </form>

        <section class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-bold text-[#0F172A]">Mes points ({{ visiblePoints.length }})</h2>
            <div class="inline-flex rounded-xl border border-slate-200 p-0.5 text-xs font-semibold">
              <button
                v-for="opt in [
                  { value: 'all', label: 'Tous' },
                  { value: 'active', label: 'Actifs' },
                  { value: 'inactive', label: 'Inactifs' },
                ]"
                :key="opt.value"
                type="button"
                class="px-3 py-1.5 rounded-lg"
                :class="filter === opt.value ? 'bg-[#0F172A] text-white' : 'text-slate-500 hover:text-slate-800'"
                @click="filter = opt.value"
              >
                {{ opt.label }}
              </button>
            </div>
          </div>

          <div v-if="loading" class="text-sm text-slate-500 py-12 text-center">Chargement…</div>
          <div
            v-else-if="visiblePoints.length === 0"
            class="text-sm text-slate-500 py-12 text-center"
          >
            <p v-if="points.length === 0">
              Aucun point pour le moment. Sans point actif, vos véhicules ne peuvent pas être réservés.
            </p>
            <p v-else>Aucun point dans ce filtre.</p>
          </div>
          <ul v-else class="divide-y divide-slate-100">
            <li
              v-for="point in visiblePoints"
              :key="point.id"
              class="p-5 flex flex-col sm:flex-row sm:items-center gap-4"
              :class="{ 'bg-slate-50/60': !point.is_active }"
              data-testid="point-item"
            >
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="font-bold text-sm text-[#0F172A]">{{ point.name }}</p>
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                    :class="point.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600'"
                  >
                    {{ point.is_active ? 'Actif' : 'Inactif' }}
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                  📍 {{ point.address }}<span v-if="cityName(point)"> · {{ cityName(point) }}</span>
                </p>
                <p v-if="point.instructions" class="text-xs text-slate-400 mt-1 italic">{{ point.instructions }}</p>
                <div class="flex gap-2 mt-2">
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                    :class="point.allows_pickup ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-400 line-through'"
                  >
                    Prise en charge
                  </span>
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                    :class="point.allows_return ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-400 line-through'"
                  >
                    Restitution
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                  @click="startEdit(point)"
                >
                  Modifier
                </button>
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold disabled:opacity-50"
                  :class="point.is_active
                    ? 'border border-rose-200 text-rose-600 hover:bg-rose-50'
                    : 'border border-emerald-200 text-emerald-700 hover:bg-emerald-50'"
                  :disabled="togglingId === point.id"
                  @click="toggle(point)"
                >
                  <template v-if="togglingId === point.id">…</template>
                  <template v-else>{{ point.is_active ? 'Désactiver' : 'Réactiver' }}</template>
                </button>
              </div>
            </li>
          </ul>
        </section>
      </div>
    </div>
  </AgencyLayout>
</template>
