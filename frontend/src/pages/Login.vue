<template>
  <div class="auth-wrap fade-in">
    <div class="glass-card auth-card">
      <div class="auth-logo">
        <CarIcon class="auth-logo-icon" />
        <span>GlobalRental</span>
      </div>
      <h2 class="auth-title">{{ t('auth.welcomeBack') }}</h2>
      <p class="auth-sub">{{ t('auth.signInSub') }}</p>

      <form @submit.prevent="handleSubmit">
        <div class="form-group">
          <label class="form-label">{{ t('auth.email') }}</label>
          <input v-model="form.email" type="email" class="form-input" placeholder="you@email.com" required />
          <span v-if="errors.email" class="form-error">{{ errors.email }}</span>
        </div>
        <div class="form-group">
          <label class="form-label">{{ t('auth.password') }}</label>
          <input v-model="form.password" type="password" class="form-input" placeholder="••••••••" required />
        </div>

        <div v-if="globalError" class="error-banner">{{ globalError }}</div>

        <button type="submit" class="btn btn-primary btn-full" :disabled="loading">
          <span v-if="loading" class="spinner" style="width:16px;height:16px;border-width:2px"></span>
          <span v-else>{{ t('auth.loginButton') }}</span>
        </button>
      </form>

      <p class="auth-footer">
        {{ t('auth.noAccount') }}
        <RouterLink to="/register" style="color:var(--accent);font-weight:600">{{ t('auth.register') }}</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Car as CarIcon } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth   = useAuthStore()
const router = useRouter()
const route  = useRoute()

const form        = reactive({ email: '', password: '' })
const errors      = reactive({})
const globalError = ref('')
const loading     = ref(false)

async function handleSubmit() {
  globalError.value = ''
  Object.keys(errors).forEach(k => delete errors[k])
  loading.value = true
  try {
    const user = await auth.login(form)
    const redirect = route.query.redirect || '/'
    if (user.role === 'admin')         router.push('/admin/dashboard')
    else if (user.role === 'agency_owner') router.push('/owner/dashboard')
    else                               router.push(redirect)
  } catch (e) {
    if (e.errors) Object.assign(errors, e.errors)
    else globalError.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-wrap { display: flex; align-items: center; justify-content: center; min-height: 70vh; }
.auth-card { width: 100%; max-width: 420px; padding: 40px; }
.auth-logo { display: flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 800; margin-bottom: 28px; }
.auth-logo-icon { width: 28px; height: 28px; color: var(--accent); }
.auth-title { font-size: 1.6rem; margin-bottom: 4px; }
.auth-sub { color: var(--muted); margin-bottom: 28px; }
.auth-footer { text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--muted); }
.error-banner { background: var(--danger-bg); border: 1px solid rgba(239,68,68,0.3); color: var(--danger); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 0.88rem; margin-bottom: 16px; }
</style>
