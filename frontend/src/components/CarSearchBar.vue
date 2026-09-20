<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import api from '@/services/api'
import DateRangeDropdown from '@/components/DateRangeDropdown.vue'

/**
 * Search bar shared by the home page and the catalog.
 * v-model = { q, city_id, start_at, end_at }  (start/end as YYYY-MM-DDTHH:mm)
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
  start: toDatePart(props.modelValue.start_at),
  end: toDatePart(props.modelValue.end_at),
  startTime: toTimePart(props.modelValue.start_at, '10:00'),
  endTime: toTimePart(props.modelValue.end_at, '10:00'),
})

watch(
  () => props.modelValue,
  (value) => {
    local.q = value?.q || ''
    local.city_id = value?.city_id || ''
    local.start = toDatePart(value?.start_at)
    local.end = toDatePart(value?.end_at)
    local.startTime = toTimePart(value?.start_at, local.startTime || '10:00')
    local.endTime = toTimePart(value?.end_at, local.endTime || '10:00')
  },
  { deep: true }
)

function toDatePart(value) {
  if (!value) return ''
  return String(value).slice(0, 10)
}

function toTimePart(value, fallback) {
  if (!value || !String(value).includes('T')) return fallback
  return String(value).slice(11, 16) || fallback
}

function toApiDateTime(date, time) {
  if (!date) return ''
  return `${date}T${time || '10:00'}`
}

const dateError = computed(() => {
  if (!local.start || !local.end) return ''
  const start = `${local.start}T${local.startTime}`
  const end = `${local.end}T${local.endTime}`
  return end <= start ? 'Le retour doit être après le départ.' : ''
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
    start_at: toApiDateTime(local.start, local.startTime),
    end_at: toApiDateTime(local.end, local.endTime),
  }
  emit('update:modelValue', value)
  emit('search', value)
}

onMounted(loadCities)
</script>

<template>
  <form
    class="search-bar"
    data-testid="car-search-bar"
    @submit.prevent="submit"
  >
    <div
      class="search-grid"
      :class="compact ? 'is-compact' : 'is-hero'"
    >
      <label class="search-cell">
        <span class="search-label">Ville</span>
        <select
          v-model="local.city_id"
          class="search-control"
          data-testid="search-city"
        >
          <option value="">Toutes les villes</option>
          <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
        </select>
      </label>

      <DateRangeDropdown
        v-model:start-date="local.start"
        v-model:end-date="local.end"
        v-model:start-time="local.startTime"
        v-model:end-time="local.endTime"
      />

      <label class="search-cell">
        <span class="search-label">Véhicule</span>
        <input
          v-model="local.q"
          type="search"
          placeholder="Marque ou modèle"
          class="search-control"
          data-testid="search-q"
        />
      </label>

      <div class="submit-wrap">
        <button
          type="submit"
          class="search-submit"
          :disabled="!!dateError"
          aria-label="Rechercher"
          data-testid="search-submit"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
          </svg>
          Rechercher
        </button>
      </div>
    </div>
    <p v-if="dateError" class="error">{{ dateError }}</p>
  </form>
</template>

<style scoped>
.search-bar {
  position: relative;
  background: #fff;
  border-radius: 18px;
  border: 1px solid #E2E8F0;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
  padding: 8px;
}
.search-grid {
  display: grid;
  gap: 4px;
  align-items: stretch;
}
.search-grid.is-hero,
.search-grid.is-compact {
  grid-template-columns: 1fr;
}
@media (min-width: 640px) {
  .search-grid.is-hero,
  .search-grid.is-compact {
    grid-template-columns: 1fr 1.6fr 1fr auto;
  }
}
.search-cell {
  display: block;
  padding: 10px 16px;
  border-radius: 14px;
  min-width: 0;
}
.search-cell:hover { background: #F8FAFC; }
.search-label {
  display: block;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #94A3B8;
  margin-bottom: 4px;
}
.search-control {
  width: 100%;
  border: 0;
  background: transparent;
  padding: 0;
  font-size: 15px;
  line-height: 1.4;
  font-weight: 600;
  color: #0F172A;
  outline: none;
  min-width: 0;
}
.search-control::placeholder {
  color: #94A3B8;
  font-weight: 500;
}
.submit-wrap {
  display: flex;
  align-items: flex-end;
  padding: 6px;
}
.search-submit {
  width: 100%;
  height: 48px;
  padding: 0 22px;
  border-radius: 14px;
  background: #0F172A;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  white-space: nowrap;
  border: 0;
  cursor: pointer;
}
.search-submit:hover { background: #1e293b; }
.search-submit:disabled { opacity: 0.5; cursor: not-allowed; }
.error {
  font-size: 12px;
  color: #E11D48;
  padding: 0 12px 6px;
}
</style>
