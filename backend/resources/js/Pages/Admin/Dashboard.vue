<template>
  <AppLayout>
    <div class="admin-dashboard">
      <header class="dashboard-header">
        <div>
          <h1>System Operations</h1>
          <p class="text-muted">GlobalRental Administration Control</p>
        </div>
      </header>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card pending-stat">
          <span class="stat-value">{{ stats.pending_agencies }}</span>
          <span class="stat-label">Pending Agencies</span>
        </div>
        <div class="stat-card">
          <span class="stat-value">{{ stats.total_agencies }}</span>
          <span class="stat-label">Total Agencies</span>
        </div>
        <div class="stat-card">
          <span class="stat-value">{{ stats.total_users }}</span>
          <span class="stat-label">Registered Users</span>
        </div>
        <div class="stat-card">
          <span class="stat-value">{{ stats.total_reservations }}</span>
          <span class="stat-label">Total Reservations</span>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button 
          :class="['tab-btn', { active: activeTab === 'agencies' }]" 
          @click="activeTab = 'agencies'"
        >
          Agency Fleet Manifests
          <span v-if="stats.pending_agencies > 0" class="tab-count">{{ stats.pending_agencies }}</span>
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'users' }]" 
          @click="activeTab = 'users'"
        >
          User Moderation Ledger
        </button>
      </div>

      <!-- Agencies Ledger -->
      <div v-if="activeTab === 'agencies'" class="fleet-ledger">
        <table class="ledger-table">
          <thead>
            <tr>
              <th>Agency Name</th>
              <th>Owner Details</th>
              <th>Contact Info</th>
              <th>Status</th>
              <th class="actions-cell">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="agency in agencies.data" :key="agency.id">
              <td class="brand-model">{{ agency.name }}</td>
              <td>
                <div class="vehicle-info">
                  <span class="brand-model" v-if="agency.owner">{{ agency.owner.first_name }} {{ agency.owner.last_name }}</span>
                  <span class="year-type" v-if="agency.owner">{{ agency.owner.email }}</span>
                  <span class="year-type" v-else>Owner deleted</span>
                </div>
              </td>
              <td>
                <div class="vehicle-info">
                  <span class="plate-cell">{{ agency.phone }}</span>
                  <span class="year-type">{{ agency.address }}</span>
                </div>
              </td>
              <td class="status-cell">
                <span class="stamp-badge" :class="agency.status">{{ agency.status }}</span>
              </td>
              <td class="actions-cell">
                <button 
                  v-if="agency.status !== 'approved'" 
                  @click="approveAgency(agency)" 
                  class="btn btn-accent" 
                  :disabled="isProcessing(agency.id)"
                  title="Approve"
                >
                  <CheckCircle :size="14" /> Approve
                </button>
                <button 
                  v-if="agency.status !== 'rejected'" 
                  @click="rejectAgency(agency)" 
                  class="btn btn-danger ml-2" 
                  :disabled="isProcessing(agency.id)"
                  title="Reject"
                >
                  <XCircle :size="14" /> Reject
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-if="agencies.data.length === 0" class="empty-state">
          <p>No agencies found.</p>
        </div>
      </div>

      <!-- Users Ledger -->
      <div v-if="activeTab === 'users'" class="fleet-ledger">
        <table class="ledger-table">
          <thead>
            <tr>
              <th>User Name</th>
              <th>Contact Details</th>
              <th>Role</th>
              <th>Status</th>
              <th class="actions-cell">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users.data" :key="user.id">
              <td class="brand-model">{{ user.first_name }} {{ user.last_name }}</td>
              <td>
                <div class="vehicle-info">
                  <span class="brand-model">{{ user.email }}</span>
                  <span class="year-type">{{ user.phone }}</span>
                </div>
              </td>
              <td class="plate-cell">{{ user.role }}</td>
              <td class="status-cell">
                <span class="stamp-badge" :class="user.status">{{ user.status }}</span>
              </td>
              <td class="actions-cell">
                <button 
                  v-if="user.status !== 'active'" 
                  @click="activateUser(user)" 
                  class="btn btn-accent" 
                  :disabled="isProcessing(user.id)"
                  title="Activate"
                >
                  <CheckCircle :size="14" /> Activate
                </button>
                <button 
                  v-if="user.status !== 'blocked'" 
                  @click="suspendUser(user)" 
                  class="btn btn-danger ml-2" 
                  :disabled="isProcessing(user.id)"
                  title="Suspend"
                >
                  <Ban :size="14" /> Suspend
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-if="users.data.length === 0" class="empty-state">
          <p>No users found.</p>
        </div>
      </div>
      
      <!-- Pagination logic that switches based on active tab -->
      <div class="mt-4">
        <Pagination v-if="activeTab === 'agencies'" :links="agencies.links" />
        <Pagination v-if="activeTab === 'users'" :links="users.links" />
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { CheckCircle, XCircle, Ban } from 'lucide-vue-next';

const props = defineProps({
  stats: Object,
  agencies: Object,
  users: Object,
});

const activeTab = ref('agencies');
const processingIds = ref(new Set());

function isProcessing(id) {
  return processingIds.value.has(id);
}

function approveAgency(agency) {
  if (!confirm(`Approve agency ${agency.name}?`)) return;
  processingIds.value.add(agency.id);
  router.post(`/admin/agencies/${agency.id}/approve`, {}, {
    preserveScroll: true,
    onFinish: () => processingIds.value.delete(agency.id),
  });
}

function rejectAgency(agency) {
  const message = agency.status === 'approved'
    ? `This agency is currently live. Rejecting will immediately remove ${agency.name}'s cars from public search. Continue?`
    : `Reject ${agency.name}'s application?`;
  if (!confirm(message)) return;
  processingIds.value.add(agency.id);
  router.post(`/admin/agencies/${agency.id}/reject`, {}, {
    preserveScroll: true,
    onFinish: () => processingIds.value.delete(agency.id),
  });
}

function activateUser(user) {
  if (!confirm(`Activate user ${user.email}?`)) return;
  processingIds.value.add(user.id);
  router.post(`/admin/users/${user.id}/activate`, {}, {
    preserveScroll: true,
    onFinish: () => processingIds.value.delete(user.id),
  });
}

function suspendUser(user) {
  if (!confirm(`Suspend user ${user.email}?`)) return;
  processingIds.value.add(user.id);
  router.post(`/admin/users/${user.id}/suspend`, {}, {
    preserveScroll: true,
    onFinish: () => processingIds.value.delete(user.id),
  });
}
</script>

<style scoped>
.admin-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 3rem 1.5rem;
  font-family: 'Inter', sans-serif;
  color: #1B2430;
  background-color: #FAF9F6;
  min-height: 100vh;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  border-bottom: 1px solid #E3E1DB;
  padding-bottom: 1rem;
}

.dashboard-header h1 {
  font-family: 'Fraunces', serif;
  font-size: 2.25rem;
  font-weight: 400;
  margin: 0 0 0.5rem;
  color: #1B2430;
}

.text-muted { 
  color: #6B7280; 
  font-size: 0.95rem; 
  margin: 0; 
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: #FFFFFF;
  border: 1px solid #E3E1DB;
  border-radius: 4px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.stat-card.pending-stat {
  border-left: 4px solid #9C6B1F; /* Highlight pending queue */
}

.stat-value {
  font-family: 'Fraunces', serif;
  font-size: 2.5rem;
  color: #1B2430;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.85rem;
  color: #6B7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 500;
}

/* Tabs */
.tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid #E3E1DB;
  padding-bottom: 1px; /* Align with border */
}

.tab-btn {
  background: none;
  border: none;
  font-family: 'Inter', sans-serif;
  font-size: 1rem;
  font-weight: 500;
  color: #6B7280;
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px; /* Overlap border */
  transition: all 0.2s;
}

.tab-btn:hover {
  color: #1B2430;
}

.tab-btn.active {
  color: #21606B;
  border-bottom-color: #21606B;
}

.tab-count {
  background: #9C6B1F;
  color: #FFFFFF;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.1rem 0.4rem;
  border-radius: 12px;
  margin-left: 0.5rem;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #6B7280;
  border-top: 1px solid #E3E1DB;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  border: 1px solid transparent;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.2s, background-color 0.2s;
  letter-spacing: 0.02em;
}

.btn:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-accent { 
  background: #21606B; 
  color: #FFFFFF; 
}
.btn-accent:hover { background: #1a4d56; }

.btn-danger {
  background: transparent;
  color: #9B4A42;
  border-color: #9B4A42;
}
.btn-danger:hover {
  background: #fee2e2;
}

.ml-2 { margin-left: 0.5rem; }
.mt-4 { margin-top: 1.5rem; }

/* Ledger Layout */
.fleet-ledger {
  background: #FFFFFF;
  border: 1px solid #E3E1DB;
  border-radius: 4px;
  overflow-x: auto;
}

.ledger-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.ledger-table th {
  padding: 1rem 1.5rem;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6B7280;
  border-bottom: 1px solid #E3E1DB;
  font-weight: 600;
}

.ledger-table td {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #E3E1DB;
  vertical-align: middle;
}

.ledger-table tr:last-child td {
  border-bottom: none;
}

.vehicle-info {
  display: flex;
  flex-direction: column;
}

.brand-model {
  font-weight: 500;
  color: #1B2430;
  font-size: 0.95rem;
}

.year-type {
  font-size: 0.8rem;
  color: #6B7280;
  margin-top: 0.15rem;
}

.plate-cell {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 0.9rem;
  color: #1B2430;
}

.actions-cell {
  text-align: right;
  white-space: nowrap;
}

/* Stamp Badges */
.stamp-badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  font-family: 'IBM Plex Mono', monospace;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border: 1.5px solid;
  border-radius: 2px;
  transform: rotate(-3deg);
  background: transparent;
}

/* Status colors */
.stamp-badge.active,
.stamp-badge.approved { color: #4C7A5D; border-color: #4C7A5D; }

.stamp-badge.pending { color: #9C6B1F; border-color: #9C6B1F; }

.stamp-badge.blocked,
.stamp-badge.rejected { color: #9B4A42; border-color: #9B4A42; }

</style>
