<template>
  <AdminLayout>
    <h1 class="page-greeting">Agences</h1>
    <p class="page-sub">Toutes les agences inscrites sur la plateforme.</p>

    <div class="filter-row">
      <input
        v-model="q"
        class="form-input"
        type="search"
        placeholder="Nom, ville, email…"
      />
      <select v-model="status" @change="load">
        <option value="">Tous les statuts</option>
        <option value="approved">Approuvées</option>
        <option value="pending">En attente</option>
        <option value="rejected">Rejetées</option>
      </select>
    </div>

    <p v-if="error" class="banner-error">{{ error }}</p>
    <div v-else-if="loading" class="loading-box">Chargement des agences...</div>
    <div v-else class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Agence</th>
            <th>Ville</th>
            <th>Gérant</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Commission</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0">
            <td colspan="7" class="empty-cell">Aucune agence.</td>
          </tr>
          <tr
            v-for="agency in filtered"
            :key="agency.id"
            class="row-link"
            role="link"
            tabindex="0"
            @click="openAgency(agency.id)"
            @keyup.enter="openAgency(agency.id)"
          >
            <td class="bold">
              <RouterLink :to="agencyPath(agency.id)" class="agency-link" @click.stop>
                {{ agency.name }}
              </RouterLink>
              <span class="mono"> {{ agency.reference }}</span>
            </td>
            <td>{{ agency.city || '—' }}</td>
            <td>{{ agency.manager || '—' }}</td>
            <td>{{ agency.email || '—' }}</td>
            <td>{{ agency.phone || '—' }}</td>
            <td>{{ agency.commission_rate != null ? agency.commission_rate + ' %' : '—' }}</td>
            <td>
              <span :class="['pill', agencyStatus(agency.status).className]">
                {{ agencyStatus(agency.status).label }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AdminLayout from '@/components/AdminLayout.vue'
import adminService from '@/services/admin'
import { agencyStatus } from '@/utils/adminFormat'

const router = useRouter()

const loading = ref(true)
const error = ref('')
const agencies = ref([])
const q = ref('')
const status = ref('')

const filtered = computed(() => {
  const term = q.value.trim().toLowerCase()
  if (!term) return agencies.value
  return agencies.value.filter((agency) => {
    const haystack = [
      agency.name,
      agency.city,
      agency.email,
      agency.manager,
      agency.phone,
      agency.reference,
    ].join(' ').toLowerCase()
    return haystack.includes(term)
  })
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getAgencies({
      status: status.value || undefined,
    })
    agencies.value = data.agencies || []
  } catch (err) {
    error.value = err.message || 'Impossible de charger les agences.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

function agencyPath(id) {
  return `/admin/agencies/${id}`
}

function openAgency(id) {
  router.push(agencyPath(id))
}
</script>

<style scoped>
.row-link {
  cursor: pointer;
}

.row-link:hover td {
  background: #FAFAF9;
}

.agency-link {
  color: inherit;
  text-decoration: none;
}

.agency-link:hover {
  text-decoration: underline;
}
</style>
