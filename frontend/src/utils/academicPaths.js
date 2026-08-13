export function attendanceBaseFromPath(path) {
  if (String(path || '').startsWith('/admin/teacher')) {
    return '/admin/teacher/attendance'
  }

  return '/admin/secretariats/academic/attendance'
}
