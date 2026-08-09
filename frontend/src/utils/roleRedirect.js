const ROLE_HOME = {
  SUPER_ADMIN: '/admin',
  PRESIDENT: '/admin',
  GENERAL_SECRETARIAT: '/admin',
  ACADEMIC_SECRETARIAT: '/admin',
  SOCIAL_SECRETARIAT: '/admin',
  MEDIA_SECRETARIAT: '/admin',
  WOMEN_CHILDREN: '/admin',
  STATISTICS_SECRETARIAT: '/admin',
  EXTERNAL_RELATIONS: '/admin',
  SHURA_COUNCIL: '/admin',
  PARENTS_COUNCIL: '/admin',
  TEACHER: '/admin',
  PARENT: '/admin',
  MEMBER: '/admin',
}

export function resolvePostLoginPath(user) {
  const roles = user?.roles || []
  const codes = roles.map((role) => role.code)

  if (codes.includes('SUPER_ADMIN') || codes.includes('PRESIDENT')) {
    return ROLE_HOME.PRESIDENT
  }
  if (codes.includes('ACADEMIC_SECRETARIAT')) {
    return ROLE_HOME.ACADEMIC_SECRETARIAT
  }
  if (codes.includes('STATISTICS_SECRETARIAT')) {
    return ROLE_HOME.STATISTICS_SECRETARIAT
  }
  if (codes.includes('TEACHER')) {
    return ROLE_HOME.TEACHER
  }

  const firstKnown = codes.find((code) => ROLE_HOME[code])
  return firstKnown ? ROLE_HOME[firstKnown] : '/admin'
}
