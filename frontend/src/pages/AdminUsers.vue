<template>
  <AdminLayout>
    <h1 class="page-greeting">Utilisateurs</h1>
    <p class="page-sub">Comptes clients, agences et administrateurs.</p>

    <div class="filter-row">
      <input v-model="q" class="form-input" type="search" placeholder="Nom, email, téléphone…" @keyup.enter="search" />
      <select v-model="role" @change="search">
        <option value="">Tous les rôles</option>
        <option value="client">Client</option>
        <option value="agency">Agence</option>
        <option value="admin">Admin</option>
      </select>
      <select v-model="status" @change="search">
        <option value="">Tous les statuts</option>
        <option value="active">Actif</option>
        <option value="pending">Attente</option>
        <option value="suspended">Suspendu</option>
      </select>
    </div>

    <p v-if="error" class="banner-error">{{ error }}</p>
    <div v-else-if="loading" class="loading-box">Chargement des utilisateurs...</div>
    <div v-else class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Rôle</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="users.length === 0">
            <td colspan="5" class="empty-cell">Aucun utilisateur.</td>
          </tr>
          <tr v-for="user in users" :key="user.id">
            <td class="bold">{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.phone || '—' }}</td>
            <td><span :class="['pill', userRole(user.role).className]">{{ userRole(user.role).label }}</span></td>
            <td><span :class="['pill', userStatus(user.status).className]">{{ userStatus(user.status).label }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
    <AdminPager :meta="meta" @prev="page--" @next="page++" />
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import AdminPager from '@/components/AdminPager.vue'
import adminService from '@/services/admin'
import { userRole, userStatus } from '@/utils/adminFormat'

const loading = ref(true)
const error = ref('')
const users = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const page = ref(1)
const q = ref('')
const role = ref('')
const status = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getUsers({
      page: page.value,
      q: q.value || undefined,
      role: role.value || undefined,
      status: status.value || undefined,
    })
    users.value = data.users || []
    meta.value = data.meta || meta.value
  } catch (err) {
    error.value = err.message || 'Impossible de charger les utilisateurs.'
  } finally {
    loading.value = false
  }
}

function search() {
  page.value = 1
  load()
}

watch(page, load)
onMounted(load)
</script>
