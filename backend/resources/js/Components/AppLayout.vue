<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col" :dir="isRtl ? 'rtl' : 'ltr'">
    <!-- Navbar Header -->
    <nav class="navbar sticky top-0 z-50 bg-slate-950/80 backdrop-blur-md border-b border-white/5 py-4">
      <div class="max-w-7xl mx-auto px-6 flex-between">
        <Link href="/" class="navbar-brand flex items-center gap-2 text-2xl font-bold text-slate-100">
          <CarIcon class="w-7 h-7 text-indigo-500" />
          <span>GlobalRental</span>
        </Link>

        <ul class="flex items-center gap-6">
          <li>
            <Link href="/" class="hover:text-indigo-400 font-medium transition-colors" :class="{ 'text-indigo-400': $page.url === '/' }">
              {{ $t('nav.browse') }}
            </Link>
          </li>

          <!-- Guest Nav -->
          <template v-if="!user">
            <li>
              <Link href="/login" class="btn btn-secondary btn-sm">
                {{ $t('nav.login') }}
              </Link>
            </li>
            <li>
              <Link href="/register" class="btn btn-primary btn-sm">
                {{ $t('nav.register') }}
              </Link>
            </li>
          </template>

          <!-- Client Nav -->
          <template v-else-if="user.role === 'client'">
            <li>
              <Link href="/client/reservations" class="hover:text-indigo-400 font-medium transition-colors" :class="{ 'text-indigo-400': $page.url.startsWith('/client') }">
                {{ $t('nav.bookings') }}
              </Link>
            </li>
            <li class="flex items-center">
              <span class="badge badge-active flex items-center gap-1.5 px-3 py-1 font-semibold">
                <UserIcon class="w-3.5 h-3.5" />
                {{ user.first_name }}
              </span>
            </li>
          </template>

          <!-- Owner Nav -->
          <template v-else-if="user.role === 'agency_owner'">
            <li>
              <Link href="/owner/dashboard" class="hover:text-indigo-400 font-medium transition-colors flex items-center gap-1.5" :class="{ 'text-indigo-400': $page.url.startsWith('/owner') }">
                <LayoutDashboardIcon class="w-4 h-4" />
                {{ $t('nav.dashboard') }}
              </Link>
            </li>
            <li class="flex items-center">
              <span class="badge badge-picked-up bg-indigo-500/10 text-indigo-400 flex items-center gap-1.5 px-3 py-1 font-semibold">
                <LandmarkIcon class="w-3.5 h-3.5" />
                {{ user.agency ? user.agency.name : 'Agency' }}
              </span>
            </li>
          </template>

          <!-- Admin Nav -->
          <template v-else-if="user.role === 'admin'">
            <li>
              <Link href="/admin/dashboard" class="hover:text-indigo-400 font-medium transition-colors flex items-center gap-1.5" :class="{ 'text-indigo-400': $page.url.startsWith('/admin') }">
                <ShieldIcon class="w-4 h-4" />
                {{ $t('nav.admin') }}
              </Link>
            </li>
            <li class="flex items-center">
              <span class="badge badge-cancelled flex items-center gap-1.5 px-3 py-1 font-semibold">
                <ShieldIcon class="w-3.5 h-3.5" />
                Admin
              </span>
            </li>
          </template>

          <!-- Logout Button -->
          <li v-if="user">
            <button @click="logout" class="btn btn-secondary btn-sm flex-center p-2" :title="$t('nav.logout')">
              <LogOutIcon class="w-4 h-4" />
            </button>
          </li>

          <!-- Language Dropdown -->
          <li class="flex items-center gap-1.5 relative">
            <GlobeIcon class="w-4 h-4 text-slate-400" />
            <select
              :value="locale"
              @change="changeLang($event.target.value)"
              class="bg-transparent border border-white/10 text-slate-200 text-xs font-semibold rounded px-1.5 py-1 outline-none cursor-pointer hover:border-white/20 transition-colors"
            >
              <option value="en" class="bg-slate-900 text-slate-200">EN</option>
              <option value="fr" class="bg-slate-900 text-slate-200">FR</option>
              <option value="ar" class="bg-slate-900 text-slate-200">AR</option>
            </select>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Main Content Slot -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-6 py-8">
      <slot />
    </main>

    <!-- Global Floating Toasts List -->
    <div class="fixed bottom-6 z-50 flex flex-col gap-3" :class="isRtl ? 'left-6' : 'right-6'" style="max-width: 320px;">
      <transition-group name="toast-fade">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="glass-card flex items-start gap-3 p-4 rounded-lg shadow-lg border animate-slide"
          :class="{
            'border-emerald-500/30 bg-emerald-950/20': toast.type === 'success',
            'border-red-500/30 bg-red-950/20': toast.type === 'error'
          }"
        >
          <CheckCircle2Icon v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" />
          <AlertCircleIcon v-else class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
          <div>
            <p class="text-sm font-semibold" :class="toast.type === 'success' ? 'text-emerald-300' : 'text-red-300'">
              {{ toast.type === 'success' ? 'Success' : 'Error' }}
            </p>
            <p class="text-xs text-slate-300 mt-0.5 leading-relaxed">{{ toast.message }}</p>
          </div>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { useSettings } from '@/Composables/useSettings';
import { router, usePage, Link } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import {
  Car as CarIcon,
  LogOut as LogOutIcon,
  User as UserIcon,
  Landmark as LandmarkIcon,
  Shield as ShieldIcon,
  LayoutDashboard as LayoutDashboardIcon,
  Globe as GlobeIcon,
  AlertCircle as AlertCircleIcon,
  CheckCircle2 as CheckCircle2Icon
} from 'lucide-vue-next';

const { locale, isRtl, setLanguage } = useSettings();
const i18n = useI18n();
const page = usePage();

const user = computed(() => page.props.auth?.user);

const toasts = ref([]);
let toastId = 0;

const addToast = (message, type = 'success') => {
  const id = toastId++;
  toasts.value.push({ id, message, type });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 4000);
};

const changeLang = (lang) => {
  setLanguage(lang);
  i18n.locale.value = lang;
};

const logout = () => {
  router.post('/auth/logout');
};

// Sync HTML dir/lang parameters on mounted
onMounted(() => {
  document.documentElement.lang = locale.value;
  document.documentElement.dir = isRtl.value ? 'rtl' : 'ltr';
});

// Watch for flash notifications shared by Laravel session redirect
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      addToast(flash.success, 'success');
      flash.success = null; // Consume flash prop
    }
    if (flash?.error) {
      addToast(flash.error, 'error');
      flash.error = null;
    }
  },
  { deep: true, immediate: true }
);
</script>

<style scoped>
.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: all 0.3s ease;
}
.toast-fade-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.9);
}
.toast-fade-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>
