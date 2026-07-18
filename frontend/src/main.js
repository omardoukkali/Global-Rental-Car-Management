import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from '@/router'
import App from './App.vue'
import '@/assets/main.css'
import i18n from '@/i18n'

// Apply saved theme before mount — prevents flash
const savedTheme = localStorage.getItem('grcm-theme') || 'calm'
document.documentElement.setAttribute('data-theme', savedTheme)

// Apply saved locale before mount — prevents flash of LTR/English
const savedLocale = localStorage.getItem('grcm-locale') || 'en'
i18n.global.locale.value = savedLocale
document.documentElement.setAttribute('lang', savedLocale)
document.documentElement.setAttribute('dir', savedLocale === 'ar' ? 'rtl' : 'ltr')

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(i18n)
app.mount('#app')
