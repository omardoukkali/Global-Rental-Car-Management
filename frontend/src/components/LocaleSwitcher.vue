<template>
  <div class="locale-switcher">
    <button
      v-for="loc in locales"
      :key="loc.code"
      @click="locale.setLocale(loc.code)"
      :class="['locale-btn', { active: locale.current === loc.code }]"
      :title="loc.label"
    >
      <span class="locale-flag">{{ loc.flag }}</span>
      <span class="locale-label">{{ loc.code.toUpperCase() }}</span>
    </button>
  </div>
</template>

<script setup>
import { useLocaleStore } from '@/stores/locale'

const locale = useLocaleStore()

const locales = [
  { code: 'en', label: 'English', flag: '🇬🇧' },
  { code: 'fr', label: 'Français', flag: '🇫🇷' },
  { code: 'ar', label: 'العربية', flag: '🇲🇦' },
]
</script>

<style scoped>
.locale-switcher {
  display: flex;
  gap: 4px;
  background: var(--surface);
  padding: 4px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--line);
}
.locale-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background var(--transition-fast);
}
.locale-btn:hover {
  background: rgba(128,128,128,0.1);
}
.locale-btn.active {
  background: rgba(128,128,128,0.2);
}
.locale-flag {
  font-size: 0.9rem;
  line-height: 1;
}
.locale-label {
  font-family: var(--font-body);
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--muted);
}
.locale-btn.active .locale-label {
  color: var(--ink);
  font-weight: 600;
}

@media (max-width: 640px) {
  .locale-label { display: none; }
  .locale-btn { padding: 4px; }
}
</style>
