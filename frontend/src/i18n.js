import { createI18n } from 'vue-i18n'
import en from '@/locales/en.json'
import fr from '@/locales/fr.json'
import ar from '@/locales/ar.json'

const i18n = createI18n({
  legacy: false,          // Composition API mode
  locale: 'en',
  fallbackLocale: 'en',
  messages: { en, fr, ar },
})

export default i18n
