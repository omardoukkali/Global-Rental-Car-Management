<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  car: { type: Object, required: true },
  query: { type: Object, default: () => ({}) },
})

const image = computed(() => {
  const images = props.car?.images || []
  const primary = images.find((img) => img.is_primary) || images[0]
  return primary?.url || primary?.image_url || null
})

const title = computed(() => `${props.car.brand || ''} ${props.car.model || ''}`.trim())

const to = computed(() => {
  const query = {}
  if (props.query.start_at && props.query.end_at) {
    query.start_at = props.query.start_at
    query.end_at = props.query.end_at
  }
  return { path: `/cars/${props.car.id}`, query }
})

const days = computed(() => {
  if (!props.query.start_at || !props.query.end_at) return 0
  const diff = new Date(props.query.end_at) - new Date(props.query.start_at)
  if (!(diff > 0)) return 0
  return Math.ceil(diff / 86400000)
})

const total = computed(() => days.value * Number(props.car.daily_price || 0))

function capitalize(value) {
  if (!value) return ''
  return String(value).charAt(0).toUpperCase() + String(value).slice(1)
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('fr-MA')
}
</script>

<template>
  <RouterLink :to="to" class="group block" data-testid="public-car-card">
    <div class="relative aspect-[4/3] rounded-2xl overflow-hidden bg-slate-100 border border-slate-200">
      <img
        v-if="image"
        :src="image"
        :alt="title"
        class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300"
        loading="lazy"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-sm font-semibold">
        Photo indisponible
      </div>
      <span
        v-if="car.images?.length > 1"
        class="absolute bottom-2 right-2 px-2 py-0.5 rounded-md bg-white/90 text-[11px] font-bold text-slate-700"
      >
        {{ car.images.length }} photos
      </span>
    </div>

    <div class="pt-3 px-0.5">
      <div class="flex items-start justify-between gap-2">
        <h3 class="font-bricolage font-bold text-[#0F172A] leading-tight">
          {{ title }}
          <span v-if="car.year" class="text-slate-400 font-semibold text-sm">{{ car.year }}</span>
        </h3>
        <span
          v-if="car.agency?.avg_rating"
          class="text-sm font-bold text-[#0F172A] shrink-0"
        >
          {{ Number(car.agency.avg_rating).toFixed(1) }} <span class="text-amber-500">★</span>
        </span>
      </div>

      <p class="text-xs text-slate-500 mt-1 flex flex-wrap gap-x-1.5">
        <span v-if="car.type">{{ capitalize(car.type) }}</span>
        <span v-if="car.transmission">· {{ car.transmission === 'automatic' ? 'Automatique' : 'Manuelle' }}</span>
        <span v-if="car.seats">· {{ car.seats }} places</span>
        <span v-if="car.city?.name">· {{ car.city.name }}</span>
      </p>

      <p class="mt-2 text-sm text-[#0F172A]">
        <strong class="font-extrabold">{{ formatMoney(car.daily_price) }} MAD</strong>
        <span class="text-slate-500"> / jour</span>
        <span v-if="days" class="text-slate-500"> · {{ formatMoney(total) }} MAD pour {{ days }} j</span>
      </p>
      <p v-if="car.agency?.name" class="text-xs text-slate-400 mt-0.5 truncate">{{ car.agency.name }}</p>
    </div>
  </RouterLink>
</template>
