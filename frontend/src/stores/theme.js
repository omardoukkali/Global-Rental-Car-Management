import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const current = ref(localStorage.getItem('grcm-theme') || 'calm')

  function setTheme(name) {
    current.value = name
    localStorage.setItem('grcm-theme', name)
    document.documentElement.setAttribute('data-theme', name)
  }

  return { current, setTheme }
})
