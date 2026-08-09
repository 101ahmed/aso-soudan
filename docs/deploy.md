# Déploiement RDP (hébergement mutualisé, sans domaine)

> Alternative plus simple sans FTP : **[Render](deploy-render.md)** (`*.onrender.com`).

Architecture retenue : **un seul site HTTPS**. Le build Vue est servi depuis `backend/public/`, l’API Laravel depuis `/api`. CORS et cookies restent simples.

Exemple d’URL temporaire (sous-domaine hébergeur) :

`https://YOUR-SUBDOMAIN.example`

---

## 1. Prérequis hébergeur

- PHP **8.2+** (idéalement **8.3**), extensions : `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `bcmath`
- MySQL 8 (ou MariaDB compatible)
- Accès FTP/SFTP ou File Manager + Terminal SSH si disponible
- Document root du sous-domaine = dossier **`public`** de Laravel

### Créer la base MySQL

Dans cPanel / hPanel :

1. Créer une base (ex. `u123_rdp`)
2. Créer un utilisateur MySQL + mot de passe fort
3. Accorder tous les privilèges sur cette base
4. Noter : host (souvent `localhost` ou `127.0.0.1`), nom base, user, password

---

## 2. Build local (SPA → `backend/public`)

```bash
cd frontend
copy .env.production.example .env.production
# Windows PowerShell : Copy-Item .env.production.example .env.production
```

Vérifier [`frontend/.env.production`](../frontend/.env.production.example) :

```env
VITE_API_URL=/api
VITE_DEFAULT_LOCALE=ar
```

Puis :

```bash
cd frontend
npm install
npm run build:deploy
```

Cela exécute `vite build` puis copie `frontend/dist/*` vers `backend/public/`, **sans écraser** `.htaccess` ni `index.php`.

Sous Windows, la copie utilise `scripts/copy-dist-to-public.ps1` (chemins non-ASCII plus fiables). Sous Linux/macOS : `node scripts/copy-dist-to-public.mjs` ou `bash scripts/copy-dist-to-public.sh`.

Vérifier en local (optionnel) :

```bash
cd backend
php artisan serve --host=127.0.0.1 --port=8001
```

Ouvrir `http://127.0.0.1:8001/` (SPA) et `http://127.0.0.1:8001/api/health`.

---

## 3. Fichier `.env` production sur le serveur

Copier [`backend/.env.production.example`](../backend/.env.production.example) vers `.env` **sur le serveur uniquement** (ne jamais committer).

Remplacer au minimum :

| Variable | Valeur |
|---|---|
| `APP_URL` | `https://YOUR-SUBDOMAIN.example` |
| `FRONTEND_URL` | `https://YOUR-SUBDOMAIN.example` |
| `APP_DEBUG` | `false` |
| `APP_ENV` | `production` |
| `DB_*` | credentials MySQL hébergeur |
| `SANCTUM_STATEFUL_DOMAINS` | `YOUR-SUBDOMAIN.example` |

Générer la clé :

```bash
php artisan key:generate
```

---

## 4. Upload

Uploader le dossier `backend/` (après `composer install --no-dev` en local **ou** Composer sur le serveur).

**Ne pas uploader :**

- `backend/.env` local de WAMP
- `backend/node_modules` (si présent)
- `frontend/node_modules`
- `frontend/` complet (sauf si vous rebuild sur le serveur)

**Structure attendue sur l’hébergeur :**

```text
/home/.../rdp/          ← hors webroot si possible
  app/
  bootstrap/
  config/
  database/
  public/               ← document root du sous-domaine
    index.php
    index.html          ← SPA
    assets/
    .htaccess
  storage/
  vendor/
  .env
  artisan
```

Si l’hébergeur impose `public_html` :

- soit pointer le sous-domaine vers `.../rdp/public`
- soit placer le contenu de `public/` dans `public_html` et adapter les chemins `index.php` (moins recommandé)

---

## 5. Commandes sur le serveur

```bash
cd /chemin/vers/backend
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

Droits écriture :

- `storage/`
- `bootstrap/cache/`

### Sécurité admin

Le seeder de développement crée :

- Email : `admin@acs-rennes.fr`
- Mot de passe : `Password123!`

**Changez immédiatement le mot de passe en production** (SQL, Tinker, ou écran admin une fois connecté).

---

## 6. Checklist de validation

Cochez après mise en ligne (`SOUS` = votre sous-domaine HTTPS) :

| # | Test | Attendu |
|---|---|---|
| 1 | `https://SOUS/` | Page d’accueil publique (logo, AR/FR) |
| 2 | `https://SOUS/api/health` | JSON type `{"status":"ok",...}` |
| 3 | `https://SOUS/about` | Page « من نحن » (pas de 404) |
| 4 | Rafraîchir `https://SOUS/about` | Toujours la SPA (fallback `.htaccess`) |
| 5 | `https://SOUS/a-propos` | Redirection / page À propos |
| 6 | `https://SOUS/login` | Formulaire de connexion |
| 7 | Login Super Admin | Accès `/admin` selon rôle |
| 8 | `https://SOUS/secretariats` | Liste des secrétariats |
| 9 | HTTPS | Cadenas navigateur (certificat hébergeur) |
| 10 | `APP_DEBUG=false` | Pas de stack trace Laravel publique |

En cas d’erreur 500 : consulter `storage/logs/laravel.log`, droits `storage/`, et `php artisan config:clear`.

En cas de 404 sur les routes Vue : vérifier que le document root est bien `public/` et que `.htaccess` est présent avec `mod_rewrite` activé.

---

## 7. Plus tard : nom de domaine

1. Acheter le domaine (ex. `acs-rennes.fr`)
2. Pointer les DNS A/AAAA (ou nameservers) vers l’hébergeur
3. Activer SSL
4. Mettre à jour `APP_URL`, `FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS`
5. `php artisan config:cache`
6. Rebuild frontend seulement si `VITE_API_URL` n’était pas relatif (`/api`)

---

## Scripts utiles

| Commande | Rôle |
|---|---|
| `cd frontend && npm run build:deploy` | Build Vue + copie vers `backend/public` |
| `cd frontend && npm run copy:public` | Recopie `dist` (PowerShell sous Windows) |
| `bash scripts/copy-dist-to-public.sh` | Recopie sous Linux/macOS |
| `node scripts/build-production.mjs` | Depuis la **racine** du monorepo |
