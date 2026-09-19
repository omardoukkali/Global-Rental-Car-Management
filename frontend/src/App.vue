<template>
  <RouterView />
  <SmartDriveAssistant
    v-if="auth.isAuthenticated && auth.user?.role !== 'agency' && auth.user?.role !== 'admin'"
  />
</template>

<script setup>
import { RouterView } from 'vue-router'
import SmartDriveAssistant from '@/components/SmartDriveAssistant.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>