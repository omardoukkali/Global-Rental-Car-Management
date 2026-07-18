<template>
  <div class="auth-wrap fade-in">
    <div class="glass-card auth-card">
      <div class="auth-logo">
        <CarIcon class="auth-logo-icon" />
        <span>GlobalRental</span>
      </div>
      <h2 class="auth-title">{{ t('auth.createAccount') }}</h2>

      <!-- Tab switcher -->
      <div class="tabs">
        <button :class="['tab', { active: tab === 'client' }]" @click="tab = 'client'">{{ t('auth.client') }}</button>
        <button :class="['tab', { active: tab === 'agency' }]" @click="tab = 'agency'">{{ t('auth.agencyOwner') }}</button>
      </div>

      <form @submit.prevent="handleSubmit">
        <!-- Common fields -->
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">{{ t('auth.firstName') }}</label>
            <input v-model="form.first_name" type="text" class="form-input" required />
            <span v-if="errors.first_name" class="form-error">{{ errors.first_name[0] }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">{{ t('auth.lastName') }}</label>
            <input v-model="form.last_name" type="text" class="form-input" required />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">{{ t('auth.email') }}</label>
          <input v-model="form.email" type="email" class="form-input" required />
          <span v-if="errors.email" class="form-error">{{ errors.email[0] }}</span>
        </div>
        <div class="form-group">
          <label class="form-label">{{ t('auth.phone') }}</label>
          <input v-model="form.phone" type="tel" class="form-input" required />
        </div>
        <div class="form-group">
          <label class="form-label">{{ t('auth.password') }}</label>
          <input v-model="form.password" type="password" class="form-input" required minlength="8" />
        </div>

        <!-- Agency-only fields -->
        <template v-if="tab === 'agency'">
          <hr class="divider" />
          <div class="form-group">
            <label class="form-label">{{ t('auth.passwordConfirmation') }}</label>
            <input v-model="form.password_confirmation" type="password" class="form-input" required />
          </div>
          <div class="form-group">
            <label class="form-label">{{ t('auth.agencyName') }}</label>
            <input v-model="form.agency_name" type="text" class="form-input" required />
          </div>
          <div class="form-group">
            <label class="form-label">{{ t('auth.city') }}</label>
            <select v-model="form.agency_city" class="form-input" required>
              <option value="">{{ t('auth.selectCity') }}</option>
              <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">{{ t('auth.address') }}</label>
            <input v-model="form.address" type="text" class="form-input" required />
          </div>
          <div class="form-group">
            <label class="form-label">{{ t('auth.agencyPhone') }}</label>
            <input v-model="form.agency_phone" type="tel" class="form-input" required />
          </div>
        </template>

        <div v-if="globalError" class="error-banner">{{ globalError }}</div>
        <div v-if="success" class="success-banner">{{ success }}</div>

        <button type="submit" class="btn btn-primary btn-full" :disabled="loading">
          <span v-if="loading" class="spinner" style="width:16px;height:16px;border-width:2px"></span>
          <span v-else>{{ tab === 'client' ? t('auth.createBtn') : t('auth.submitAgency') }}</span>
        </button>
      </form>

      <p class="auth-footer">
        {{ t('auth.haveAccount') }}
        <RouterLink to="/login" style="color:var(--accent);font-weight:600">{{ t('auth.signIn') }}</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Car as CarIcon } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const { t } = useI18n()
const auth   = useAuthStore()
const router = useRouter()

const tab    = ref('client')
const cities = ref([])
const form   = reactive({
  first_name: '', last_name: '', email: '', phone: '', password: '',
  password_confirmation: '', agency_name: '', agency_city: '', address: '', agency_phone: ''
})
const errors      = reactive({})
const globalError = ref('')
const success     = ref('')
const loading     = ref(false)

async function handleSubmit() {
  globalError.value = ''
  success.value     = ''
  Object.keys(errors).forEach(k => delete errors[k])
  loading.value = true
  try {
    if (tab.value === 'client') {
      const data = await api.post('/auth/register/client', form)
      auth.setSession(data.token, data.user)
      router.push('/')
    } else {
      await api.post('/auth/register/agency', form)
      success.value = t('auth.applicationSubmitted')
      setTimeout(() => router.push('/login'), 3000)
    }
  } catch (e) {
    if (e.errors) Object.assign(errors, e.errors)
    else globalError.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const data = await api.get('/cities')
    cities.value = data.data ?? data
  } catch {}
})
</script>

<style scoped>
.auth-wrap  { display: flex; align-items: center; justify-content: center; min-height: 70vh; padding: 32px 0; }
.auth-card  { width: 100%; max-width: 520px; padding: 40px; }
.auth-logo  { display: flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 800; margin-bottom: 20px; }
.auth-logo-icon { width: 28px; height: 28px; color: var(--accent); }
.auth-title { font-size: 1.5rem; margin-bottom: 20px; }
.auth-footer { text-align: center; margin-top: 20px; font-size: 0.9rem; color: var(--muted); }
.error-banner   { background: var(--danger-bg); border: 1px solid rgba(239,68,68,0.3); color: var(--danger); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 0.88rem; margin-bottom: 16px; }
.success-banner { background: var(--success-bg); border: 1px solid rgba(16,185,129,0.3); color: var(--success); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 0.88rem; margin-bottom: 16px; }

.tabs { display: flex; border-bottom: 1px solid var(--line); margin-bottom: 24px; }
.tab { flex: 1; padding: 10px; font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 0.9rem; color: var(--muted); background: none; border: none; cursor: pointer; border-bottom: 2px solid transparent; transition: all var(--transition-fast); }
.tab.active { color: var(--accent); border-bottom-color: var(--accent); }
</style>
