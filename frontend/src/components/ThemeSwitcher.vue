<template>
  <div class="theme-switcher">
    <button 
      v-for="themeItem in themes" 
      :key="themeItem.name" 
      @click="theme.setTheme(themeItem.name)"
      :class="['theme-btn', { active: theme.current === themeItem.name }]"
      :title="t(themeItem.labelKey)"
    >
      <span class="theme-dot" :style="{ background: themeItem.color }"></span>
      <span class="theme-label">{{ t(themeItem.labelKey) }}</span>
    </button>
  </div>
</template>

<script setup>
import { useThemeStore } from '@/stores/theme'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const theme = useThemeStore()

const themes = [
  { name: 'calm', labelKey: 'themes.calm', color: '#FAF9F6' },
  { name: 'majestic', labelKey: 'themes.majestic', color: '#C9A227' },
  { name: 'marque', labelKey: 'themes.marque', color: '#B08D46' }
]
</script>

<style scoped>
.theme-switcher {
  display: flex;
  gap: 4px;
  background: var(--surface);
  padding: 4px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--line);
}
.theme-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background var(--transition-fast);
}
.theme-btn:hover {
  background: rgba(128,128,128,0.1);
}
.theme-btn.active {
  background: rgba(128,128,128,0.2);
}
.theme-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid var(--line);
}
.theme-label {
  font-family: var(--font-body);
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--muted);
}
.theme-btn.active .theme-label {
  color: var(--ink);
  font-weight: 600;
}

@media (max-width: 640px) {
  .theme-label { display: none; }
  .theme-btn { padding: 4px; }
}
</style>
