import { createI18n } from 'vue-i18n'
import fr from './locales/fr.json'
import ar from './locales/ar.json'

const savedLocale = localStorage.getItem('rdp_locale')
const defaultLocale = savedLocale || import.meta.env.VITE_DEFAULT_LOCALE || 'ar'

export const i18n = createI18n({
  legacy: false,
  locale: defaultLocale,
  fallbackLocale: 'fr',
  messages: { fr, ar },
})

export function applyDocumentDirection(locale) {
  const dir = locale === 'ar' ? 'rtl' : 'ltr'
  document.documentElement.setAttribute('lang', locale)
  document.documentElement.setAttribute('dir', dir)
  document.body.setAttribute('dir', dir)
}

applyDocumentDirection(defaultLocale)
