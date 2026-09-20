<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import api from '@/services/api'

/**
 * Search bar shared by the home page and the catalog.
 * v-model = { q, city_id, start_at, end_at }
 */
const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  compact: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'search'])

const cities = ref([])

const local = reactive({
  q: props.modelValue.q || '',
  city_id: props.modelValue.city_id || '',
  start_at: props.modelValue.start_at || '',
  end_at: props.modelValue.end_at || '',
})

watch(
  () => props.modelValue,
  (value) => {
    local.q = value?.q || ''
    local.city_id = value?.city_id || ''
    local.start_at = value?.start_at || ''
    local.end_at = value?.end_at || ''
  },
  { deep: true }
)

const minStart = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const dateError = computed(() => {
  if (!local.start_at || !local.end_at) return ''
  return new Date(local.end_at) <= new Date(local.start_at)
    ? 'La date de retour doit être après le départ.'
    : ''
})

function extractCities(data) {
  if (Array.isArray(data?.cities)) return data.cities
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data)) return data
  return []
}

async function loadCities() {
  try {
    cities.value = extractCities(await api.get('/cities'))
  } catch {
    cities.value = []
  }
}

function submit() {
  if (dateError.value) return
  const value = {
    q: local.q.trim(),
    city_id: local.city_id,
    start_at: local.start_at,
    end_at: local.end_at,
  }
  emit('update:modelValue', value)
  emit('search', value)
}

onMounted(loadCities)
</script>

<template>
  <form
    class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-900/5 p-3 sm:p-4"
    :class="compact ? '' : 'lg:rounded-full lg:pl-6'"
    data-testid="car-search-bar"
    @submit.prevent="submit"
  >
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
      <label class="md:col-span-3 block">
        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Où</span>
        <select
          v-model="local.city_id"
          class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-[#0F172A] bg-white"
          data-testid="search-city"
        >
          <option value="">Toutes les villes</option>
          <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
        </select>
      </label>

      <label class="md:col-span-3 block">
        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Départ</span>
        <input
          v-model="local.start_at"
          type="datetime-local"
          :min="minStart"
          class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-[#0F172A]"
          data-testid="search-start"
        />
      </label>

      <label class="md:col-span-3 block">
        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Retour</span>
        <input
          v-model="local.end_at"
          type="datetime-local"
          :min="local.start_at || minStart"
          class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-[#0F172A]"
          :class="{ 'border-rose-400': dateError }"
          data-testid="search-end"
        />
      </label>

      <label class="md:col-span-2 block">
        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Véhicule</span>
        <input
          v-model="local.q"
          type="search"
          placeholder="Marque, modèle…"
          class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-[#0F172A]"
          data-testid="search-q"
        />
      </label>

      <button
        type="submit"
        class="md:col-span-1 h-[42px] rounded-xl bg-[#0F172A] text-white text-sm font-bold hover:opacity-90 disabled:opacity-50 flex items-center justify-center gap-2"
        :disabled="!!dateError"
        aria-label="Rechercher"
        data-testid="search-submit"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
        </svg>
        <span class="md:hidden">Rechercher</span>
      </button>
    </div>
    <p v-if="dateError" class="text-xs text-rose-600 mt-2 px-1">{{ dateError }}</p>
  </form>
</template>
