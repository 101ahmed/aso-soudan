export function formatPublicCount(value) {
  const count = Number(value)
  if (!Number.isFinite(count) || count <= 0) return '0'
  return `+${count}`
}

export function mergePublicStats(items, live, aliases = {}) {
  if (!live || typeof live !== 'object') return items
  return items.map((item) => {
    const source = aliases[item.key] || item.key
    if (!Object.hasOwn(live, source)) return item
    return { ...item, value: formatPublicCount(live[source]) }
  })
}

export function secretariatStatAliases(slug) {
  if (slug === 'statistics') {
    return {
      teachers: 'teachers_and_volunteers',
      programs: 'initiatives',
    }
  }
  if (slug === 'academic') {
    return { programs: 'academic_events' }
  }
  return {}
}
