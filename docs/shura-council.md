# Conseil de la Choura (Phase 1)

Modèle aligné sur les secrétariats : **page publique** + **dashboard interne**, avec séparation stricte public / privé.

## Structure

```
مجلس الشورى
├── رئيس مجلس الشورى      (SHURA_PRESIDENT)
├── نائب رئيس المجلس      (SHURA_VICE_PRESIDENT)
├── مقرر / أمين المجلس    (SHURA_SECRETARY)
├── أعضاء                 (SHURA_MEMBER)
└── محرر contenu          (SHURA_CONTENT_EDITOR)
```

Department CMS : `code = shura` (news, annonces, albums via `/admin/secretariats/shura`).

## Membres (`council_members`)

| Champ | Public | Interne |
|-------|--------|---------|
| Nom, fonction, bio, photo | si `is_public` | oui |
| Dates de mandat, statut | résumé public | oui |
| Email, téléphone, `user_id` | non | oui |
| Présence / absences | non | oui |

Statuts : `active` | `inactive` | `former` | `suspended`.

Deux types de profil :

1. **Affiché seulement** — fiche membre sans compte (`user_id` null).
2. **Avec compte** — `user_id` + rôle `SHURA_*` pour accéder au dashboard.

## Rôles + Section + Permissions

- Section / department : `shura` (via `DepartmentRoleMap`).
- Exemples de permissions : `shura.member.*`, `shura.meeting.*`, `shura.proposal.*`, `shura.document.*`, plus `news.*` / `announcement.*` / `gallery.*` pour le contenu.

| Rôle | Capacité principale |
|------|---------------------|
| `SHURA_PRESIDENT` | Gestion membres + réunions + contenu + propositions |
| `SHURA_VICE_PRESIDENT` | Comme le président sans `shura.member.manage` |
| `SHURA_SECRETARY` | Réunions, documents, présence |
| `SHURA_MEMBER` | Lecture + créer une proposition |
| `SHURA_CONTENT_EDITOR` | Contenu public du département `shura` uniquement |

## Contenu public

- News / albums / annonces rattachés au département `shura`.
- Annonces : `visibility = public|internal` (seul `public` dans les feeds publics).
- Réunions : `visibility = public|internal` ; PV et présences **jamais** exposés sans auth.

## API

**Public**

- `GET /api/public/shura/members`
- `GET /api/public/shura/meetings`
- Feed secrétariat : `GET /api/public/secretariats/shura/feed`

**Admin** (`auth:sanctum` + permissions)

- `GET /api/admin/shura/overview`
- CRUD `/api/admin/shura/members`
- CRUD `/api/admin/shura/meetings`
- `POST /api/admin/shura/meetings/{id}/attendance`

## Frontend

| Route | Rôle |
|-------|------|
| `/shura-council` | Page publique (API + fallback statique) |
| `/admin/shura` | Vue d’ensemble |
| `/admin/shura/members` | Fiches membres |
| `/admin/shura/meetings` | Réunions |
| `/admin/secretariats/shura/*` | News / annonces / galerie |

Menus du shell filtrés par permission.

## Hors Phase 1 (prévu)

- Propositions / recommandations / décisions avancées
- Fiche membre détaillée (réunions, propositions, tâches)
- Notifications
- UI complète de la présence

## Déploiement

```bash
php artisan migrate --force
php artisan db:seed --class=DepartmentSeeder
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=ShuraMemberSeeder
# ou : php artisan rdp:ensure-admin
```
