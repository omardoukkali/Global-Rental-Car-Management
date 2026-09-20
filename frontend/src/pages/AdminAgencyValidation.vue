<template>
  <AdminLayout :pending-count="pendingAgencies.length">
    <div class="anim d1">
      <h1 class="page-greeting">Validation des agences</h1>
      <p class="page-sub">Vérifiez les dossiers en attente et validez ou rejetez chaque agence.</p>
    </div>

    <p v-if="error" class="banner-error">{{ error }}</p>
    <div v-if="loading" class="loading-box">Chargement des agences en attente...</div>

    <template v-else>
      <div class="section-title anim d2">
        <h2>
          Agences en attente de vérification
          <span class="sb-badge orange pending-badge">{{ pendingAgencies.length }}</span>
        </h2>
        <RouterLink to="/admin/agencies" class="see-all">Toutes les agences</RouterLink>
      </div>

      <AdminPendingAgencies
        class="anim d2"
        :agencies="pendingAgencies"
        :busy-id="busyId"
        @approve="approveAgency"
        @reject="rejectAgency"
      />
    </template>
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/components/AdminLayout.vue'
import AdminPendingAgencies from '@/components/AdminPendingAgencies.vue'
import adminService from '@/services/admin'

const loading = ref(true)
const error = ref('')
const busyId = ref(null)
const pendingAgencies = ref([])

async function loadAgencies() {
  loading.value = true
  error.value = ''
  try {
    const data = await adminService.getAgencies({ status: 'pending' })
    pendingAgencies.value = data.agencies || []
  } catch (err) {
    error.value = err.message || 'Impossible de charger les agences.'
  } finally {
    loading.value = false
  }
}

async function approveAgency(id) {
  error.value = ''
  busyId.value = id
  try {
    await adminService.approveAgency(id)
    pendingAgencies.value = pendingAgencies.value.filter((agency) => agency.id !== id)
  } catch (err) {
    error.value = err.message || 'Validation impossible.'
  } finally {
    busyId.value = null
  }
}

async function rejectAgency(id) {
  error.value = ''
  busyId.value = id
  try {
    await adminService.rejectAgency(id)
    pendingAgencies.value = pendingAgencies.value.filter((agency) => agency.id !== id)
  } catch (err) {
    error.value = err.message || 'Rejet impossible.'
  } finally {
    busyId.value = null
  }
}

onMounted(loadAgencies)
</script>

<style scoped>
.page-greeting {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  color: #0A0A0B;
  margin-bottom: 4px;
}

.page-sub {
  font-size: 0.88rem;
  color: #7A7A7D;
  margin-bottom: 36px;
}

.banner-error {
  background: #FEF2F2;
  color: #B91C1C;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 20px;
}

.loading-box {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 40px 20px;
  text-align: center;
  color: #7A7A7D;
  font-size: 0.9rem;
}

.section-title {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #0A0A0B;
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.section-title h2 {
  display: flex;
  align-items: center;
  margin: 0;
}

.pending-badge {
  margin-left: 8px;
  vertical-align: middle;
}

.sb-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 800;
  background: #E8E8EA;
  color: #0A0A0B;
}

.sb-badge.orange {
  background: #FFEDD5;
  color: #C2410C;
}

.see-all {
  font-size: 0.75rem;
  font-weight: 700;
  color: #7A7A7D;
  text-decoration: none;
}

.see-all:hover {
  color: #0A0A0B;
}

.anim {
  opacity: 0;
  animation: fadeUp 0.55s cubic-bezier(0.16,1,0.3,1) forwards;
}

.d1 { animation-delay: .06s; }
.d2 { animation-delay: .14s; }

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(18px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
