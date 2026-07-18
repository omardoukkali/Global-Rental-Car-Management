<template>
  <AppLayout>
    <div class="register-container flex-center min-h-[80vh] py-12 px-6">
      <div class="card glass-card w-full max-w-[540px] p-8 text-center fade-in">
        <h2 class="register-title text-3xl font-extrabold mb-2">{{ titleText }}</h2>
        <p class="register-subtitle text-sm text-slate-400 mb-6">{{ subtitleText }}</p>

        <!-- Role Toggle Tabs -->
        <div class="role-tabs flex bg-slate-950 p-1 rounded-lg border border-white/5 mb-6">
          <button
            type="button"
            class="flex-grow flex items-center justify-center gap-2 py-2 text-sm font-semibold rounded-md transition-all"
            :class="role === 'client' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
            @click="role = 'client'"
          >
            <UserIcon class="w-4 h-4" /> {{ clientTab }}
          </button>
          <button
            type="button"
            class="flex-grow flex items-center justify-center gap-2 py-2 text-sm font-semibold rounded-md transition-all"
            :class="role === 'agency' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
            @click="role = 'agency'"
          >
            <LandmarkIcon class="w-4 h-4" /> {{ ownerTab }}
          </button>
        </div>

        <form @submit.prevent="submit" class="mt-4 text-left">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              type="text"
              :label="fNameLabel"
              v-model="form.first_name"
              :error="form.errors.first_name"
              placeholder="John"
              required
              :disabled="form.processing"
            />
            <FormField
              type="text"
              :label="lNameLabel"
              v-model="form.last_name"
              :error="form.errors.last_name"
              placeholder="Doe"
              required
              :disabled="form.processing"
            />
          </div>

          <FormField
            type="email"
            :label="emailLabel"
            v-model="form.email"
            :error="form.errors.email"
            placeholder="john.doe@example.com"
            required
            :disabled="form.processing"
          />

          <FormField
            type="text"
            :label="phoneLabel"
            v-model="form.phone"
            :error="form.errors.phone"
            placeholder="+123456789"
            required
            :disabled="form.processing"
          />

          <FormField
            type="password"
            :label="passLabel"
            v-model="form.password"
            :error="form.errors.password"
            placeholder="Min. 8 characters"
            required
            :disabled="form.processing"
          />

          <!-- Agency Onboarding Block -->
          <template v-if="role === 'agency'">
            <FormField
              type="password"
              :label="confirmPassLabel"
              v-model="form.password_confirmation"
              :error="form.errors.password_confirmation"
              placeholder="Repeat password"
              required
              :disabled="form.processing"
            />

            <hr class="border-white/5 my-6" />
            <h3 class="text-lg font-bold text-slate-200 mb-4">{{ agencyHeader }}</h3>

            <FormField
              type="text"
              :label="agencyNameLabel"
              v-model="form.agency_name"
              :error="form.errors.agency_name"
              placeholder="Apex Car Rental"
              required
              :disabled="form.processing"
            />

            <FormField
              type="select"
              :label="cityLabel"
              v-model="form.agency_city"
              :error="form.errors.agency_city"
              required
              :disabled="form.processing"
            >
              <option value="">{{ selectCityOpt }}</option>
              <option v-for="city in cities" :key="city.id" :value="city.id">
                {{ city.name }}
              </option>
            </FormField>

            <FormField
              type="text"
              :label="addressLabel"
              v-model="form.address"
              :error="form.errors.address"
              placeholder="123 Luxury Dr, Suite A"
              required
              :disabled="form.processing"
            />

            <FormField
              type="text"
              :label="agencyPhoneLabel"
              v-model="form.agency_phone"
              :error="form.errors.agency_phone"
              placeholder="+198765432"
              required
              :disabled="form.processing"
            />
          </template>

          <button type="submit" class="btn btn-primary w-full mt-6 flex items-center justify-center" :disabled="form.processing">
            {{ btnText }}
            <ChevronRightIcon class="w-4 h-4" :class="{ 'rotate-180': isRtl, 'ml-1': !isRtl, 'mr-1': isRtl }" />
          </button>
        </form>

        <div class="register-footer mt-6 pt-4 border-t border-white/5 text-sm text-slate-400">
          <p>
            {{ footerPrompt }} 
            <Link href="/login" class="text-indigo-400 hover:text-indigo-300 font-semibold transition-colors">
              {{ footerLinkText }}
            </Link>
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useSettings } from '@/Composables/useSettings';
import AppLayout from '@/Components/AppLayout.vue';
import FormField from '@/Components/FormField.vue';
import { User as UserIcon, Landmark as LandmarkIcon, ChevronRight as ChevronRightIcon } from 'lucide-vue-next';

defineProps({
  cities: {
    type: Array,
    default: () => []
  }
});

const { locale: language, isRtl } = useSettings();
const role = ref('client');

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  agency_name: '',
  agency_city: '',
  address: '',
  agency_phone: '',
});

const submit = () => {
  const path = role.value === 'client' ? '/register/client' : '/register/agency';
  form.post(path, {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};

// Inline translations
const titleText = computed(() => isRtl.value ? 'ابدأ الآن' : (language.value === 'fr' ? 'Commencer' : 'Get Started'));
const subtitleText = computed(() => {
  return isRtl.value
    ? 'أنشئ حساباً للانضمام للمنصة'
    : (language.value === 'fr' ? 'Créez un compte pour rejoindre la plateforme' : 'Create an account to join the platform');
});
const clientTab = computed(() => isRtl.value ? 'تسجيل كزبون' : (language.value === 'fr' ? 'Client' : 'Register as Client'));
const ownerTab = computed(() => isRtl.value ? 'تسجيل وكالة' : (language.value === 'fr' ? 'Enregistrer une Agence' : 'Register Agency'));

const fNameLabel = computed(() => isRtl.value ? 'الاسم الأول' : (language.value === 'fr' ? 'Prénom' : 'First Name'));
const lNameLabel = computed(() => isRtl.value ? 'الاسم الأخير' : (language.value === 'fr' ? 'Nom de famille' : 'Last Name'));
const emailLabel = computed(() => isRtl.value ? 'البريد الإلكتروني' : (language.value === 'fr' ? 'Adresse e-mail' : 'Email Address'));
const phoneLabel = computed(() => isRtl.value ? 'رقم الهاتف الشخصي' : (language.value === 'fr' ? 'Téléphone personnel' : 'Personal Phone Number'));
const passLabel = computed(() => isRtl.value ? 'كلمة المرور' : (language.value === 'fr' ? 'Mot de passe' : 'Password'));
const confirmPassLabel = computed(() => isRtl.value ? 'تأكيد كلمة المرور' : (language.value === 'fr' ? 'Confirmer le mot de passe' : 'Confirm Password'));

const agencyHeader = computed(() => isRtl.value ? 'تفاصيل الوكالة' : (language.value === 'fr' ? "Détails de l'agence" : 'Agency Details'));
const agencyNameLabel = computed(() => isRtl.value ? 'اسم الوكالة التجاري' : (language.value === 'fr' ? "Nom de l'agence" : 'Agency Business Name'));
const cityLabel = computed(() => isRtl.value ? 'المقر (المدينة)' : (language.value === 'fr' ? 'Ville' : 'City Location'));
const selectCityOpt = computed(() => isRtl.value ? 'اختر المدينة' : (language.value === 'fr' ? 'Sélectionner une ville' : 'Select a City'));
const addressLabel = computed(() => isRtl.value ? 'عنوان المكتب' : (language.value === 'fr' ? "Adresse" : 'Agency Address'));
const agencyPhoneLabel = computed(() => isRtl.value ? 'هاتف عمل الوكالة' : (language.value === 'fr' ? "Téléphone de l'agence" : 'Agency Business Phone'));

const btnText = computed(() => {
  if (form.processing) {
    return isRtl.value ? 'جاري إرسال الطلب...' : (language.value === 'fr' ? 'Création...' : 'Creating Account...');
  }
  return role.value === 'client'
    ? (isRtl.value ? 'سجل الآن' : (language.value === 'fr' ? "S'inscrire" : 'Register Now'))
    : (isRtl.value ? 'تقديم للموافقة' : (language.value === 'fr' ? 'Soumettre' : 'Submit for Approval'));
});

const footerPrompt = computed(() => {
  return isRtl.value ? 'لديك حساب بالفعل؟' : (language.value === 'fr' ? 'Vous avez déjà un compte ?' : 'Already have an account?');
});

const footerLinkText = computed(() => {
  return isRtl.value ? 'سجل دخولك هنا' : (language.value === 'fr' ? 'Se connecter ici' : 'Sign in here');
});
</script>
