<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC]">
    <AppHeader />
    <div class="flex-1">
      <RouterView />
    </div>
    <SmartDriveAssistant
      v-if="auth.isAuthenticated && auth.user?.role === 'client'"
    />
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'
import SmartDriveAssistant from '@/components/SmartDriveAssistant.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>
