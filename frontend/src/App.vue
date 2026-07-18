<template>
  <AppLayout>
    <RouterView />
  </AppLayout>

  <!-- Global toast portal -->
  <Teleport to="body">
    <div class="toast-portal">
      <TransitionGroup name="toast">
        <div
          v-for="t in toasts"
          :key="t.id"
          class="glass-card toast-item"
          :class="t.type === 'success' ? 'toast-success' : 'toast-error'"
        >
          <CheckCircle2Icon v-if="t.type === 'success'" class="toast-icon" />
          <AlertCircleIcon  v-else                       class="toast-icon" />
          <div>
            <p class="toast-title">{{ t.type === 'success' ? 'Success' : 'Error' }}</p>
            <p class="toast-msg">{{ t.message }}</p>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { provide, ref } from 'vue'
import { RouterView } from 'vue-router'
import { CheckCircle2 as CheckCircle2Icon, AlertCircle as AlertCircleIcon } from 'lucide-vue-next'
import AppLayout from '@/components/AppLayout.vue'

const toasts = ref([])
let _id = 0

function addToast(message, type = 'success') {
  const id = _id++
  toasts.value.push({ id, message, type })
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id) }, 4000)
}

provide('toast', addToast)
</script>

<style scoped>
.toast-portal {
  position: fixed; bottom: 24px; right: 24px;
  display: flex; flex-direction: column; gap: 10px;
  z-index: 9999; max-width: 340px;
}
.toast-item {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 14px 16px; border-radius: 10px;
}
.toast-success { border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.07); }
.toast-error   { border-color: rgba(239,68,68,0.3);  background: rgba(239,68,68,0.07);  }
.toast-icon    { width: 18px; height: 18px; flex-shrink: 0; margin-top: 2px; }
.toast-success .toast-icon { color: var(--success); }
.toast-error   .toast-icon { color: var(--danger);  }
.toast-title   { font-size: 0.85rem; font-weight: 700; margin-bottom: 2px; }
.toast-success .toast-title { color: #6ee7b7; }
.toast-error   .toast-title { color: #fca5a5; }
.toast-msg     { font-size: 0.78rem; color: var(--muted); line-height: 1.4; }
</style>
