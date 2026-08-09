# Phase 3 — Users / Roles / Permissions

## Backend

- Models : `User`, `Role`, `Permission`
- Middleware : `permission:{code}`
- API :
  - `POST /api/auth/login`
  - `GET /api/auth/me`
  - `POST /api/auth/logout`
  - CRUD `/api/users` (+ disable)
  - `/api/roles`, `/api/roles/{id}/permissions`
  - `/api/permissions`
- Seeders : rôles RDP + permissions + Super Admin

## Frontend

- Login `/login`
- Admin `/admin` (dashboard, users, roles)
- Guards auth + permissions
- i18n FR/AR

## Compte de test

- Email : `admin@acs-rennes.fr`
- Mot de passe : `Password123!`

```bash
cd backend
php artisan db:seed
```
