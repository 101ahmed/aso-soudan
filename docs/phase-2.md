# Phase 2 — Conception base de données

## Livrables

- ERD : `database/erd.md`
- Migrations Laravel : `backend/database/migrations/2026_08_09_110*.php`

## Tables créées (hors Laravel système)

users *(étendu)*, roles, permissions, user_roles, role_permissions,  
departments, members,  
academic_years, education_stages, levels, subjects, level_subject,  
teachers, teacher_subject, guardians, students, student_guardians, student_subject,  
class_groups, class_students, academic_sessions, student_attendances, teacher_attendances,  
news, events, event_registrations, albums, media, documents,  
notifications, consents, audit_logs

## Validation

```bash
cd backend
php artisan migrate
php artisan migrate:status
```

## Suite

Phase 3 — Users / Roles / Permissions (API + seeders + écrans admin).
