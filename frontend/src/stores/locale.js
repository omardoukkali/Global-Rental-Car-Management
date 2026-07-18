import { defineStore } from 'pinia'
import { ref } from 'vue'
import i18n from '@/i18n'

export const useLocaleStore = defineStore('locale', () => {
  const current = ref(localStorage.getItem('grcm-locale') || 'en')

  function setLocale(code) {
    current.value = code
    localStorage.setItem('grcm-locale', code)
    i18n.global.locale.value = code
    document.documentElement.setAttribute('lang', code)
    document.documentElement.setAttribute('dir', code === 'ar' ? 'rtl' : 'ltr')
  }

  return { current, setLocale }
})
