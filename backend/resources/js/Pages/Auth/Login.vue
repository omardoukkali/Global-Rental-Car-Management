<template>
  <AppLayout>
    <div class="auth-container flex-center min-h-[70vh] py-12 px-6">
      <div class="card glass-card w-full max-w-[420px] p-8 text-center fade-in">
        <h2 class="auth-title text-3xl font-extrabold mb-2">{{ title }}</h2>
        <p class="auth-subtitle text-sm text-slate-400 mb-6">{{ subtitle }}</p>

        <!-- Login Form -->
        <form @submit.prevent="submit" class="mt-4 text-left">
          <FormField
            type="email"
            :label="emailLabel"
            v-model="form.email"
            :error="form.errors.email"
            placeholder="name@example.com"
            required
            :disabled="form.processing"
          />

          <FormField
            type="password"
            :label="passLabel"
            v-model="form.password"
            :error="form.errors.password"
            placeholder="••••••••"
            required
            :disabled="form.processing"
          />

          <button type="submit" class="btn btn-primary w-full mt-4 flex items-center justify-center" :disabled="form.processing">
            {{ buttonText }}
            <ArrowRightIcon class="w-4 h-4" :class="{ 'rotate-180': isRtl, 'ml-1': !isRtl, 'mr-1': isRtl }" />
          </button>
        </form>

        <div class="auth-footer mt-6 pt-4 border-t border-white/5 text-sm text-slate-400">
          <p>
            {{ footerText }} 
            <Link href="/register" class="text-indigo-400 hover:text-indigo-300 font-semibold transition-colors">
              {{ footerLink }}
            </Link>
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useSettings } from '@/Composables/useSettings';
import AppLayout from '@/Components/AppLayout.vue';
import FormField from '@/Components/FormField.vue';
import { ArrowRight as ArrowRightIcon } from 'lucide-vue-next';

const { locale: language, isRtl } = useSettings();

const form = useForm({
  email: '',
  password: '',
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};

// Inline translation hooks
const title = computed(() => {
  return isRtl.value ? 'مرحباً بك مجدداً' : (language.value === 'fr' ? 'Bon retour' : 'Welcome Back');
});

const subtitle = computed(() => {
  return isRtl.value
    ? 'قم بتسجيل الدخول لإدارة تجربة تأجير السيارات الخاصة بك'
    : (language.value === 'fr' ? 'Connectez-vous pour gérer votre expérience de location' : 'Log in to manage your car rental experience');
});

const emailLabel = computed(() => {
  return isRtl.value ? 'البريد الإلكتروني' : (language.value === 'fr' ? 'Adresse e-mail' : 'Email Address');
});

const passLabel = computed(() => {
  return isRtl.value ? 'كلمة المرور' : (language.value === 'fr' ? 'Mot de passe' : 'Password');
});

const buttonText = computed(() => {
  if (form.processing) {
    return isRtl.value ? 'جاري الدخول...' : (language.value === 'fr' ? 'Connexion...' : 'Signing In...');
  }
  return isRtl.value ? 'تسجيل الدخول' : (language.value === 'fr' ? 'Se connecter' : 'Sign In');
});

const footerText = computed(() => {
  return isRtl.value ? 'ليس لديك حساب؟' : (language.value === 'fr' ? "Vous n'avez pas de compte ?" : "Don't have an account?");
});

const footerLink = computed(() => {
  return isRtl.value ? 'أنشئ حساباً هنا' : (language.value === 'fr' ? 'Créez-en un ici' : 'Create one here');
});
</script>
