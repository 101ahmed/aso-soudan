# Phase 1 — Setup projet

## Validé

- Repository monorepo initialisé
- Backend Laravel avec routes API
- Sanctum installé (`HasApiTokens` sur `User`)
- CORS configuré pour `http://127.0.0.1:5173`
- Frontend Vue connecté via Axios à `/api/health`
- i18n FR / AR avec bascule RTL

## Notes techniques WAMP

- Utiliser PHP WAMP 8.3.6 (fileinfo activé)
- Forcer InnoDB (`config/database.php` → `engine => InnoDB`)
- `Schema::defaultStringLength(191)` conservé par compatibilité
- API locale sur `http://127.0.0.1:8001` (le port 8000 est souvent déjà pris)
- Frontend local sur `http://127.0.0.1:5173`
