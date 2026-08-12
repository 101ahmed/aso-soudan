export const ROLE_TO_DEPARTMENT = {
  GENERAL_SECRETARIAT: 'general',
  ACADEMIC_SECRETARIAT: 'academic',
  SOCIAL_SECRETARIAT: 'social',
  MEDIA_SECRETARIAT: 'media',
  WOMEN_CHILDREN: 'women-children',
  STATISTICS_SECRETARIAT: 'statistics',
  EXTERNAL_RELATIONS: 'external-relations',
  SPORTS_SECRETARIAT: 'sports',
}

export function departmentCodesForUser(user) {
  const fromPivot = (user?.departments || []).map((d) => d.code).filter(Boolean)
  if (fromPivot.length) return fromPivot

  const fromRoles = (user?.roles || [])
    .map((r) => ROLE_TO_DEPARTMENT[r.code])
    .filter(Boolean)

  return [...new Set(fromRoles)]
}

export function primaryDepartmentCode(user) {
  const primary = (user?.departments || []).find((d) => d.is_primary)
  if (primary?.code) return primary.code
  return departmentCodesForUser(user)[0] || null
}

export function canAccessDepartment(user, code, { write = false } = {}) {
  if (!user || !code) return false
  if (user.roles?.some((r) => r.code === 'SUPER_ADMIN')) return true
  if (!write && user.roles?.some((r) => r.code === 'PRESIDENT')) return true
  return departmentCodesForUser(user).includes(code)
}
