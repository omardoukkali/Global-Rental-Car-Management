<template>
  <AppLayout>
    <div class="fade-in">
      <!-- Hero Header Section -->
      <header class="text-center py-16 px-6 max-w-4xl mx-auto">
        <span class="inline-block bg-indigo-500/10 text-indigo-400 text-xs font-bold uppercase tracking-widest px-3 py-1.5 rounded-full mb-4">
          {{ $t('hero.tagline') }}
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold text-slate-100 tracking-tight leading-none mb-6">
          {{ $t('hero.title') }}
        </h1>
        <p class="text-slate-400 text-lg md:text-xl leading-relaxed mb-0">
          {{ $t('hero.desc') }}
        </p>
      </header>

      <!-- Advanced Filter Panel Grid -->
      <section class="glass-card p-6 md:p-8 rounded-xl mb-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Text Search Debounced -->
          <div class="form-group mb-0">
            <label class="form-label text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 select-none">
              {{ $t('nav.browse') }}
            </label>
            <input
              type="text"
              v-model="search"
              placeholder="Search make or model..."
              class="form-input bg-slate-950 border border-white/5 text-slate-200 text-sm rounded-lg px-4 py-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/10 outline-none w-full"
            />
          </div>

          <!-- City Dropdown Immediate -->
          <div class="form-group mb-0">
            <label class="form-label text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 select-none">
              {{ $t('filter.city') }}
            </label>
            <select
              v-model="cityId"
              class="form-input bg-slate-950 border border-white/5 text-slate-200 text-sm rounded-lg px-4 py-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/10 outline-none w-full cursor-pointer"
            >
              <option value="">{{ $t('filter.anywhere') }}</option>
              <option v-for="city in cities" :key="city.id" :value="city.id">
                {{ city.name }}
              </option>
            </select>
          </div>

          <!-- Class/Type Dropdown Immediate -->
          <div class="form-group mb-0">
            <label class="form-label text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 select-none">
              {{ $t('filter.class') }}
            </label>
            <select
              v-model="type"
              class="form-input bg-slate-950 border border-white/5 text-slate-200 text-sm rounded-lg px-4 py-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/10 outline-none w-full cursor-pointer"
            >
              <option value="">{{ $t('filter.allClasses') }}</option>
              <option value="sedan">Sedan</option>
              <option value="suv">SUV</option>
              <option value="hatchback">Hatchback</option>
              <option value="coupe">Coupe</option>
              <option value="van">Van</option>
              <option value="truck">Truck</option>
            </select>
          </div>

          <!-- Transmission Dropdown Immediate -->
          <div class="form-group mb-0">
            <label class="form-label text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5 select-none">
              {{ $t('filter.gearbox') }}
            </label>
            <select
              v-model="transmission"
              class="form-input bg-slate-950 border border-white/5 text-slate-200 text-sm rounded-lg px-4 py-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/10 outline-none w-full cursor-pointer"
            >
              <option value="">{{ $t('filter.anyGearbox') }}</option>
              <option value="automatic">{{ $t('filter.automatic') }}</option>
              <option value="manual">{{ $t('filter.manual') }}</option>
            </select>
          </div>
        </div>

        <hr class="border-white/5 my-6" />

        <!-- Price Range Slider Debounced & Clear button -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="flex-grow max-w-xl">
            <div class="flex justify-between text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
              <span>{{ $t('filter.budget') }}</span>
              <span class="text-indigo-400 font-bold">${{ maxPrice || 1000 }} / {{ $t('landing.day') }}</span>
            </div>
            <input
              type="range"
              min="10"
              max="1000"
              step="10"
              v-model="maxPrice"
              class="w-full h-1.5 bg-slate-950 rounded-lg appearance-none cursor-pointer accent-indigo-500"
            />
          </div>

          <button
            @click="resetFilters"
            class="btn btn-secondary px-6 py-2.5 text-sm flex items-center justify-center gap-2 self-end shrink-0"
          >
            <RefreshCwIcon class="w-4 h-4" />
            {{ $t('filter.reset') }}
          </button>
        </div>
      </section>

      <!-- Fleet Header -->
      <div class="flex items-end justify-between border-b border-white/5 pb-4 mb-8">
        <div>
          <h2 class="text-2xl font-bold text-slate-100">{{ $t('landing.fleet') }}</h2>
          <p class="text-sm text-slate-400 mt-1">{{ $t('landing.sub') }}</p>
        </div>
        <span class="text-xs font-bold bg-white/5 text-slate-300 px-3 py-1 rounded-full select-none">
          {{ cars.total }} {{ cars.total === 1 ? 'Car' : 'Cars' }}
        </span>
      </div>

      <!-- Cars Grid Catalog -->
      <div v-if="cars.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <article
          v-for="car in cars.data"
          :key="car.id"
          class="glass-card overflow-hidden group flex flex-col h-full rounded-xl"
        >
          <!-- Car Image Card Cover -->
          <div class="relative aspect-[16/10] bg-slate-900 border-b border-white/5 overflow-hidden">
            <img
              :src="getPrimaryImage(car)"
              :alt="car.brand + ' ' + car.model"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <span class="absolute top-4 left-4 badge bg-slate-950/70 backdrop-blur border border-white/5 px-2.5 py-1 text-slate-200 font-semibold rounded">
              {{ car.type }}
            </span>
            <div v-if="car.avg_rating" class="absolute top-4 right-4 flex items-center gap-1 bg-amber-500/90 text-slate-950 font-bold px-2 py-0.5 rounded text-xs select-none shadow">
              <StarIcon class="w-3.5 h-3.5 fill-slate-950" />
              <span>{{ parseFloat(car.avg_rating).toFixed(1) }}</span>
            </div>
          </div>

          <!-- Details Body -->
          <div class="p-6 flex flex-col flex-grow">
            <div class="mb-4">
              <h3 class="text-xl font-bold text-slate-100 group-hover:text-indigo-400 transition-colors duration-150">
                {{ car.brand }} {{ car.model }}
              </h3>
              <p class="text-xs text-indigo-400 font-semibold mt-1 flex items-center gap-1">
                <MapPinIcon class="w-3.5 h-3.5" />
                {{ car.city ? car.city.name : 'Unknown City' }}
              </p>
            </div>

            <!-- Features Grid Row -->
            <div class="grid grid-cols-3 gap-3 border-t border-white/5 py-4 mb-5 text-center text-xs text-slate-300">
              <div class="bg-white/5 py-2 rounded-lg">
                <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-0.5">Year</span>
                <span class="font-bold">{{ car.year }}</span>
              </div>
              <div class="bg-white/5 py-2 rounded-lg">
                <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-0.5">Gear</span>
                <span class="font-bold capitalize">{{ car.transmission }}</span>
              </div>
              <div class="bg-white/5 py-2 rounded-lg">
                <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-0.5">Seats</span>
                <span class="font-bold">{{ car.seats }} Seats</span>
              </div>
            </div>

            <!-- Card Bottom Price & Action -->
            <div class="mt-auto pt-4 border-t border-white/5 flex items-center justify-between">
              <div>
                <span class="text-2xl font-extrabold text-slate-100">${{ parseFloat(car.price_per_day).toFixed(0) }}</span>
                <span class="text-xs text-slate-400"> / {{ $t('landing.day') }}</span>
              </div>
              
              <Link
                :href="route('cars.show', car.id)"
                class="btn btn-primary btn-sm flex items-center gap-1.5 px-4 py-2 font-bold"
              >
                {{ $t('landing.rent') }}
                <ChevronRightIcon class="w-4 h-4" :class="{ 'rotate-180': isRtl }" />
              </Link>
            </div>
          </div>
        </article>
      </div>

      <!-- Empty State View -->
      <div v-else class="text-center py-20 bg-slate-900/10 border border-dashed border-white/5 rounded-xl">
        <CarIcon class="w-12 h-12 text-slate-600 mx-auto mb-4" />
        <h3 class="text-lg font-bold text-slate-300">{{ $t('landing.noCars') }}</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 leading-relaxed">
          {{ $t('landing.noCarsSub') }}
        </p>
      </div>

      <!-- Pagination Block -->
      <Pagination :links="cars.links" />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useSettings } from '@/Composables/useSettings';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import {
  Car as CarIcon,
  Star as StarIcon,
  MapPin as MapPinIcon,
  RefreshCw as RefreshCwIcon,
  ChevronRight as ChevronRightIcon
} from 'lucide-vue-next';

const props = defineProps({
  cars: {
    type: Object,
    required: true
  },
  cities: {
    type: Array,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

const { isRtl } = useSettings();

// Pre-fill states from server-provided filters
const search = ref(props.filters.search || '');
const cityId = ref(props.filters.city_id || '');
const type = ref(props.filters.type || '');
const transmission = ref(props.filters.transmission || '');
const maxPrice = ref(props.filters.max_price || '');

let timeout = null;

const triggerSearch = (immediate = false) => {
  clearTimeout(timeout);
  const run = () => {
    router.get('/', {
      search: search.value,
      city_id: cityId.value,
      type: type.value,
      transmission: transmission.value,
      max_price: maxPrice.value,
    }, {
      preserveState: true,
      preserveScroll: true
    });
  };

  if (immediate) {
    run();
  } else {
    timeout = setTimeout(run, 300);
  }
};

// Custom watchers: text inputs debounce (300ms), dropdown changes fire immediately
watch(search, () => triggerSearch(false));
watch(maxPrice, () => triggerSearch(false));

watch(cityId, () => triggerSearch(true));
watch(type, () => triggerSearch(true));
watch(transmission, () => triggerSearch(true));

const resetFilters = () => {
  search.value = '';
  cityId.value = '';
  type.value = '';
  transmission.value = '';
  maxPrice.value = '';
  triggerSearch(true);
};

// Fallback image helper
const getPrimaryImage = (car) => {
  if (car.images && car.images.length > 0) {
    const primary = car.images.find(img => img.is_primary);
    return primary ? primary.url : car.images[0].url;
  }
  return 'https://images.unsplash.com/photo-1542282088-fe8426682b8f?auto=format&fit=crop&q=80&w=800'; // Premium default fallback car image
};
</script>
