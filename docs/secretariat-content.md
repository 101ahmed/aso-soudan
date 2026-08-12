# Contenu unifié par secrétariat (Phase 1)

## Modèle

- Table `departments` = secrétariats (`code` = slug public : `academic`, `social`, …)
- Contenu lié via `department_id` : `news`, `announcements`, `albums`
- Pivot `department_user` : utilisateur ↔ secrétariat
- Statuts : `draft` | `pending_review` | `published` | `archived`

## API publique

- `GET /api/public/departments`
- `GET /api/public/secretariats/{code}/feed`
- `GET /api/public/news`, `GET /api/public/news/{slug}`
- `GET /api/public/announcements`
- `GET /api/public/albums`

## API admin

Préfixe : `/api/admin/departments/{code}/…`  
Middleware : `auth:sanctum` + `department:read|write` + permissions (`news.*`, `announcement.*`, `gallery.*`)

## Frontend

- Admin : `/admin/secretariats/:code` (news, annonces, albums)
- Public : `/secretariats/:code` consomme le feed API (fallback statique si vide)
- Rôle secrétariat → redirection vers son dashboard ; Super Admin voit tout

## Permissions contenu vs métier

- Éditeur (`CONTENT_EDITOR` + department) : news/annonces/galerie **sans** `student.*`
- Responsable académique : contenu **+** étudiants / présence

## Déploiement

Après pull : `php artisan migrate --force` puis seeders (déjà via `rdp:ensure-admin`).
