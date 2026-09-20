<script setup>
import { ref, onMounted } from 'vue'
import AgencyLayout from '@/components/AgencyLayout.vue'
import agencyService from '@/services/agency'
import api from '@/services/api'

const points = ref([])
const cities = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')

const form = ref({
  city_id: '',
  name: '',
  address: '',
  allows_pickup: true,
  allows_return: true,
})

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

async function createPoint() {
  if (saving.value) return
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    await agencyService.createPoint({
      city_id: form.value.city_id,
      name: form.value.name.trim(),
      address: form.value.address.trim(),
      allows_pickup: !!form.value.allows_pickup,
      allows_return: !!form.value.allows_return,
    })
    success.value = 'Point créé. Il apparaîtra dans le formulaire de réservation.'
    form.value.name = ''
    form.value.address = ''
    form.value.allows_pickup = true
    form.value.allows_return = true
    await load()
  } catch (err) {
    error.value = err?.message || 'Échec de la création du point.'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <AgencyLayout>
    <div class="max-w-3xl space-y-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight font-bricolage">
          Points de retrait & restitution
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          Ces lieux alimentent les listes « Prise en charge » et « Restitution » du formulaire client.
        </p>
      </div>

      <div
        v-if="error"
        class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"
      >
        {{ error }}
      </div>
      <div
        v-if="success"
        class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm"
      >
        {{ success }}
      </div>

      <form
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4"
        @submit.prevent="createPoint"
      >
        <h2 class="font-bold text-[#0F172A]">Ajouter un point</h2>

        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="point-city">Ville</label>
          <select
            id="point-city"
            v-model="form.city_id"
            required
            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
          >
            <option disabled value="">Choisir une ville</option>
            <option v-for="city in cities" :key="city.id" :value="city.id">
              {{ city.name }}
            </option>
          </select>
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

        <button
          type="submit"
          class="px-5 py-2.5 rounded-xl bg-[#0F172A] text-white text-sm font-semibold disabled:opacity-50"
          :disabled="saving"
        >
          {{ saving ? 'Création…' : 'Créer le point' }}
        </button>
      </form>

      <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-[#0F172A] mb-4">
          Points existants ({{ points.length }})
        </h2>

        <div v-if="loading" class="text-sm text-slate-500 py-6 text-center">Chargement…</div>
        <div
          v-else-if="points.length === 0"
          class="text-sm text-slate-500 py-6 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200"
        >
          Aucun point pour le moment.
        </div>
        <ul v-else class="space-y-3">
          <li
            v-for="point in points"
            :key="point.id"
            class="p-4 rounded-xl border border-slate-200 bg-slate-50/50"
          >
            <p class="font-bold text-sm text-[#0F172A]">{{ point.name }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ point.address }}</p>
            <p class="text-[11px] text-slate-400 mt-2">
              Pickup: {{ point.allows_pickup ? 'oui' : 'non' }}
              · Return: {{ point.allows_return ? 'oui' : 'non' }}
              · {{ point.is_active ? 'Actif' : 'Inactif' }}
            </p>
          </li>
        </ul>
      </section>
    </div>
  </AgencyLayout>
</template>
