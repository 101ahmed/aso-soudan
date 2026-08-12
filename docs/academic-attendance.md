# Présence / absence par matière (الأمانة الأكاديمية)

## Modèle

```
Subject (مادة)
  └── ClassGroup (فصل للمادة)
        ├── Students (inscrits)
        └── AcademicSession (حصة)
              └── StudentAttendance (present|absent|late|excused)
```

## Accès admin

`/admin/secretariats/academic/attendance`

Permissions : `attendance.view`, `attendance.create` (rôles `ACADEMIC_SECRETARIAT`, `TEACHER`, `SUPER_ADMIN`).

## API

- `GET /api/admin/academic/attendance/overview`
- `GET /api/admin/academic/subjects/{id}/classes`
- `GET|POST /api/admin/academic/classes/{id}/sessions`
- `GET|POST /api/admin/academic/sessions/{id}/sheet`

## Seed démo

```bash
php artisan db:seed --class=AcademicAttendanceSeeder
```

Crée année 2026/2027, 4 matières, 6 élèves, une classe par matière + une séance du jour.
