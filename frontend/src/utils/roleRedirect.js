import { primaryDepartmentCode, ROLE_TO_DEPARTMENT } from '@/utils/departmentAccess'

const ROLE_HOME = {
  SUPER_ADMIN: '/admin',
  PRESIDENT: '/admin/president',
  GENERAL_SECRETARIAT: '/admin/secretariats/general',
  ACADEMIC_SECRETARIAT: '/admin/secretariats/academic',
  SOCIAL_SECRETARIAT: '/admin/secretariats/social',
  MEDIA_SECRETARIAT: '/admin/secretariats/media',
  WOMEN_CHILDREN: '/admin/secretariats/women-children',
  STATISTICS_SECRETARIAT: '/admin/secretariats/statistics',
  EXTERNAL_RELATIONS: '/admin/secretariats/external-relations',
  SPORTS_SECRETARIAT: '/admin/secretariats/sports',
  CONTENT_EDITOR: '/admin',
  SHURA_COUNCIL: '/admin/shura',
  SHURA_PRESIDENT: '/admin/shura',
  SHURA_VICE_PRESIDENT: '/admin/shura',
  SHURA_SECRETARY: '/admin/shura',
  SHURA_MEMBER: '/admin/shura',
  SHURA_CONTENT_EDITOR: '/admin/shura',
  PARENTS_COUNCIL: '/admin',
  TEACHER: '/admin',
  PARENT: '/admin',
  MEMBER: '/admin',
}

export function resolvePostLoginPath(user) {
  const roles = user?.roles || []
  const codes = roles.map((role) => role.code)

  if (codes.includes('PRESIDENT') && !codes.includes('SUPER_ADMIN')) {
    return ROLE_HOME.PRESIDENT
  }
  if (codes.includes('SUPER_ADMIN')) {
    return ROLE_HOME.SUPER_ADMIN
  }

  const shuraRole = codes.find((c) => c.startsWith('SHURA_'))
  if (shuraRole) {
    return ROLE_HOME[shuraRole] || '/admin/shura'
  }

  const primary = primaryDepartmentCode(user)
  if (primary) {
    return `/admin/secretariats/${primary}`
  }

  for (const code of codes) {
    const dept = ROLE_TO_DEPARTMENT[code]
    if (dept) return `/admin/secretariats/${dept}`
  }

  if (codes.includes('CONTENT_EDITOR')) {
    return ROLE_HOME.CONTENT_EDITOR
  }
  if (codes.includes('TEACHER')) {
    return ROLE_HOME.TEACHER
  }

  const firstKnown = codes.find((code) => ROLE_HOME[code])
  return firstKnown ? ROLE_HOME[firstKnown] : '/admin'
}
