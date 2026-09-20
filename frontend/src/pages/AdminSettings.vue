<template>
  <AdminLayout>
    <h1 class="page-greeting">Paramètres</h1>
    <p class="page-sub">Profil de l’administrateur connecté.</p>

    <p v-if="error" class="banner-error">{{ error }}</p>
    <p v-if="success" class="banner-ok">{{ success }}</p>
    <div v-if="loading" class="loading-box">Chargement du profil...</div>

    <form v-else class="settings-card" @submit.prevent="save">
      <label class="form-label" for="admin-first-name">Prénom</label>
      <input id="admin-first-name" v-model="form.first_name" class="form-input" type="text" required />

      <label class="form-label" for="admin-last-name">Nom</label>
      <input id="admin-last-name" v-model="form.last_name" class="form-input" type="text" required />

      <label class="form-label" for="admin-email">Email</label>
      <input id="admin-email" v-model="form.email" class="form-input" type="email" required />

      <label class="form-label" for="admin-phone">Téléphone</label>
      <input id="admin-phone" v-model="form.phone" class="form-input" type="text" placeholder="+212 6…" />

      <label class="form-label" for="admin-password">Nouveau mot de passe</label>
      <input id="admin-password" v-model="form.password" class="form-input" type="password" placeholder="Laisser vide pour ne pas changer" />

      <button type="submit" class="btn-primary" :disabled="saving">
        {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
      </button>
    </form>
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import adminService from '@/services/admin'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')
const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getProfile()
    const user = data.user || {}
    form.first_name = user.first_name || ''
    form.last_name = user.last_name || ''
    form.email = user.email || ''
    form.phone = user.phone || ''
    form.password = ''
  } catch (err) {
    error.value = err.message || 'Impossible de charger le profil.'
  } finally {
    loading.value = false
  }
}

async function save() {
  error.value = ''
  success.value = ''
  saving.value = true
  try {
    const payload = {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      phone: form.phone || null,
    }
    if (form.password) payload.password = form.password
    const data = await adminService.updateProfile(payload)
    const user = data.user || {}
    if (auth.user) {
      auth.setSession(auth.token, {
        ...auth.user,
        first_name: user.first_name,
        last_name: user.last_name,
        email: user.email,
        phone: user.phone,
      })
    }
    form.password = ''
    success.value = 'Profil mis à jour.'
  } catch (err) {
    error.value = err.message || 'Enregistrement impossible.'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
