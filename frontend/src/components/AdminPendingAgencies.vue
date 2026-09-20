<script setup>
import { RouterLink } from 'vue-router'

defineProps({
  agencies: { type: Array, default: () => [] },
  busyId: { type: [String, Number], default: null },
  emptyMessage: { type: String, default: 'Aucune agence en attente.' },
})

const emit = defineEmits(['approve', 'reject'])
</script>

<template>
  <div v-if="agencies.length === 0" class="empty-pending">
    {{ emptyMessage }}
  </div>
  <div v-else class="req-grid">
    <div v-for="agency in agencies" :key="agency.id" class="req-card">
      <div class="req-head">
        <div>
          <div class="req-name">
            <RouterLink :to="`/admin/agencies/${agency.id}`" class="agency-link" @click.stop>
              {{ agency.name }}
            </RouterLink>
          </div>
          <div class="req-car">{{ agency.city || 'Ville inconnue' }} • {{ agency.manager }}</div>
        </div>
        <div class="req-rc">{{ agency.reference }}</div>
      </div>

      <div class="req-box">
        <div class="req-row">
          <span class="req-check-label">Email</span>
          <span :class="agency.email ? 'req-value' : 'req-ko'">{{ agency.email || '—' }}</span>
        </div>
        <div class="req-row">
          <span class="req-check-label">Téléphone</span>
          <span :class="agency.phone ? 'req-value' : 'req-ko'">{{ agency.phone || '—' }}</span>
        </div>
        <div class="req-row">
          <span class="req-check-label">Adresse</span>
          <span :class="agency.address ? 'req-value' : 'req-ko'">{{ agency.address || '—' }}</span>
        </div>
        <div class="req-row">
          <span class="req-check-label">Ville</span>
          <span :class="agency.city ? 'req-value' : 'req-ko'">{{ agency.city || '—' }}</span>
        </div>
        <div class="req-row">
          <span class="req-check-label">Gérant</span>
          <span class="req-value">{{ agency.manager || '—' }}</span>
        </div>
        <div class="req-row">
          <span class="req-check-label">Compte</span>
          <span :class="agency.owner_email ? 'req-value' : 'req-ko'">{{ agency.owner_email || '—' }}</span>
        </div>
        <div class="req-row">
          <span class="req-check-label">Commission</span>
          <span class="req-value">{{ agency.commission_rate != null ? agency.commission_rate + ' %' : '—' }}</span>
        </div>
      </div>

      <div class="req-actions">
        <button
          type="button"
          class="btn btn-primary"
          :disabled="busyId === agency.id"
          @click="emit('approve', agency.id)"
        >
          ✓ Valider
        </button>
        <button
          type="button"
          class="btn btn-outline"
          :disabled="busyId === agency.id"
          @click="emit('reject', agency.id)"
        >
          ✗ Rejeter
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.req-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 36px;
}

.req-card {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
  display: flex;
  flex-direction: column;
  gap: 16px;
  border-top: 4px solid #F59E0B;
}

.req-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.req-name {
  font-weight: 800;
  font-size: 1rem;
  margin-bottom: 2px;
}

.agency-link {
  color: inherit;
  text-decoration: none;
}

.agency-link:hover {
  text-decoration: underline;
}

.req-car {
  font-size: 0.75rem;
  color: #7A7A7D;
  font-weight: 500;
}

.req-rc {
  font-family: monospace;
  font-size: 0.7rem;
  background: #FAFAF9;
  border: 1px solid #E8E8EA;
  padding: 2px 6px;
  border-radius: 4px;
}

.req-box {
  background: #FAFAF9;
  border-radius: 12px;
  padding: 12px;
  font-size: 0.75rem;
  border: 1px solid #F2F2F3;
}

.req-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 8px;
}

.req-row:last-child {
  margin-bottom: 0;
}

.req-check-label {
  color: #7A7A7D;
  flex-shrink: 0;
}

.req-value,
.req-ko {
  text-align: right;
  max-width: 62%;
  word-break: break-word;
}

.req-value {
  color: #0A0A0B;
  font-weight: 600;
}

.req-ko {
  color: #B91C1C;
  font-weight: 700;
}

.req-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}

.btn {
  flex: 1;
  padding: 9px;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  transition: all .15s;
  border: none;
}

.btn:disabled {
  opacity: 0.6;
  cursor: wait;
}

.btn-primary {
  background: #0A0A0B;
  color: #fff;
}

.btn-outline {
  background: transparent;
  border: 1.5px solid #E8E8EA;
  color: #0A0A0B;
}

.empty-pending {
  background: #fff;
  border: 1px solid #F2F2F3;
  border-radius: 18px;
  padding: 28px 20px;
  text-align: center;
  color: #7A7A7D;
  font-size: 0.88rem;
  margin-bottom: 36px;
}

@media (max-width: 1280px) {
  .req-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 980px) {
  .req-grid { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
  .req-head { flex-direction: column; align-items: flex-start; }
  .req-actions { flex-direction: column; }
}
</style>
