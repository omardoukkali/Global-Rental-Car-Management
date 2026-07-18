import { ref } from 'vue';

const getCookie = (name) => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
  return 'en';
};

// Module-level reactive references (global singleton state)
const locale = ref(getCookie('lang') || 'en');
const isRtl = ref(locale.value === 'ar');

export function useSettings() {
  const setLanguage = (lang) => {
    // Validate locale input to prevent garbage values
    const validLocales = ['en', 'fr', 'ar'];
    const chosenLang = validLocales.includes(lang) ? lang : 'en';

    locale.value = chosenLang;
    isRtl.value = chosenLang === 'ar';
    
    // Save to persistent cookie (1 year expiration)
    document.cookie = `lang=${chosenLang}; path=/; max-age=31536000; SameSite=Lax`;
    
    // Update layout document attributes
    document.documentElement.lang = chosenLang;
    document.documentElement.dir = isRtl.value ? 'rtl' : 'ltr';
  };

  return {
    locale,
    isRtl,
    setLanguage
  };
}
