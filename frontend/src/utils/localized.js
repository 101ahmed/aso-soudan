export const LOCALES = ['ar', 'fr', 'en']

export function isRtl(locale) {
  return locale === 'ar'
}

export function localized(value, locale, fallback = '') {
  if (value == null) return fallback
  if (typeof value === 'string' || typeof value === 'number') return String(value)
  if (Array.isArray(value)) return value
  return value[locale] || value.en || value.fr || value.ar || fallback
}

export function localizedList(value, locale) {
  const picked = localized(value, locale, [])
  return Array.isArray(picked) ? picked : []
}

export function localizedField(item, locale, arKey, frKey, enKey) {
  if (!item) return ''
  const ar = item[arKey]
  const fr = item[frKey]
  const en = enKey ? item[enKey] : undefined
  if (locale === 'ar') return ar || fr || en || ''
  if (locale === 'en') return en || fr || ar || ''
  return fr || en || ar || ''
}

export function pickName(item, locale) {
  return localizedField(item, locale, 'name_ar', 'name_fr', 'name_en') || item?.name || ''
}

export function pickTitle(item, locale) {
  if (item?.title && typeof item.title === 'object') return localized(item.title, locale)
  return localizedField(item, locale, 'title_ar', 'title_fr', 'title_en')
}

export function pickContent(item, locale) {
  if (item?.content && typeof item.content === 'object') return localized(item.content, locale)
  return localizedField(item, locale, 'content_ar', 'content_fr', 'content_en')
}
