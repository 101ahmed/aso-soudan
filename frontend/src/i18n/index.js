import { createI18n } from 'vue-i18n'
import fr from './locales/fr.json'
import ar from './locales/ar.json'
import en from './locales/en.json'
import { LOCALES, isRtl } from '@/utils/localized'

const savedLocale = localStorage.getItem('rdp_locale')
const defaultLocale = LOCALES.includes(savedLocale)
  ? savedLocale
  : (import.meta.env.VITE_DEFAULT_LOCALE || 'ar')

export const i18n = createI18n({
  legacy: false,
  locale: defaultLocale,
  fallbackLocale: ['fr', 'en', 'ar'],
  messages: { fr, ar, en },
})

export function applyDocumentDirection(locale) {
  const dir = isRtl(locale) ? 'rtl' : 'ltr'
  document.documentElement.setAttribute('lang', locale)
  document.documentElement.setAttribute('dir', dir)
  document.body.setAttribute('dir', dir)
}

applyDocumentDirection(defaultLocale)
