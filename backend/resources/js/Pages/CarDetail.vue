<template>
  <AppLayout>
    <div class="fade-in max-w-6xl mx-auto py-6">
      <!-- Back to Fleet Link -->
      <Link href="/" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-indigo-400 transition-colors mb-6">
        <ArrowLeftIcon class="w-4 h-4" />
        {{ $t('carDetail.back') }}
      </Link>

      <!-- Split Layout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Side Column (2/3 width on large screens) -->
        <div class="lg:col-span-2 flex flex-col gap-8">
          <!-- Interactive Image Gallery Cover -->
          <div class="glass-card p-4 rounded-xl overflow-hidden">
            <div class="relative aspect-[16/10] bg-slate-950 rounded-lg overflow-hidden mb-4 border border-white/5">
              <img
                :src="activeImage"
                :alt="car.brand + ' ' + car.model"
                class="w-full h-full object-cover"
              />
            </div>
            <!-- Thumbnails List -->
            <div v-if="car.images && car.images.length > 1" class="flex gap-3 overflow-x-auto pb-1">
              <button
                v-for="img in car.images"
                :key="img.id"
                @click="activeImage = img.url"
                class="w-20 h-14 rounded overflow-hidden border-2 transition-all shrink-0"
                :class="activeImage === img.url ? 'border-indigo-500 scale-95' : 'border-white/10 opacity-70 hover:opacity-100'"
              >
                <img :src="img.url" class="w-full h-full object-cover" />
              </button>
            </div>
          </div>

          <!-- Technical Specs Block -->
          <div class="glass-card p-6 rounded-xl">
            <h2 class="text-2xl font-bold text-slate-100 border-b border-white/5 pb-3 mb-4">
              {{ $t('carDetail.specs') }}
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-sm">
              <div class="bg-white/5 p-3.5 rounded-lg border border-white/5">
                <span class="block text-[10px] text-slate-500 uppercase font-semibold mb-1">{{ $t('carDetail.brand') }}</span>
                <span class="font-bold text-slate-200 text-base">{{ car.brand }}</span>
              </div>
              <div class="bg-white/5 p-3.5 rounded-lg border border-white/5">
                <span class="block text-[10px] text-slate-500 uppercase font-semibold mb-1">{{ $t('carDetail.model') }}</span>
                <span class="font-bold text-slate-200 text-base">{{ car.model }}</span>
              </div>
              <div class="bg-white/5 p-3.5 rounded-lg border border-white/5">
                <span class="block text-[10px] text-slate-500 uppercase font-semibold mb-1">{{ $t('carDetail.year') }}</span>
                <span class="font-bold text-slate-200 text-base">{{ car.year }}</span>
              </div>
              <div class="bg-white/5 p-3.5 rounded-lg border border-white/5">
                <span class="block text-[10px] text-slate-500 uppercase font-semibold mb-1">{{ $t('filter.class') }}</span>
                <span class="font-bold text-slate-200 capitalize text-base">{{ car.type }}</span>
              </div>
              <div class="bg-white/5 p-3.5 rounded-lg border border-white/5">
                <span class="block text-[10px] text-slate-500 uppercase font-semibold mb-1">{{ $t('filter.gearbox') }}</span>
                <span class="font-bold text-slate-200 capitalize text-base">{{ car.transmission }}</span>
              </div>
              <div class="bg-white/5 p-3.5 rounded-lg border border-white/5">
                <span class="block text-[10px] text-slate-500 uppercase font-semibold mb-1">Seats</span>
                <span class="font-bold text-slate-200 text-base">{{ car.seats }} Seats</span>
              </div>
              <div class="bg-white/5 p-3.5 rounded-lg border border-white/5">
                <span class="block text-[10px] text-slate-500 uppercase font-semibold mb-1">Fuel Type</span>
                <span class="font-bold text-slate-200 capitalize text-base">{{ car.fuel_type || 'Gasoline' }}</span>
              </div>
              <div class="bg-white/5 p-3.5 rounded-lg border border-white/5">
                <span class="block text-[10px] text-slate-500 uppercase font-semibold mb-1">Plate Number</span>
                <span class="font-bold text-slate-200 text-base font-mono uppercase">{{ car.plate_number }}</span>
              </div>
              <div class="bg-white/5 p-3.5 rounded-lg border border-white/5">
                <span class="block text-[10px] text-slate-500 uppercase font-semibold mb-1">City Hub</span>
                <span class="font-bold text-slate-200 text-base">{{ car.city ? car.city.name : 'Unknown' }}</span>
              </div>
            </div>

            <!-- Description Block -->
            <div v-if="car.description" class="mt-6 pt-6 border-t border-white/5">
              <span class="block text-xs text-slate-500 uppercase font-bold tracking-wider mb-2">Description</span>
              <p class="text-sm text-slate-300 leading-relaxed">{{ car.description }}</p>
            </div>
          </div>

          <!-- Verified Reviews Feed -->
          <div class="glass-card p-6 rounded-xl">
            <h2 class="text-2xl font-bold text-slate-100 border-b border-white/5 pb-3 mb-6">
              {{ $t('carDetail.reviews') }}
            </h2>
            <div v-if="reviews.length > 0" class="flex flex-col gap-6">
              <div v-for="rev in reviews" :key="rev.id" class="border-b border-white/5 last:border-b-0 pb-6 last:pb-0">
                <div class="flex items-center justify-between gap-4 mb-2">
                  <div>
                    <h4 class="font-bold text-slate-200 text-sm">
                      {{ rev.client ? (rev.client.first_name + ' ' + rev.client.last_name) : 'Verified Client' }}
                    </h4>
                    <span class="text-[10px] text-slate-500">{{ formatDate(rev.created_at) }}</span>
                  </div>
                  <StarRating :rating="parseFloat(rev.car_rating)" class="scale-90 origin-right" />
                </div>
                <p class="text-xs text-slate-400 italic leading-relaxed">{{ rev.comment || 'No comment provided' }}</p>
              </div>
            </div>
            <div v-else class="text-center py-8 text-slate-500 text-sm italic">
              {{ $t('carDetail.noReviews') }}
            </div>
          </div>
        </div>

        <!-- Right Side Sticky Booking Card -->
        <div class="lg:col-span-1">
          <div class="glass-card p-6 rounded-xl sticky top-24 border-indigo-500/10">
            <!-- Price Display Header -->
            <div class="flex items-end justify-between border-b border-white/5 pb-4 mb-6">
              <span class="text-sm text-slate-400 font-medium">{{ $t('carDetail.pricePerDay') }}</span>
              <div class="text-right">
                <span class="text-3xl font-extrabold text-slate-100">${{ parseFloat(car.price_per_day).toFixed(0) }}</span>
                <span class="text-xs text-slate-400"> / {{ $t('landing.day') }}</span>
              </div>
            </div>

            <!-- Booking Datepicker Inputs -->
            <form @submit.prevent="submitBooking">
              <FormField
                type="date"
                :label="$t('filter.pickup')"
                v-model="form.start_date"
                :error="form.errors.start_date"
                :min="todayDate"
                required
                :disabled="form.processing || !canBook"
              />

              <FormField
                type="date"
                :label="$t('filter.return')"
                v-model="form.end_date"
                :error="form.errors.end_date"
                :min="form.start_date || todayDate"
                required
                :disabled="form.processing || !canBook"
              />

              <!-- Price Summary Billing breakdown -->
              <div v-if="calculatedDays > 0" class="bg-indigo-600/5 border border-indigo-500/10 rounded-lg p-4 mb-6 animate-slide">
                <div class="flex justify-between text-xs text-slate-300 mb-2">
                  <span>${{ parseFloat(car.price_per_day).toFixed(0) }} x {{ calculatedDays }} days</span>
                  <span class="font-semibold">${{ calculatedTotal }}</span>
                </div>
                <div class="flex justify-between text-xs text-emerald-400 font-bold border-t border-white/5 pt-2">
                  <span>{{ $t('carDetail.freeCancel') }}</span>
                </div>
              </div>

              <!-- Action CTA -->
              <template v-if="user">
                <!-- If agency owner attempts to book -->
                <div v-if="user.role === 'agency_owner'" class="bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded-lg p-3 text-xs text-center select-none font-semibold mb-2">
                  {{ $t('carDetail.ownerCannotBook') }}
                </div>
                <!-- If user is client -->
                <button
                  v-else
                  type="submit"
                  class="btn btn-primary w-full flex items-center justify-center font-bold"
                  :disabled="form.processing || calculatedDays <= 0"
                >
                  {{ form.processing ? 'Requesting...' : $t('carDetail.bookNow') }}
                  <CalendarDaysIcon class="w-4 h-4 ml-1.5" />
                </button>
              </template>

              <!-- Guest Redirect login option -->
              <Link
                v-else
                href="/login"
                class="btn btn-primary w-full flex items-center justify-center font-bold"
              >
                {{ $t('carDetail.logInToBook') }}
                <UserIcon class="w-4 h-4 ml-1.5" />
              </Link>
            </form>

            <div class="mt-6 pt-6 border-t border-white/5 text-[11px] text-slate-500 flex items-center gap-1.5 justify-center">
              <ShieldCheckIcon class="w-4 h-4 text-slate-500" />
              <span>{{ $t('carDetail.rentedVia') }} {{ car.agency ? car.agency.name : 'Verified Agency' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import FormField from '@/Components/FormField.vue';
import StarRating from '@/Components/StarRating.vue';
import {
  ArrowLeft as ArrowLeftIcon,
  CalendarDays as CalendarDaysIcon,
  ShieldCheck as ShieldCheckIcon,
  User as UserIcon
} from 'lucide-vue-next';

const props = defineProps({
  car: {
    type: Object,
    required: true
  },
  reviews: {
    type: Array,
    default: () => []
  }
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const activeImage = ref(
  props.car.images && props.car.images.length > 0
    ? props.car.images.find(img => img.is_primary)?.url || props.car.images[0].url
    : 'https://images.unsplash.com/photo-1542282088-fe8426682b8f?auto=format&fit=crop&q=80&w=800'
);

const todayDate = computed(() => {
  const d = new Date();
  d.setDate(d.getDate() + 1); // Minimum booking date is tomorrow to comply with validation rules
  return d.toISOString().split('T')[0];
});

const form = useForm({
  car_id: props.car.id,
  start_date: '',
  end_date: '',
});

// Compute total booking days dynamically client-side
const calculatedDays = computed(() => {
  if (!form.start_date || !form.end_date) return 0;
  const start = new Date(form.start_date);
  const end = new Date(form.end_date);
  if (end <= start) return 0;
  const diffTime = Math.abs(end - start);
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const calculatedTotal = computed(() => {
  return calculatedDays.value * parseFloat(props.car.price_per_day);
});

const canBook = computed(() => {
  return !user.value || user.value.role === 'client';
});

const submitBooking = () => {
  form.post('/client/reservations', {
    onSuccess: () => {
      // Clear forms on success
      form.reset('start_date', 'end_date');
    }
  });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateStr).toLocaleDateString(undefined, options);
};
</script>
