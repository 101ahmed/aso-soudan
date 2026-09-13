export function attendanceBaseFromPath(path) {
  if (String(path || '').startsWith('/admin/teacher')) {
    return '/admin/teacher/attendance'
  }

  return '/admin/secretariats/academic/attendance'
}

export function academicBaseFromPath(path) {
  if (String(path || '').startsWith('/admin/teacher')) {
    return '/admin/teacher'
  }

  return '/admin/secretariats/academic'
}
