<template>
  <div class="fade-in">
    <div class="flex-between" style="margin-bottom:24px">
      <h1>{{ t('admin.title') }}</h1>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button :class="['tab', { active: tab === 'agencies' }]" @click="tab = 'agencies'">{{ t('admin.agencies') }}</button>
      <button :class="['tab', { active: tab === 'users' }]" @click="tab = 'users'">{{ t('admin.users') }}</button>
    </div>

    <div v-if="loading" class="flex-center" style="padding:64px 0"><div class="spinner spinner-lg"></div></div>

    <!-- Agencies Tab -->
    <div v-else-if="tab === 'agencies'">
      <div v-if="!agencies.length" class="empty-state glass-card">
        <p>{{ t('admin.noAgencies') }}</p>
      </div>

      <div v-else class="res-list">
        <div v-for="a in agencies" :key="a.id" class="glass-card res-card">
          <div class="flex-between">
            <div>
              <h3>{{ a.name }}</h3>
              <p style="color:var(--muted);font-size:0.88rem;margin-top:4px">{{ t('admin.owner') }}: {{ a.owner?.first_name }} {{ a.owner?.last_name }} ({{ a.owner?.email }})</p>
              <p style="color:var(--muted);font-size:0.88rem">📍 {{ t('cities.' + a.city?.name, a.city?.name) }} • 📞 {{ a.phone }}</p>
            </div>
            <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:8px">
              <span class="badge" :class="`badge-${a.status}`">{{ t(`status.${a.status}`, a.status) }}</span>
              <div v-if="a.status === 'pending'" style="display:flex;gap:8px">
                <button class="btn btn-success btn-sm" @click="approveAgency(a.id)" :disabled="actioning === a.id">{{ t('admin.approve') }}</button>
                <button class="btn btn-danger btn-sm" @click="rejectAgency(a.id)" :disabled="actioning === a.id">{{ t('admin.reject') }}</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Users Tab -->
    <div v-else-if="tab === 'users'">
      <div v-if="!users.length" class="empty-state glass-card">
        <p>{{ t('admin.noUsers') }}</p>
      </div>

      <div v-else class="res-list">
        <div v-for="u in users" :key="u.id" class="glass-card res-card">
          <div class="flex-between">
            <div>
              <p style="font-weight:700;font-size:1.1rem;color:var(--accent)">{{ u.first_name }} {{ u.last_name }}</p>
              <p style="color:var(--muted);font-size:0.88rem;margin-top:4px">{{ t('admin.email') }}: {{ u.email }} | {{ t('admin.role') }}: {{ u.role }}</p>
            </div>
            <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:8px">
              <span class="badge" :class="`badge-${u.status}`">{{ t(`status.${u.status}`, u.status) }}</span>
              <div v-if="u.role !== 'admin'" style="display:flex;gap:8px">
                <button v-if="u.status === 'active'" class="btn btn-danger btn-sm" @click="suspendUser(u.id)" :disabled="actioning === u.id">{{ t('admin.suspend') }}</button>
                <button v-if="u.status === 'blocked'" class="btn btn-success btn-sm" @click="activateUser(u.id)" :disabled="actioning === u.id">{{ t('admin.activate') }}</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, inject, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

const { t } = useI18n()
const toast = inject('toast')

const tab = ref('agencies')
const loading = ref(true)
const agencies = ref([])
const users = ref([])
const actioning = ref(null)

async function loadData() {
  loading.value = true
  try {
    if (tab.value === 'agencies') {
      const res = await api.get('/admin/agencies')
      agencies.value = res.data ?? res
    } else {
      const res = await api.get('/admin/users')
      users.value = res.data ?? res
    }
  } catch (e) {
    toast(e.message, 'error')
  } finally {
    loading.value = false
  }
}

watch(tab, loadData)

async function approveAgency(id) {
  if (!confirm(t('admin.confirmApprove'))) return
  actioning.value = id
  try {
    await api.put(`/admin/agencies/${id}/approve`)
    toast(t('admin.agencyApproved'), 'success')
    loadData()
  } catch (e) {
    toast(e.message, 'error')
  } finally {
    actioning.value = null
  }
}

async function rejectAgency(id) {
  if (!confirm(t('admin.confirmReject'))) return
  actioning.value = id
  try {
    await api.put(`/admin/agencies/${id}/reject`)
    toast(t('admin.agencyRejected'), 'success')
    loadData()
  } catch (e) {
    toast(e.message, 'error')
  } finally {
    actioning.value = null
  }
}

async function suspendUser(id) {
  if (!confirm(t('admin.confirmSuspend'))) return
  actioning.value = id
  try {
    await api.put(`/admin/users/${id}/suspend`)
    toast(t('admin.userSuspended'), 'success')
    loadData()
  } catch (e) {
    toast(e.message, 'error')
  } finally {
    actioning.value = null
  }
}

async function activateUser(id) {
  if (!confirm(t('admin.confirmActivate'))) return
  actioning.value = id
  try {
    await api.put(`/admin/users/${id}/activate`)
    toast(t('admin.userActivated'), 'success')
    loadData()
  } catch (e) {
    toast(e.message, 'error')
  } finally {
    actioning.value = null
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.tabs { display: flex; border-bottom: 1px solid var(--line); margin-bottom: 24px; gap: 16px; }
.tab { padding: 10px 4px; font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; color: var(--muted); background: none; border: none; cursor: pointer; border-bottom: 2px solid transparent; transition: all var(--transition-fast); }
.tab.active { color: var(--accent); border-bottom-color: var(--accent); }

.res-list { display: flex; flex-direction: column; gap: 16px; }
.res-card { padding: 20px; }

.empty-state { text-align: center; padding: 64px 32px; color: var(--muted); }
</style>
