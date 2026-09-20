<template>
  <AdminLayout>
    <RouterLink to="/admin/agencies" class="back-link">← Agences</RouterLink>

    <p v-if="error" class="banner-error">{{ error }}</p>
    <p v-if="success" class="banner-ok">{{ success }}</p>
    <div v-if="loading" class="loading-box">Chargement de l’agence...</div>

    <template v-else-if="agency">
      <div class="agency-head">
        <div>
          <h1 class="page-greeting">{{ agency.name }}</h1>
          <p class="page-sub">
            {{ agency.city || 'Ville inconnue' }}
            <span v-if="agency.manager"> • {{ agency.manager }}</span>
          </p>
        </div>
        <div class="head-meta">
          <span class="mono-chip">{{ agency.reference }}</span>
          <span :class="['pill', agencyStatus(agency.status).className]">
            {{ agencyStatus(agency.status).label }}
          </span>
        </div>
      </div>

      <div class="info-grid">
        <div class="info-card">
          <h2 class="card-title">Informations</h2>
          <div class="info-row"><span>Email</span><strong>{{ agency.email || '—' }}</strong></div>
          <div class="info-row"><span>Téléphone</span><strong>{{ agency.phone || '—' }}</strong></div>
          <div class="info-row"><span>Adresse</span><strong>{{ agency.address || '—' }}</strong></div>
          <div class="info-row"><span>Ville</span><strong>{{ agency.city || '—' }}</strong></div>
          <div class="info-row"><span>Gérant</span><strong>{{ agency.manager || '—' }}</strong></div>
          <div class="info-row"><span>Compte</span><strong>{{ agency.owner_email || '—' }}</strong></div>
          <div class="info-row"><span>Flotte</span><strong>{{ Number(agency.cars_count || 0) }} véhicule(s)</strong></div>
          <div class="info-row"><span>Points</span><strong>{{ Number(agency.points_count || 0) }} point(s)</strong></div>
          <div class="info-row">
            <span>Note</span>
            <strong>
              {{ agency.avg_rating != null ? agency.avg_rating + ' ★' : '—' }}
              <span v-if="agency.total_reviews" class="muted">({{ agency.total_reviews }})</span>
            </strong>
          </div>
        </div>

        <div class="info-card">
          <h2 class="card-title">Commission</h2>
          <p class="card-help">Taux prélevé par la plateforme sur chaque location payée.</p>
          <form class="commission-form" @submit.prevent="saveCommission">
            <label class="form-label" for="agency-commission">Taux (%)</label>
            <div class="commission-row">
              <input
                id="agency-commission"
                v-model.number="commissionRate"
                class="form-input"
                type="number"
                min="0"
                max="100"
                step="0.5"
                required
              />
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="danger-card">
        <h2 class="card-title">Supprimer l’agence</h2>
        <p class="card-help">
          L’agence disparaît de la plateforme. Les véhicules sont retirés du catalogue.
          Impossible s’il reste des réservations en cours.
        </p>
        <button type="button" class="btn-danger" :disabled="deleting" @click="removeAgency">
          {{ deleting ? 'Suppression…' : 'Supprimer l’agence' }}
        </button>
      </div>
    </template>
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AdminLayout from '@/components/AdminLayout.vue'
import adminService from '@/services/admin'
import { agencyStatus } from '@/utils/adminFormat'

const props = defineProps({
  agencyId: { type: String, required: true },
})

const router = useRouter()
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const error = ref('')
const success = ref('')
const agency = ref(null)
const commissionRate = ref(0)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getAgency(props.agencyId)
    agency.value = data.agency
    commissionRate.value = Number(data.agency?.commission_rate ?? 0)
  } catch (err) {
    error.value = err.message || 'Impossible de charger l’agence.'
  } finally {
    loading.value = false
  }
}

async function saveCommission() {
  error.value = ''
  success.value = ''
  saving.value = true
  try {
    const data = await adminService.updateAgency(props.agencyId, {
      commission_rate: Number(commissionRate.value),
    })
    agency.value = data.agency || agency.value
    commissionRate.value = Number(data.agency?.commission_rate ?? commissionRate.value)
    success.value = 'Commission mise à jour.'
  } catch (err) {
    error.value = err.message || 'Enregistrement impossible.'
  } finally {
    saving.value = false
  }
}

async function removeAgency() {
  if (!confirm(`Supprimer ${agency.value?.name || 'cette agence'} ? Cette action retire l’agence de la plateforme.`)) {
    return
  }
  error.value = ''
  success.value = ''
  deleting.value = true
  try {
    await adminService.deleteAgency(props.agencyId)
    router.push('/admin/agencies')
  } catch (err) {
    error.value = err.message || 'Suppression impossible.'
  } finally {
    deleting.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.back-link {
  display: inline-block;
  font-size: 0.8rem;
  font-weight: 700;
  color: #7A7A7D;
  text-decoration: none;
  margin-bottom: 12px;
}

.back-link:hover {
  color: #0A0A0B;
}

.agency-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 8px;
}

.head-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.mono-chip {
  font-family: monospace;
  font-size: 0.7rem;
  background: #FAFAF9;
  border: 1px solid #E8E8EA;
  padding: 2px 6px;
  border-radius: 4px;
}

.info-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 16px;
  margin: 8px 0 20px;
}

.info-card,
.danger-card {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 22px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
}

.card-title {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1rem;
  font-weight: 800;
  margin: 0 0 14px;
}

.card-help {
  font-size: 0.8rem;
  color: #7A7A7D;
  margin: -6px 0 16px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 0.8rem;
  padding: 8px 0;
  border-bottom: 1px solid #F2F2F3;
}

.info-row:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.info-row span {
  color: #7A7A7D;
}

.info-row strong {
  text-align: right;
  max-width: 65%;
  word-break: break-word;
}

.muted {
  color: #7A7A7D;
  font-weight: 600;
}

.commission-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.commission-row .form-input {
  max-width: 140px;
  height: 42px;
  border-radius: 12px;
  border: 1px solid #E8E8EA;
  padding: 0 12px;
}

.danger-card {
  border-color: #FECACA;
}

.btn-danger {
  background: #B91C1C;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 10px 16px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: wait;
}

@media (max-width: 980px) {
  .info-grid { grid-template-columns: 1fr; }
  .agency-head { flex-direction: column; }
}
</style>
